<?php

namespace Klevu\Search\Test\Integration\Observer\Sync\Product;

use Klevu\Search\Api\Data\HistoryInterface;
use Klevu\Search\Api\KlevuProductSyncHistoryRepositoryInterface as SyncHistoryRepository;
use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Service\Sync\Product\DeleteHistory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class RecordSyncHistoryAfterObserverTest extends TestCase
{
    const EVENT_NAME = 'klevu_record_sync_history_after';

    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var SyncHistoryRepository
     */
    private $syncHistoryRepository;
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadSyncHistorySimpleFixtures
     */
    public function testDefaultNumberOfHistoryItemsRemainAfterDelete()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');
        $syncHistoryResult = $this->getSyncHistoryResult($product, $store);

        $this->assertSame(5, $syncHistoryResult->getTotalCount());
        $items = $syncHistoryResult->getItems();
        $keys = array_keys($items);
        $this->assertArrayHasKey($keys[0], $items);
        /** @var HistoryInterface $savedHistory */
        $savedHistory = $items[$keys[0]];

        $historyItem = [
            DeleteHistory::DELETE_PARAM_PRODUCT_ID => $savedHistory->getProductId(),
            DeleteHistory::DELETE_PARAM_PARENT_ID => $savedHistory->getParentId(),
            DeleteHistory::DELETE_PARAM_STORE_ID => $savedHistory->getStoreId()
        ];

        $this->eventManager->dispatch(
            self::EVENT_NAME,
            [
                'historyItems' => [$historyItem]
            ]
        );

        $syncHistoryResult = $this->getSyncHistoryResult($product, $store);
        $this->assertSame(1, $syncHistoryResult->getTotalCount());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadSyncHistorySimpleFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/history_length 2
     */
    public function testCustomNumberOfHistoryItemsRemainAfterDelete()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');
        $syncHistoryResult = $this->getSyncHistoryResult($product, $store);

        $this->assertSame(5, $syncHistoryResult->getTotalCount());
        $items = $syncHistoryResult->getItems();
        $keys = array_keys($items);
        $this->assertArrayHasKey($keys[0], $items);
        /** @var HistoryInterface $savedHistory */
        $savedHistory = $items[$keys[0]];

        $historyItem = [
            DeleteHistory::DELETE_PARAM_PRODUCT_ID => $savedHistory->getProductId(),
            DeleteHistory::DELETE_PARAM_PARENT_ID => $savedHistory->getParentId(),
            DeleteHistory::DELETE_PARAM_STORE_ID => $savedHistory->getStoreId()
        ];

        $this->eventManager->dispatch(
            self::EVENT_NAME,
            [
                'historyItems' => [$historyItem]
            ]
        );

        $syncHistoryResult = $this->getSyncHistoryResult($product, $store);
        $this->assertSame(2, $syncHistoryResult->getTotalCount());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadSyncHistorySimpleFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/history_length 20
     */
    public function testNothingIsDeletedIfNumberOfRecordsIsLessThanTheConfiguredLength()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');
        $syncHistoryResult = $this->getSyncHistoryResult($product, $store);

        $this->assertSame(5, $syncHistoryResult->getTotalCount());
        $items = $syncHistoryResult->getItems();
        $keys = array_keys($items);
        $this->assertArrayHasKey($keys[0], $items);
        /** @var HistoryInterface $savedHistory */
        $savedHistory = $items[$keys[0]];

        $historyItem = [
            DeleteHistory::DELETE_PARAM_PRODUCT_ID => $savedHistory->getProductId(),
            DeleteHistory::DELETE_PARAM_PARENT_ID => $savedHistory->getParentId(),
            DeleteHistory::DELETE_PARAM_STORE_ID => $savedHistory->getStoreId()
        ];

        $this->eventManager->dispatch(
            self::EVENT_NAME,
            [
                'historyItems' => [$historyItem]
            ]
        );

        $syncHistoryResult = $this->getSyncHistoryResult($product, $store);
        $this->assertSame(5, $syncHistoryResult->getTotalCount());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadConfigurableProductFixtures
     * @magentoDataFixture loadSyncHistoryConfigFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/history_length 3
     */
    public function testComplexProductHistoryItemsAreRemoved()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_child_1');
        $parentProduct = $this->getProduct('klevu_configurable_1');
        $syncHistoryResult = $this->getSyncHistoryResult($product, $store, $parentProduct);

        $this->assertSame(5, $syncHistoryResult->getTotalCount());
        $items = $syncHistoryResult->getItems();
        $keys = array_keys($items);
        $this->assertArrayHasKey($keys[0], $items);
        /** @var HistoryInterface $savedHistory */
        $savedHistory = $items[$keys[0]];

        $historyItem = [
            DeleteHistory::DELETE_PARAM_PRODUCT_ID => $savedHistory->getProductId(),
            DeleteHistory::DELETE_PARAM_PARENT_ID => $savedHistory->getParentId(),
            DeleteHistory::DELETE_PARAM_STORE_ID => $savedHistory->getStoreId()
        ];

        $this->eventManager->dispatch(
            self::EVENT_NAME,
            [
                'historyItems' => [$historyItem]
            ]
        );

        $syncHistoryResult = $this->getSyncHistoryResult($product, $store, $parentProduct);
        $this->assertSame(3, $syncHistoryResult->getTotalCount());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadSyncHistorySimpleFixtures
     * @dataProvider requiredFieldDataProvider
     */
    public function testHistoryItemIsNotDeletedIfDataIsMissing($requiredField)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $syncHistoryResult = $this->getSyncHistoryResult($product, $store);
        $this->assertSame(5, $syncHistoryResult->getTotalCount());
        $items = $syncHistoryResult->getItems();
        $keys = array_keys($items);
        $this->assertArrayHasKey($keys[0], $items);
        /** @var HistoryInterface $savedHistory */
        $savedHistory = $items[$keys[0]];

        $historyItem = [];
        if ($requiredField !== DeleteHistory::DELETE_PARAM_PRODUCT_ID) {
            $historyItem[DeleteHistory::DELETE_PARAM_PRODUCT_ID] = $savedHistory->getProductId();
        }
        if ($requiredField !== DeleteHistory::DELETE_PARAM_PARENT_ID) {
            $historyItem[DeleteHistory::DELETE_PARAM_PARENT_ID] = $savedHistory->getParentId();
        }
        if ($requiredField !== DeleteHistory::DELETE_PARAM_STORE_ID) {
            $historyItem[DeleteHistory::DELETE_PARAM_STORE_ID] = $savedHistory->getStoreId();
        }

        $this->eventManager->dispatch(
            self::EVENT_NAME,
            [
                'historyItems' => [$historyItem]
            ]
        );

        $syncHistoryResult = $this->getSyncHistoryResult($product, $store);
        $this->assertSame(5, $syncHistoryResult->getTotalCount());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadSyncHistorySimpleFixtures
     */
    public function testHistoryItemIsNotDeletedIfStoreOesNotExist()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $syncHistoryResult = $this->getSyncHistoryResult($product, $store);
        $this->assertSame(5, $syncHistoryResult->getTotalCount());
        $items = $syncHistoryResult->getItems();
        $keys = array_keys($items);
        $this->assertArrayHasKey($keys[0], $items);
        /** @var HistoryInterface $savedHistory */
        $savedHistory = $items[$keys[0]];
        $historyItem = [];
        $historyItem[DeleteHistory::DELETE_PARAM_PRODUCT_ID] = $savedHistory->getProductId();
        $historyItem[DeleteHistory::DELETE_PARAM_PARENT_ID] = $savedHistory->getParentId();
        $historyItem[DeleteHistory::DELETE_PARAM_STORE_ID] = 9999999999999999;

        $this->eventManager->dispatch(
            self::EVENT_NAME,
            [
                'historyItems' => [$historyItem]
            ]
        );

        $syncHistoryResult = $this->getSyncHistoryResult($product, $store);
        $this->assertSame(5, $syncHistoryResult->getTotalCount());
    }

    /**
     * @return array[]
     */
    public function requiredFieldDataProvider()
    {
        return [
            [DeleteHistory::DELETE_PARAM_PRODUCT_ID],
            [DeleteHistory::DELETE_PARAM_PARENT_ID],
            [DeleteHistory::DELETE_PARAM_STORE_ID],
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
        $this->eventManager = $this->objectManager->get(EventManager::class);
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
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter(
            History::FIELD_PRODUCT_ID,
            $product->getId(),
            'eq'
        );
        $parentId = ($parentProduct && $parentProduct->getId()) ? $parentProduct->getId(): 0;
        $searchCriteriaBuilder->addFilter(
            History::FIELD_PARENT_ID,
            $parentId,
            'eq'
        );
        $searchCriteriaBuilder->addFilter(
            History::FIELD_STORE_ID,
            $store->getId(),
            'eq'
        );
        $searchCriteria = $searchCriteriaBuilder->create();

        return $this->syncHistoryRepository->getList($searchCriteria);
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
     * Loads configurable product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadConfigurableProductFixtures()
    {
        include __DIR__ . '/../../../_files/productFixtures_configurableProduct.php';
    }

    /**
     * Rolls back configurable product  creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadConfigurableProductFixturesRollback()
    {
        include __DIR__ . '/../../../_files/productFixtures_configurableProduct_rollback.php';
    }

    /**
     * Loads sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistorySimpleFixtures()
    {
        include __DIR__ . '/../../../_files/syncHistoryFixtures.php';
    }

    /**
     * Rolls back sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistorySimpleFixturesRollback()
    {
        include __DIR__ . '/../../../_files/syncHistoryFixtures_rollback.php';
    }

    /**
     * Loads sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryConfigFixtures()
    {
        include __DIR__ . '/../../../_files/syncHistoryFixtures_configurableProduct.php';
    }

    /**
     * Rolls back sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryConfigFixturesRollback()
    {
        include __DIR__ . '/../../../_files/syncHistoryFixtures_configurableProduct_rollback.php';
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
