<?php

namespace Klevu\Search\Test\Integration\Service\Sync\Product;

use Klevu\Search\Api\Service\Sync\ScheduleSyncInterface;
use Klevu\Search\Exception\MissingSyncEntityIds;
use Klevu\Search\Service\Sync\Product\Schedule;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ScheduleTest extends TestCase
{
    /**
     * @var  ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $syncNowService = $this->instantiateSyncScheduleService();

        $this->assertInstanceOf(ScheduleSyncInterface::class, $syncNowService);
    }

    public function testThrowsExceptionIfStoreDoesNotExist()
    {
        $this->setUpPhp5();

        $this->expectException(NoSuchEntityException::class);
        $productMetaData = $this->objectManager->get(ProductMetadataInterface::class);
        if (version_compare($productMetaData->getVersion(), 2.3) < 0) {
            $this->expectExceptionMessage('Requested store is not found');
        } else {
            $this->expectExceptionMessage('The store that was requested wasn\'t found. Verify the store and try again.');
        }

        $syncScheduleService = $this->instantiateSyncScheduleService();
        $syncScheduleService->execute(['1-1-1'], 99999999);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testThrowsExceptionIfNoIdsProvided()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $this->expectException(MissingSyncEntityIds::class);
        $this->expectExceptionMessage('No entity IDs provided for product sync');

        $syncScheduleService = $this->instantiateSyncScheduleService();
        $syncScheduleService->execute([], (int)$store->getId());
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @return ScheduleSyncInterface
     */
    private function instantiateSyncScheduleService()
    {
        // get concrete class as there is no preference for this. Is injected directly to controller via di.xml
        return $this->objectManager->get(Schedule::class);
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
