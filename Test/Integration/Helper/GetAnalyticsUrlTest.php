<?php

namespace Klevu\Search\Test\Integration\Helper;

use Klevu\Search\Helper\Api as ApiHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetAnalyticsUrlTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testGetAnalyticsUrlReturnsDefaultWhenNoConfigAvailable()
    {
        $this->setUpPhp5();

        $configHelper = $this->instantiateConfigHelper();
        $hostname = $configHelper->getAnalyticsUrl();

        $this->assertSame(ApiHelper::ENDPOINT_DEFAULT_ANALYTICS_HOSTNAME, $hostname);
    }

    /**
     * @magentoConfigFixture default/klevu_search/general/analytics_url some.url
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/analytics_url analytics.url
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetAnalyticsUrlReturnsStoreValue()
    {
        $this->setUpPhp5();

        $expectedValue = 'analytics.url';
        $store = $this->getStore('klevu_test_store_1');

        $configHelper = $this->instantiateConfigHelper();
        $hostname = $configHelper->getAnalyticsUrl($store->getId());

        $this->assertSame($expectedValue, $hostname);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @return ConfigHelper
     */
    private function instantiateConfigHelper()
    {
        return $this->objectManager->get(ConfigHelper::class);
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
