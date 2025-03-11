<?php

namespace Klevu\Search\Test\Integration\Service\Account;

use Klevu\Search\Api\Service\Account\ConfirmIntegrationInterface;
use Klevu\Search\Exception\InvalidApiKeyException;
use Klevu\Search\Service\Account\GetAccountDetails;
use Klevu\Search\Service\Account\KlevuApi\GetAccountDetails as ApiGetAccountDetails;
use Klevu\Search\Service\Account\Model\AccountDetails;
use Klevu\Search\Validator\JsApiKeyValidator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ReinitableConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 * @runTestsInSeparateProcesses
 */
class ConfirmIntegrationServiceTest extends TestCase
{
    /**
     * @var string
     */
    private $mockRestApiKey = 'klevu-someValidRestApiKey';
    /**
     * @var string
     */
    private $mockJsApiKey = 'klevu-someValidJsApiKey';

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testExecuteCanBeCalled()
    {
        $this->setUpPhp5();

        $mockGetApiAccountDetails = $this->getMockBuilder(ApiGetAccountDetails::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGetApiAccountDetails->expects($this->once())
            ->method('execute')
            ->willReturn($this->getMockApiResponse());

        $getAccountDetails = $this->objectManager->create(GetAccountDetails::class, [
            'getAccountDetailsApi' => $mockGetApiAccountDetails
        ]);

        /** @var ConfirmIntegrationInterface $confirmIntegration */
        $confirmIntegration = $this->objectManager->create(ConfirmIntegrationInterface::class, [
            'getAccountDetails' => $getAccountDetails
        ]);
        $apiKeys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];
        $storeId = 1;

        $confirmIntegration->execute($apiKeys, $storeId);
    }

    /**
     * @dataProvider invalidJsApiKeys
     */
    public function testCheckThrowsExceptionForIncorrectJsAPiKey($jsApiKey)
    {
        $this->setUpPhp5();
        $this->expectException(InvalidApiKeyException::class);
        /** @var ConfirmIntegrationInterface $confirmIntegration */
        $confirmIntegration = $this->objectManager->get(ConfirmIntegrationInterface::class);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $jsApiKey
        ];
        $storeId = 1;

