<?php

namespace Klevu\Search\Test\Integration\Service\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Data\HistoryInterface;
use Klevu\Search\Api\KlevuProductSyncHistoryRepositoryInterface as SyncHistoryRepository;
use Klevu\Search\Api\Service\Sync\Product\DeleteHistoryInterface;
use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Service\Sync\Product\DeleteHistory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class DeleteHistoryTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var SyncHistoryRepository
     */
    private $syncHistoryRepository;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $deleteHistory = $this->instantiateDeleteHistory();

        $this->assertInstanceOf(DeleteHistory::class, $deleteHistory);
    }

    /**
     * @dataProvider invalidRecordDataProvider
     */
    public function testEventNotDispatchedWhenInvalidDataProvided($record)
    {
        $this->setUpPhp5();

        $this->expectException(InvalidArgumentException::class);

        $recordHistory = $this->instantiateDeleteHistory();
        $recordHistory->execute([$record]);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadSyncHistoryFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/history_length 1
     */
    public function testEventIsDispatchedAfterSuccessfulSave()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_simple_1');
        $store = $this->getStore('klevu_test_store_1');

        $syncHistoryResult = $this->getSyncHistoryResult($product, $store);

        $this->assertSame(5, $syncHistoryResult->getTotalCount());
        $items = $syncHistoryResult->getItems();
        $keys = array_keys($items);
        $this->assertArrayHasKey($keys[0], $items);
        /** @var HistoryInterface $item */
        $item = $items[$keys[0]];

        $record = [];
        $record[DeleteHistory::DELETE_PARAM_PRODUCT_ID] = $item->getProductId();
        $record[DeleteHistory::DELETE_PARAM_PARENT_ID] = $item->getParentId();
        $record[DeleteHistory::DELETE_PARAM_STORE_ID] = $item->getStoreId();

        $recordHistory = $this->instantiateDeleteHistory();
        $recordHistory->execute([$record]);

        $syncHistoryResult = $this->getSyncHistoryResult($product, $store);
        $this->assertSame(1, $syncHistoryResult->getTotalCount());
    }

    /**
     * @return array
     */
    public function invalidRecordDataProvider()
    {
        return [
            [
                [
                    DeleteHistory::DELETE_PARAM_PRODUCT_ID => 1,
                    DeleteHistory::DELETE_PARAM_PARENT_ID => 2
                ]
            ],
            [
                [
                    DeleteHistory::DELETE_PARAM_PRODUCT_ID => 1,
                    DeleteHistory::DELETE_PARAM_STORE_ID => 2
                ]
            ],
            [
                [
                    DeleteHistory::DELETE_PARAM_STORE_ID => 1,
                    DeleteHistory::DELETE_PARAM_PARENT_ID => 2
                ]
            ]
        ];
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->syncHistoryRepository = $this->objectManager->get(SyncHistoryRepository::class);
    }

    /**
     * @return DeleteHistoryInterface
     */
    private function instantiateDeleteHistory()
    {
        return $this->objectManager->get(DeleteHistoryInterface::class);
    }

    /**
     * @param ProductInterface $product
     * @param StoreInterface $store
     * @param ProductInterface|null $parentProduct
     *
     * @return SearchResultsInterface
     */
    private function getSyncHistoryResult(ProductInterface $product, StoreInterface $store, $parentProduct = null)
    {
        $parentId = $parentProduct ? $parentProduct->getId(): 0;

        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter(History::FIELD_PRODUCT_ID, $product->getId(), 'eq');
        $searchCriteriaBuilder->addFilter(History::FIELD_PARENT_ID, $parentId, 'eq');
        $searchCriteriaBuilder->addFilter(History::FIELD_STORE_ID, $store->getId(), 'eq');
        $searchCriteria = $searchCriteriaBuilder->create();

        $result = $this->syncHistoryRepository->getList($searchCriteria);
        $this->assertInstanceOf(SearchResultsInterface::class, $result);

        return $result;
    }

    /**
     * @param string $sku
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku)
    {
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        return $productRepository->get($sku);
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

    /**
     * Loads simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSimpleProductFixtures()
    {
        include __DIR__ . '/../../../_files/productFixtures.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSimpleProductFixturesRollback()
    {
        include __DIR__ . '/../../../_files/productFixtures_rollback.php';
    }

    /**
     * Loads sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryFixtures()
    {
        include __DIR__ . '/../../../_files/syncHistoryFixtures.php';
    }

    /**
     * Rolls back sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryFixturesRollback()
    {
        include __DIR__ . '/../../../_files/syncHistoryFixtures_rollback.php';
    }
}
