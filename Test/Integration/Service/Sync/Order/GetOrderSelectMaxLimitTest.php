<?php

namespace Klevu\Search\Test\Integration\Service\Sync\Order;

use Klevu\Search\Api\Service\Sync\GetOrderSelectMaxLimitInterface;
use Klevu\Search\Service\Sync\GetOrderSelectMaxLimit;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetOrderSelectMaxLimitTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();
        $getOrderBatchSizeService = $this->instantiateGetOrderBatchSizeService();

        $this->assertInstanceOf(GetOrderSelectMaxLimitInterface::class, $getOrderBatchSizeService);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testReturnsMaxSizeWhenNotSetInDatabaseConfig()
    {
        $this->setUpPhp5();

        $getOrderSelectMaxLimitService = $this->instantiateGetOrderBatchSizeService();
        $orderSelectMaxLimit = $getOrderSelectMaxLimitService->execute(
            $this->getStore()
        );

        $this->assertSame(GetOrderSelectMaxLimit::MAX_SYNC_SELECT_LIMIT, $orderSelectMaxLimit);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/order_sync/max_sync_select_limit 5
     */
    public function testReturnsMaxSizeForStore()
    {
        $this->setUpPhp5();

        $getOrderSelectMaxLimitService = $this->instantiateGetOrderBatchSizeService();
        $orderSelectMaxLimit = $getOrderSelectMaxLimitService->execute(
            $this->getStore()
        );

        $this->assertSame(5, $orderSelectMaxLimit);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return GetOrderSelectMaxLimitInterface
     */
    private function instantiateGetOrderBatchSizeService()
    {
        return $this->objectManager->create(GetOrderSelectMaxLimit::class, [
            'scopeConfig' => $this->scopeConfig
        ]);
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
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
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