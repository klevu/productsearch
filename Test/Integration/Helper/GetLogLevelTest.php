<?php

namespace Klevu\Search\Test\Integration\Helper;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetLogLevelTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @magentoAppArea adminhtml
     */
    public function testReturnsDefaultLevelWhenDbValueIsNull()
    {
        $this->setupPhp5();

        $configHelper = $this->instantiateConfigHelper();
        $logLevel = $configHelper->getLogLevel();

        $expected = LoggerConstants::ZEND_LOG_INFO;
        $this->assertSame($expected, $logLevel);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture default/klevu_search/developer/log_level 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/log_level 7
     */
    public function testReturnsStoreDbValueWhenNotNull()
    {
        $this->setupPhp5();

        $store = $this->getStore();
        $configHelper = $this->instantiateConfigHelper();
        $logLevel = $configHelper->getLogLevel($store);

        $expected = LoggerConstants::ZEND_LOG_DEBUG;
        $this->assertSame($expected, $logLevel);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/log_level string
     */
    public function testReturnsDefaultIfConfigValueNotNumeric()
    {
        $this->setupPhp5();

        $store = $this->getStore();
        $configHelper = $this->instantiateConfigHelper();
        $logLevel = $configHelper->getLogLevel($store);

        $expected = LoggerConstants::ZEND_LOG_INFO;
        $this->assertSame($expected, $logLevel);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/log_level 7
     */
    public function testReturnsStoreDbValueWhenCurrentStoreIsSetAndScopeNotPassed()
    {
        $this->setupPhp5();

        $store = $this->getStore();
        $this->request->setParams(['store' => $store->getId()]);

        $configHelper = $this->instantiateConfigHelper();
        $logLevel = $configHelper->getLogLevel();

        $expected = LoggerConstants::ZEND_LOG_DEBUG;
        $this->assertSame($expected, $logLevel);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->request = $this->objectManager->get(RequestInterface::class);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode = 'klevu_test_store_1')
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @return ConfigHelper
     */
    private function instantiateConfigHelper()
    {
        return $this->objectManager->create(ConfigHelper::class, [
            'frameworkAppRequestInterface' => $this->request
        ]);
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../_files/websiteFixtures_rollback.php';
    }
}