        $confirmIntegration->execute($keys, $storeId);
    }

    /**
     * @dataProvider invalidRestApiKeys
     */
    public function testCheckThrowsExceptionForIncorrectRestAPiKey($restApiKey)
    {
        $this->setUpPhp5();
        $this->expectException(InvalidApiKeyException::class);
        /** @var ConfirmIntegrationInterface $confirmIntegration */
        $confirmIntegration = $this->objectManager->get(ConfirmIntegrationInterface::class);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $restApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];
        $storeId = 1;

        $confirmIntegration->execute($keys, $storeId);
    }

    public function testThrowsExceptionWhenStoreNotFound()
    {
        $this->setUpPhp5();
        $this->expectException(NoSuchEntityException::class);
        /** @var ConfirmIntegrationInterface $confirmIntegration */
        $confirmIntegration = $this->objectManager->get(ConfirmIntegrationInterface::class);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];
        $storeId = 3985395893;

        $confirmIntegration->execute($keys, $storeId);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/general/single_store_mode/enabled 0
     * @magentoConfigFixture default_store general/single_store_mode/enabled 0
     */
    public function testSavesToCorrectScopeInMultiStoreMode()
    {
        $this->setUpPhp5();

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $defaultStore = $storeManager->getStore('default');

        /** @var \Magento\Framework\App\Config\Storage\WriterInterface $configWriter */
        $configWriter = $this->objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);
        // magentoConfigFixture doesn't play nice when you start manipulating config values
        $configWriter->save('general/single_store_mode/enabled', 0, 'stores', $defaultStore->getId());
        $configWriter->save('general/single_store_mode/enabled', 0, 'default', 0);
        $configWriter->delete('klevu_search/general/rest_api_key', 'stores', $defaultStore->getId());
        $configWriter->delete('klevu_search/general/rest_api_key', 'default', 0);
        $configWriter->delete('klevu_search/general/js_api_key', 'stores', $defaultStore->getId());
        $configWriter->delete('klevu_search/general/js_api_key', 'default', 0);

        /** @var ReinitableConfig $reinitableConfig */
        $reinitableConfig = $this->objectManager->get(ReinitableConfig::class);
        /** @var ScopeConfigInterface $scopeConfig */
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);

        $reinitableConfig->reinit();
        $this->assertEmpty($scopeConfig->getValue(
            'klevu_search/general/rest_api_key',
            'stores',
            $defaultStore->getId()
        ), 'REST Key (store scope): pre-test');
        $this->assertEmpty($scopeConfig->getValue(
            'klevu_search/general/rest_api_key',
            'default',
            0
        ), 'REST Key (global scope): pre-test');
        $this->assertEmpty($scopeConfig->getValue(
            'klevu_search/general/js_api_key',
            'stores',
            $defaultStore->getId()
        ), 'API Key (store scope): pre-test');
        $this->assertEmpty($scopeConfig->getValue(
            'klevu_search/general/js_api_key',
            'default',
            0
        ), 'API Key (global scope): pre-test');

        $mockGetApiAccountDetails = $this->getMockBuilder(ApiGetAccountDetails::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGetApiAccountDetails->expects($this->once())
            ->method('execute')
            ->willReturn($this->getMockApiResponse());

        $getAccountDetails = $this->objectManager->create(GetAccountDetails::class, [
            'getAccountDetailsApi' => $mockGetApiAccountDetails
        ]);

        /** @var ConfirmIntegrationInterface $confirmIntegration */
        $confirmIntegration = $this->objectManager->create(ConfirmIntegrationInterface::class, [
            'getAccountDetails' => $getAccountDetails
        ]);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];

        $confirmIntegration->execute($keys, (int)$defaultStore->getId());

        $reinitableConfig->reinit();
        $this->assertSame(
            $keys['rest_api_key'],
            $scopeConfig->getValue(
                'klevu_search/general/rest_api_key',
                'stores',
                $defaultStore->getId()
            ),
            'REST Key (store scope): post-test'
        );
        $this->assertEmpty(
            $scopeConfig->getValue(
                'klevu_search/general/rest_api_key',
                'default',
                0
            ),
            'REST Key (global scope): post-test'
        );
        $this->assertSame(
            $keys['js_api_key'],
            $scopeConfig->getValue(
                'klevu_search/general/js_api_key',
                'stores',
                $defaultStore->getId()
            ),
            'API Key (store scope): post-test'
        );
        $this->assertEmpty(
            $scopeConfig->getValue(
                'klevu_search/general/js_api_key',
                'default',
                0
            ),
            'API Key (global scope): post-test'
        );

        $configWriter->delete('klevu_search/general/rest_api_key', 'stores', $defaultStore->getId());
        $configWriter->delete('klevu_search/general/rest_api_key', 'default', 0);
        $configWriter->delete('klevu_search/general/js_api_key', 'stores', $defaultStore->getId());
        $configWriter->delete('klevu_search/general/js_api_key', 'default', 0);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     * @magentoConfigFixture default_store general/single_store_mode/enabled 1
     */
    public function testSavesToCorrectScopeInSingleStoreMode()
    {
        $this->setUpPhp5();

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $defaultStore = $storeManager->getStore('default');

        /** @var \Magento\Framework\App\Config\Storage\WriterInterface $configWriter */
        $configWriter = $this->objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);
        // magentoConfigFixture doesn't play nice when you start manipulating config values
        $configWriter->save('general/single_store_mode/enabled', 1, 'stores', $defaultStore->getId());
        $configWriter->save('general/single_store_mode/enabled', 1, 'default', 0);
        $configWriter->delete('klevu_search/general/rest_api_key', 'stores', $defaultStore->getId());
        $configWriter->delete('klevu_search/general/rest_api_key', 'default', 0);
        $configWriter->delete('klevu_search/general/js_api_key', 'stores', $defaultStore->getId());
        $configWriter->delete('klevu_search/general/js_api_key', 'default', 0);

        /** @var ReinitableConfig $reinitableConfig */
        $reinitableConfig = $this->objectManager->get(ReinitableConfig::class);
        /** @var ScopeConfigInterface $scopeConfig */
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);

        $reinitableConfig->reinit();
        $this->assertEmpty($scopeConfig->getValue(
            'klevu_search/general/rest_api_key',
            'stores',
            $defaultStore->getId()
        ), 'REST Key (store scope): pre-test');
        $this->assertEmpty($scopeConfig->getValue(
            'klevu_search/general/rest_api_key',
            'default',
            0
        ), 'REST Key (global scope): pre-test');
        $this->assertEmpty($scopeConfig->getValue(
            'klevu_search/general/js_api_key',
            'stores',
            $defaultStore->getId()
        ), 'API Key (store scope): pre-test');
        $this->assertEmpty($scopeConfig->getValue(
            'klevu_search/general/js_api_key',
            'default',
            0
        ), 'API Key (global scope): pre-test');

        $mockGetApiAccountDetails = $this->getMockBuilder(ApiGetAccountDetails::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGetApiAccountDetails->expects($this->once())
            ->method('execute')
            ->willReturn($this->getMockApiResponse());

        $getAccountDetails = $this->objectManager->create(GetAccountDetails::class, [
            'getAccountDetailsApi' => $mockGetApiAccountDetails
        ]);

        /** @var ConfirmIntegrationInterface $confirmIntegration */
        $confirmIntegration = $this->objectManager->create(ConfirmIntegrationInterface::class, [
            'getAccountDetails' => $getAccountDetails
        ]);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];

        $confirmIntegration->execute($keys, (int)$defaultStore->getId());

        $reinitableConfig->reinit();
        $this->assertSame(
            $keys['rest_api_key'],
            $scopeConfig->getValue(
                'klevu_search/general/rest_api_key',
                'stores',
                $defaultStore->getId()
            ),
            'REST Key (store scope): post-test'
        );
        $this->assertSame(
            $keys['rest_api_key'],
            $scopeConfig->getValue(
                'klevu_search/general/rest_api_key',
                'default',
                0
            ),
            'REST Key (global scope): post-test'
        );
        $this->assertSame(
            $keys['js_api_key'],
            $scopeConfig->getValue(
                'klevu_search/general/js_api_key',
                'stores',
                $defaultStore->getId()
            ),
            'API Key (store scope): post-test'
        );
        $this->assertSame(
            $keys['js_api_key'],
            $scopeConfig->getValue(
                'klevu_search/general/js_api_key',
                'default',
                0
            ),
            'API Key (global scope): post-test'
        );

        $configWriter->delete('klevu_search/general/rest_api_key', 'stores', $defaultStore->getId());
        $configWriter->delete('klevu_search/general/rest_api_key', 'default', 0);
        $configWriter->delete('klevu_search/general/js_api_key', 'stores', $defaultStore->getId());
        $configWriter->delete('klevu_search/general/js_api_key', 'default', 0);
    }

    /**
     * @return array
     */
    public function invalidJsApiKeys()
    {
        return [
            [null],
            [0],
            [123],
            ['incorrect-format'],
            [['array is not valid']],
            ['x' . JsApiKeyValidator::JS_API_KEY_BEGINS . 'yz']
        ];
    }

    /**
     * @return array
     */
    public function invalidRestApiKeys()
    {
        return [
            [null],
            [0],
            [123],
            ['too-short'],
            ['   too-short   '],
            [['array is not valid']]
        ];
    }

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
     * @return void
     * @todo remove once support for PHP 5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }
}
