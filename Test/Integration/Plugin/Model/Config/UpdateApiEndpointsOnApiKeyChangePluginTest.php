<?php

namespace Klevu\Search\Test\Integration\Model\Config;

use Klevu\Search\Api\Service\Account\UpdateEndpointsInterface;
use Klevu\Search\Plugin\Model\Config\UpdateApiEndpointsOnApiKeyChange;
use Klevu\Search\Service\Account\KlevuApi\GetAccountDetails as ApiGetAccountDetails;
use Klevu\Search\Service\Account\GetAccountDetails;
use Klevu\Search\Service\Account\Model\AccountDetails;
use Klevu\Search\Service\Account\Model\AccountDetailsFactory;
use Klevu\Search\Validator\JsApiKeyValidator;
use Magento\Config\Model\Config;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Interception\PluginList;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class UpdateApiEndpointsOnApiKeyChangePluginTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var string
     */
    private $pluginName = 'Klevu_Search::UpdateApiEndpointsOnApiKeyChange';

    /**
     * @magentoAppArea global
     */
    public function testTheModuleDoesNotInterceptsCallsToTheFieldInGlobalScope()
    {
        $this->setupPhp5();

        $pluginInfo = $this->getSystemConfigPluginInfo();
        $this->assertArrayNotHasKey($this->pluginName, $pluginInfo);
    }

    /**
     * @magentoAppArea adminhtml
     */
    public function testTheModuleInterceptsCallsToTheFieldInAdminScope()
    {
        $this->setupPhp5();

        $pluginInfo = $this->getSystemConfigPluginInfo();
        $this->assertArrayHasKey($this->pluginName, $pluginInfo);
        $this->assertSame(UpdateApiEndpointsOnApiKeyChange::class, $pluginInfo[$this->pluginName]['instance']);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     */
    public function testBeforeSave_IsNotCalled_OnOtherSections()
    {
        $this->setupPhp5();
        $store = $this->getStore();
        $website = $this->getWebsite();
        $configData = [
            'section' => 'another_section',
            'website' => $website->getId(),
            'store' => $store->getId(),
            'groups' => [
                'another_group' => [
                    'fields' => [
                        'another_field' => ['value' => 1],
                    ]
                ]
            ]
        ];
        $configModel = $this->objectManager->create(Config::class, ['data' => $configData]);

        $updateEndpoints = $this->getMockBuilder(UpdateEndpointsInterface::class)->disableOriginalConstructor()->getMock();
        $updateEndpoints->expects($this->never())->method('execute');

        $plugin = $this->objectManager->get(UpdateApiEndpointsOnApiKeyChange::class);
        $plugin->beforeSave($configModel);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @dataProvider invalidApiKeysDataProvider
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     */
    public function testBeforeSave_RemovesApiData_WhenApiKeysAreInvalid($jsApiKey, $restApiKey)
    {
        $this->setupPhp5();
        $store = $this->getStore();
        $website = $this->getWebsite();
        $configData = [
            'section' => UpdateApiEndpointsOnApiKeyChange::SECTION_KLEVU_INTEGRATION,
            'website' => $website->getId(),
            'store' => $store->getId(),
            'groups' => [
                UpdateApiEndpointsOnApiKeyChange::GROUP_AUTHENTICATION_KEYS => [
                    'fields' => [
                        UpdateApiEndpointsOnApiKeyChange::CONFIG_FORM_JS_API_KEY => ['value' => $jsApiKey],
                        UpdateApiEndpointsOnApiKeyChange::CONFIG_FORM_REST_API_KEY => ['value' => $restApiKey],
                    ]
                ]
            ]
        ];
        $configModel = $this->objectManager->create(Config::class, ['data' => $configData]);

        $plugin = $this->objectManager->get(UpdateApiEndpointsOnApiKeyChange::class);
        $plugin->beforeSave($configModel);

        $groups = $configModel->getData('groups');
        $this->assertNotSame($configData['groups'], $groups);
        $this->assertArrayNotHasKey(
            UpdateApiEndpointsOnApiKeyChange::CONFIG_FORM_JS_API_KEY,
            $groups[UpdateApiEndpointsOnApiKeyChange::GROUP_AUTHENTICATION_KEYS]['fields']
        );

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     */
    public function testBeforeSave_CallsUpdateEndpoints_WhenApiKeysHaveChanged()
    {
        $this->setupPhp5();
        $store = $this->getStore();
        $website = $this->getWebsite();
        $configData = [
            'section' => UpdateApiEndpointsOnApiKeyChange::SECTION_KLEVU_INTEGRATION,
            'website' => $website->getId(),
            'store' => $store->getId(),
            'groups' => [
                UpdateApiEndpointsOnApiKeyChange::GROUP_AUTHENTICATION_KEYS => [
                    'fields' => [
                        UpdateApiEndpointsOnApiKeyChange::CONFIG_FORM_JS_API_KEY => ['value' => JsApiKeyValidator::JS_API_KEY_BEGINS . 'someValidJsApiKey'],
                        UpdateApiEndpointsOnApiKeyChange::CONFIG_FORM_REST_API_KEY => ['value' => 'someValidRestApiKey'],
                    ]
                ]
            ]
        ];
        $configModel = $this->objectManager->create(Config::class, ['data' => $configData]);

        $mockApiGetAccountDetails = $this->getMockBuilder(ApiGetAccountDetails::class)->disableOriginalConstructor()->getMock();
        $mockApiGetAccountDetails->expects($this->once())->method('execute')->willReturn($this->getMockApiResponse());

        $getAccountDetails = $this->objectManager->create(GetAccountDetails::class, [
            'getAccountDetailsApi' => $mockApiGetAccountDetails,
        ]);

        $mockUpdateEndpoints = $this->getMockBuilder(UpdateEndpointsInterface::class)->disableOriginalConstructor()->getMock();
        $mockUpdateEndpoints->expects($this->once())->method('execute');

        $plugin = $this->objectManager->create(UpdateApiEndpointsOnApiKeyChange::class, [
            'getAccountDetails' => $getAccountDetails,
            'updateEndpoints' => $mockUpdateEndpoints
        ]);
        $plugin->beforeSave($configModel);

        $groups = $configModel->getData('groups');
        $this->assertSame($configData['groups'], $groups);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     */
    public function testBeforeSave_DoesNotCallsUpdateEndpoints_WhenApiKeysHaveNotChanged()
    {
        $this->setupPhp5();
        $store = $this->getStore();
        $website = $this->getWebsite();
        $configData = [
            'section' => UpdateApiEndpointsOnApiKeyChange::SECTION_KLEVU_INTEGRATION,
            'website' => $website->getId(),
            'store' => $store->getId(),
            'groups' => [
                UpdateApiEndpointsOnApiKeyChange::GROUP_AUTHENTICATION_KEYS => [
                    'fields' => [
                        UpdateApiEndpointsOnApiKeyChange::CONFIG_FORM_JS_API_KEY => ['value' => 'klevu-js-api-key'],
                        UpdateApiEndpointsOnApiKeyChange::CONFIG_FORM_REST_API_KEY => ['value' => 'klevu-rest-api-key'],
                    ]
                ]
            ]
        ];
        $configModel = $this->objectManager->create(Config::class, ['data' => $configData]);
        $configModel->setStore('klevu_test_store_1');

        $mockApiGetAccountDetails = $this->getMockBuilder(ApiGetAccountDetails::class)->disableOriginalConstructor()->getMock();
        $mockApiGetAccountDetails->expects($this->never())->method('execute');

        $getAccountDetails = $this->objectManager->create(GetAccountDetails::class, [
            'getAccountDetails' => $mockApiGetAccountDetails,
        ]);

        $mockUpdateEndpoints = $this->getMockBuilder(UpdateEndpointsInterface::class)->disableOriginalConstructor()->getMock();
        $mockUpdateEndpoints->expects($this->never())->method('execute');

        $plugin = $this->objectManager->create(UpdateApiEndpointsOnApiKeyChange::class, [
            'getAccountDetails' => $getAccountDetails,
            'updateEndpoints' => $mockUpdateEndpoints
        ]);
        $plugin->beforeSave($configModel);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     */
    public function testBeforeSave_CallsUpdateEndpoints_WhenApiKeysAreMissing()
    {
        $this->setupPhp5();
        $store = $this->getStore();
        $website = $this->getWebsite();
        $configData = [
            'section' => UpdateApiEndpointsOnApiKeyChange::SECTION_KLEVU_INTEGRATION,
            'website' => $website->getId(),
            'store' => $store->getId(),
            'groups' => [
                UpdateApiEndpointsOnApiKeyChange::GROUP_AUTHENTICATION_KEYS => [
                    'fields' => [
                        UpdateApiEndpointsOnApiKeyChange::CONFIG_FORM_JS_API_KEY => ['value' => ''],
                        UpdateApiEndpointsOnApiKeyChange::CONFIG_FORM_REST_API_KEY => ['value' => ''],
                    ]
                ],
                UpdateApiEndpointsOnApiKeyChange::GROUP_ENDPOINTS => [
                    'fields' => [
                        UpdateApiEndpointsOnApiKeyChange::CONFIG_FORM_ANALYTICS_URL => ['value' => 'analytics.url'],
                        UpdateApiEndpointsOnApiKeyChange::CONFIG_FORM_INDEXING_URL => ['value' => 'indexing.url'],
                    ]
                ]
            ]
        ];
        $configModel = $this->objectManager->create(Config::class, ['data' => $configData]);
        $configModel->setStore('klevu_test_store_1');

        $mockApiGetAccountDetails = $this->getMockBuilder(ApiGetAccountDetails::class)->disableOriginalConstructor()->getMock();
        $mockApiGetAccountDetails->expects($this->never())->method('execute');

        $getAccountDetails = $this->objectManager->create(GetAccountDetails::class, [
            'getAccountDetails' => $mockApiGetAccountDetails,
        ]);

        $mockUpdateEndpoints = $this->getMockBuilder(UpdateEndpointsInterface::class)->disableOriginalConstructor()->getMock();
        $mockUpdateEndpoints->expects($this->once())->method('execute');

        $accountDetails = $this->objectManager->get(AccountDetails::class);
        $mockAccountDetailsFactory = $this->getMockBuilder(AccountDetailsFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAccountDetailsFactory->expects($this->once())->method('create')->willReturn($accountDetails);

        $plugin = $this->objectManager->create(UpdateApiEndpointsOnApiKeyChange::class, [
            'getAccountDetails' => $getAccountDetails,
            'updateEndpoints' => $mockUpdateEndpoints,
            'accountDetailsFactory' => $mockAccountDetailsFactory
        ]);
        $plugin->beforeSave($configModel);

        $groups = $configModel->getData('groups');
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($groups);
        } else {
            $this->assertTrue(is_array($groups), 'Is Array');
        }
        $this->assertArrayHasKey(UpdateApiEndpointsOnApiKeyChange::GROUP_AUTHENTICATION_KEYS, $groups);
        $this->assertArrayNotHasKey(UpdateApiEndpointsOnApiKeyChange::GROUP_ENDPOINTS, $groups);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return array
     */
    public function invalidApiKeysDataProvider()
    {
        // there are no null or empty string keys here as we allow saving of empty field
        return [
            ['js_api_key' => 'incorrect-format', 'rest_api_key' => 'some-valid-key'],
            ['js_api_key' => 0, 'rest_api_key' => 'some-valid-key'],
            ['js_api_key' => 123, 'rest_api_key' => 'some-valid-key'],
            ['js_api_key' => ['array is not valid'], 'rest_api_key' => 'some-valid-key'],
            ['js_api_key' => 'klevu-valid-key', 'rest_api_key' => 0],
            ['js_api_key' => 'klevu-valid-key', 'rest_api_key' => 123],
            ['js_api_key' => 'klevu-valid-key', 'rest_api_key' => ['array is not valid']],
            ['js_api_key' => 'klevu-valid-key', 'rest_api_key' => 'too-short'],
        ];
    }

    /**
     * @return array[]
     */
    private function getSystemConfigPluginInfo()
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->get(PluginList::class);

        return $pluginList->get(Config::class, []);
    }

    /**
     * @return array
     */
    private function getMockApiResponse()
    {
        return [
            ApiGetAccountDetails::RESPONSE_SUCCESS_ACTIVE => true,
            ApiGetAccountDetails::RESPONSE_SUCCESS_PLATFORM => AccountDetails::PLATFORM_MAGENTO,
            ApiGetAccountDetails::RESPONSE_SUCCESS_EMAIL => 'user@klevu.com',
            ApiGetAccountDetails::RESPONSE_SUCCESS_COMPANY_NAME => 'Klevu',
            ApiGetAccountDetails::RESPONSE_SUCCESS_URL_ANALYTICS => 'stats.klevu.com',
            ApiGetAccountDetails::RESPONSE_SUCCESS_URL_CAT_NAV => 'cn26.ksearchnet.com',
            ApiGetAccountDetails::RESPONSE_SUCCESS_URL_INDEXING => 'indexing-qa.ksearchnet.com',
            ApiGetAccountDetails::RESPONSE_SUCCESS_URL_JS => 'js.klevu.com',
            ApiGetAccountDetails::RESPONSE_SUCCESS_URL_SEARCH => 'eucs26v2.ksearchnet.com',
            ApiGetAccountDetails::RESPONSE_SUCCESS_URL_TIERS => 'tiers.klevu.com',
        ];
    }

    /**
     * @param string $websiteCode
     *
     * @return WebsiteInterface
     * @throws NoSuchEntityException
     */
    protected function getWebsite($websiteCode = 'klevu_test_website_1')
    {
        /** @var WebsiteRepositoryInterface $websiteRepository */
        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);

        return $websiteRepository->get($websiteCode);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    protected function getStore($storeCode = 'klevu_test_store_1')
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }
}
