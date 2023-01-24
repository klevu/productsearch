<?php

namespace Klevu\Search\Test\Integration\Service\Sync\Product;

use Klevu\Search\Api\Service\Sync\SyncNowInterface;
use Klevu\Search\Exception\MissingSyncEntityIds;
use Klevu\Search\Exception\SyncRequestFailedException;
use Klevu\Search\Model\Product\KlevuProductActions;
use Klevu\Search\Service\Sync\Product\SyncNow;
use Klevu\Search\Test\Integration\Traits\Klevu\Api\Mock\SessionFailureMock;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class SyncNowTest extends TestCase
{
    use SessionFailureMock;

    /**
     * @var  ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $syncNowService = $this->instantiateSyncNowService();

        $this->assertInstanceOf(SyncNowInterface::class, $syncNowService);
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

        $syncNowService = $this->instantiateSyncNowService();
        $syncNowService->execute(['1-1-1'], 99999999);
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

        $syncNowService = $this->instantiateSyncNowService();
        $syncNowService->execute([], (int)$store->getId());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testThrowsExceptionWhenSyncFails()
    {
        $this->setUpPhp5();
        $this->mockFailedSessionApiCall();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $this->expectException(SyncRequestFailedException::class);
        $this->expectExceptionMessage('Sync request failed. Please check error logs for more details.');

        $syncNowService = $this->instantiateSyncNowService();
        $syncNowService->execute(['0-' . $product->getId() . '-'], (int)$store->getId());
    }

    private function mockFailedSessionApiCall()
    {
        $this->objectManager->addSharedInstance(
            $this->getKlevuProductActionsMockWithSessionFailure(),
            KlevuProductActions::class
        );
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
     * @return SyncNowInterface
     */
    private function instantiateSyncNowService()
    {
        // get concrete class as there is no preference for this. Is injected directly to controller via di.xml
        return $this->objectManager->get(SyncNow::class);
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
     * @param string $sku
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku)
    {
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        return $productRepository->get($sku);
    }

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../../_files/productFixtures.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../../_files/productFixtures_rollback.php';
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
