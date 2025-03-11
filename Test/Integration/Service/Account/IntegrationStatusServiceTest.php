<?php

namespace Klevu\Search\Test\Integration\Service\Account;

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

use Klevu\Registry\Api\ConfigRegistryInterface;
use Klevu\Search\Api\Service\Account\IntegrationStatusInterface;
use Magento\Framework\App\Config\Storage\Writer as ScopeConfigWriter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation disabled
 * @magentoAppArea adminhtml
 */
class IntegrationStatusServiceTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture default/general/single_store_mode/enabled 0
     * @magentoConfigFixture klevu_test_store_1_store general/single_store_mode/enabled 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_integration/integration/status 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-someValidRestApiKey
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-someValidJsApiKey
     */
    public function testIsIntegratedReturnsTrueWhenApiKeysArePresentForExplicitStoreInMultisiteMode()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');

        $integrationStatus = $this->objectManager->get(IntegrationStatusInterface::class);
        $result = $integrationStatus->isIntegrated($store);

        $this->assertTrue($result, 'Is Integrated');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture default/general/single_store_mode/enabled 0
     * @magentoConfigFixture default_store general/single_store_mode/enabled 0
     * @magentoConfigFixture klevu_test_store_1_store general/single_store_mode/enabled 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_integration/integration/status 0
     * @magentoConfigFixture default_store klevu_integration/integration/status 1
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key klevu-someValidRestApiKey
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-someValidJsApiKey
     */
    public function testIsIntegratedReturnsTrueWhenApiKeysArePresentForNullStoreInMultisiteMode()
    {
        $this->setUpPhp5();

        $integrationStatus = $this->objectManager->get(IntegrationStatusInterface::class);
        $result = $integrationStatus->isIntegrated();

        $this->assertTrue($result, 'Is Integrated');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     * @magentoConfigFixture default_store general/single_store_mode/enabled 1
     * @magentoConfigFixture default/klevu_integration/integration/status 1
     * @magentoConfigFixture default/klevu_search/general/rest_api_key klevu-someValidRestApiKey
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-someValidJsApiKey
     */
    public function testIsIntegratedReturnsTrueWhenApiKeysArePresentForExplicitStoreInSingleStoreMode()
    {
        $this->setUpPhp5();
        $store = $this->getStore('admin');

        $integrationStatus = $this->objectManager->get(IntegrationStatusInterface::class);
        $result = $integrationStatus->isIntegrated($store);

        $this->assertTrue($result, 'Is Integrated');
    }

    /**
     * @magentoConfigFixture default/general/single_store_mode/enabled 1
     * @magentoConfigFixture default_store general/single_store_mode/enabled 1
     * @magentoConfigFixture default/klevu_integration/integration/status 1
     * @magentoConfigFixture default/klevu_search/general/rest_api_key klevu-someValidRestApiKey
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-someValidJsApiKey
     */
    public function testIsIntegratedReturnsTrueWhenApiKeysArePresentForNullStoreInSingleStoreMode()
    {
        $this->setUpPhp5();

        $integrationStatus = $this->objectManager->get(IntegrationStatusInterface::class);
        $result = $integrationStatus->isIntegrated();

        $this->assertTrue($result, 'Is Integrated');
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_integration/integration/status 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-someValidRestApiKey
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key 0
     */
    public function testIsIntegratedReturnsFalseWhenJsApiKeyMissing()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');

        $integrationStatus = $this->objectManager->get(IntegrationStatusInterface::class);
        $result = $integrationStatus->isIntegrated($store);

        $this->assertFalse($result, 'Is Integrated');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_integration/integration/status 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-someValidJsApiKey
     */
    public function testIsIntegratedReturnsFalseWhenRestApiKeyIsMissing()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');

        $integrationStatus = $this->objectManager->get(IntegrationStatusInterface::class);
        $result = $integrationStatus->isIntegrated($store);

        $this->assertFalse($result, 'Is Integrated');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_integration/integration/status 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-someValidRestApiKey
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-someValidJsApiKey
     */
    public function testIsJustIntegratedReturnsFalseWhenNotIntegrated()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['store' => $store->getId()]);

        $integrationStatus = $this->objectManager->get(IntegrationStatusInterface::class);
        $result = $integrationStatus->isJustIntegrated();

        $this->assertFalse($result, 'Is Just Integrated');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_integration/integration/status 2
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-someValidRestApiKey
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-someValidJsApiKey
     */
    public function testIsJustIntegratedReturnsFalseWhenPreviouslyIntegrated()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['store' => $store->getId()]);

        $integrationStatus = $this->objectManager->get(IntegrationStatusInterface::class);
        $result = $integrationStatus->isJustIntegrated();

        $this->assertFalse($result, 'Is Just Integrated');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_integration/integration/status 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-someValidRestApiKey
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-someValidJsApiKey
     */
    public function testIsJustIntegratedReturnsTrueWhenJustIntegrated()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['store' => $store->getId()]);

        $integrationStatus = $this->objectManager->get(IntegrationStatusInterface::class);
        $result = $integrationStatus->isJustIntegrated();

        $this->assertTrue($result, 'Is Just Integrated');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_integration/integration/status 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-someValidRestApiKey
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key invalid-some-key
     */
    public function testIsJustIntegratedReturnsFalseWhenJsApiKeyIsInvalid()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['store' => $store->getId()]);

        $integrationStatus = $this->objectManager->get(IntegrationStatusInterface::class);
        $result = $integrationStatus->isJustIntegrated();

        $this->assertFalse($result, 'Is Just Integrated');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_integration/integration/status 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key tooShort
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-someValidJsApiKey
     */
    public function testIsJustIntegratedReturnsFalseWhenRestApiKeyIsInvalid()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['store' => $store->getId()]);

        $integrationStatus = $this->objectManager->get(IntegrationStatusInterface::class);
        $result = $integrationStatus->isJustIntegrated();

        $this->assertFalse($result, 'Is Just Integrated');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_integration/integration/status 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-someValidRestApiKey
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-someValidJsApiKey
     */
    public function testReturnsFalseWhenRequestIsForAStoreThatDoesNotExist()
    {
        $this->setUpPhp5();
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['store' => 999999]);

        $integrationStatus = $this->objectManager->get(IntegrationStatusInterface::class);
        $result = $integrationStatus->isJustIntegrated();

        $this->assertFalse($result, 'Is Just Integrated');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoCache all disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_integration/integration/status 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-someValidRestApiKey
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-someValidJsApiKey
     */
    public function testSetJustIntegrated_SetsConfigValue_ForStoresTHatHaveNotBeenIntegrated()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['store' => $store->getId()]);

        $mockConfigWriter = $this->getMockBuilder(ScopeConfigWriter::class)->disableOriginalConstructor()->getMock();
        $mockConfigWriter->expects($this->once())->method('save');

        $integrationStatus = $this->objectManager->create(IntegrationStatusInterface::class, [
            'scopeConfigWriter' => $mockConfigWriter
        ]);
        $integrationStatus->setJustIntegrated($store);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoCache all disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_integration/integration/status 2
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-someValidRestApiKey
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-someValidJsApiKey
     */
    public function testSetJustIntegrated_DoesNotSetAnything_ForPreviouslyIntegratedStores()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');
        $request = $this->objectManager->get(RequestInterface::class);
        $request->setParams(['store' => $store->getId()]);

        $mockConfigWriter = $this->getMockBuilder(ScopeConfigWriter::class)->disableOriginalConstructor()->getMock();
        $mockConfigWriter->expects($this->never())->method('save');

        $integrationStatus = $this->objectManager->create(IntegrationStatusInterface::class, [
            'scopeConfigWriter' => $mockConfigWriter
        ]);
        $integrationStatus->setJustIntegrated($store);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode)
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @return void
     * @todo remove once support for PHP 5.6 is dropped
     */
    private function setUpPhp5()
    {
        if (!$this->objectManager) {
            $this->objectManager = ObjectManager::getInstance();
        }

        /** @var ConfigRegistryInterface $configRegistry */
        $configRegistry = $this->objectManager->get(ConfigRegistryInterface::class);
        $configRegistry->reset();
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }
}
