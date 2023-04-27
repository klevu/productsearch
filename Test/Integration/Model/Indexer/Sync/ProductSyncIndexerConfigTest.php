<?php

namespace Klevu\Search\Test\Integration\Model\Indexer\Sync;

use Exception;
use InvalidArgumentException;
use Klevu\Search\Model\Indexer\Sync\ProductSyncIndexer;
use Klevu\Search\Model\Klevu\Klevu as KlevuModel;
use Klevu\Search\Model\Klevu\Klevu as KlevuSync;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuResourceModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\Collection as ProductSyncCollection;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as IndexerPriceProcessor;
use Magento\CatalogInventory\Model\Adminhtml\Stock\Item as StockItem;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor as IndexerStockProcessor;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status as StockStatusResource;
use Magento\CatalogInventory\Model\Stock\Status as StockStatus;
use Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Indexer\Model\Indexer;
use Magento\Indexer\Model\Indexer\Collection as IndexerCollection;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ProductSyncIndexerConfigTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var AdapterInterface
     */
    private $connection;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     */
    public function testTriggersExist()
    {
        $this->setupPhp5();

        $this->assertTriggersExist();

        $this->tearDownPhp5();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     */
    public function testTriggersExistAfterFullReindex()
    {
        $this->setupPhp5();

        $indexerIds = $this->getIndexerIds();
        $this->reindexAll($indexerIds);

        $this->assertTriggersExist();

        $this->tearDownPhp5();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testChangeLogTableIsPopulatedWhenPriceUpdated()
    {
        $this->setupPhp5();

        $product = $this->getProduct('klevu_simple_1');
        $product->setPrice('1000');
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $productRepository->save($product);

        $this->reindexList([IndexerPriceProcessor::INDEXER_ID], [$product->getId()]);

        $this->assertTriggersExist();

        $changeLog = $this->getChangeLogData((int)$product->getId());
        $this->assertNotCount(0, $changeLog);

        $this->tearDownPhp5();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testLastUpdatedTimeIsSetToZeroWhenPriceIndexRuns()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $this->createKlevuProductSyncEntity($store, $product, null);

        $productsToSync = $this->getProductsToSync($product);
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT)
            );
        }

        $this->reindexList([IndexerPriceProcessor::INDEXER_ID], [$product->getId()]);
        $this->reindexList([ProductSyncIndexer::INDEXER_ID], [$product->getId()]);

        $productsToSync = $this->getProductsToSync($product);
        foreach ($productsToSync as $productToSync) {
            $this->assertSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT)
            );
        }

        $this->rollbackKlevuProductSyncEntity($store);
        $this->tearDownPhp5();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testLastUpdatedTimeIsSetToZeroWhenPriceIndexRunsForParentProducts()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $parent = $this->getProduct('klevu_simple_1');
        $product = $this->objectManager->get(ProductInterface::class);

        $this->createKlevuProductSyncEntity($store, $product, $parent);

        $productsToSync = $this->getProductsToSync($parent);
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT)
            );
        }

        $this->reindexList([IndexerPriceProcessor::INDEXER_ID], [$parent->getId()]);
        $this->reindexList([ProductSyncIndexer::INDEXER_ID], [$parent->getId()]);

        $productsToSync = $this->getProductsToSync($parent);
        foreach ($productsToSync as $productToSync) {
            $this->assertSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT)
            );
        }

        $this->rollbackKlevuProductSyncEntity($store);
        $this->tearDownPhp5();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testChangeLogTableIsPopulatedWhenStockUpdated()
    {
        $this->setupPhp5();

        $product = $this->getProduct('klevu_simple_1');
        $extensionAttributes = $product->getExtensionAttributes();
        /** @var StockItem $stockItem */
        $stockItem = $extensionAttributes->getStockItem();
        $stockItem->setData('is_in_stock', 0);

        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $productRepository->save($product);

        $this->reindexList([IndexerStockProcessor::INDEXER_ID], [$product->getId()]);

        $this->assertTriggersExist();

        $changeLog = $this->getChangeLogData((int)$product->getId());
        $this->assertNotCount(0, $changeLog);

        $this->tearDownPhp5();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testChangeLogTableIsNotPopulatedWhenStockQtyUpdated()
    {
        $this->setupPhp5();

        $productMetadata = $this->objectManager->get(ProductMetadataInterface::class);
        if (version_compare($productMetadata->getVersion(), '2.4.2', '<')) {
            $this->markTestSkipped(
                'Mview Subscription param ignoredUpdateColumnsBySubscription is not present prior to Magento 2.4.2'
            );
            // \Magento\Framework\Mview\View\Subscription constructor param ignoredUpdateColumnsBySubscription
            // updating the quantity prior to Magento 2.4.2 will trigger a sync for the product
        }
        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');
        $newQuantity = 99;
        $originalStatus = StockStatus::STATUS_IN_STOCK;

        /** @var StockStatusResource $stockStatusResource */
        $stockStatusResource = $this->objectManager->get(StockStatusResource::class);
        $stockStatusResource->saveProductStatus(
            $product->getID(),
            $originalStatus,
            $newQuantity,
            0
        );

        $this->assertTriggersExist();

        $changeLog = $this->getChangeLogData((int)$product->getId());
        $this->assertCount(0, $changeLog);

        $this->tearDownPhp5();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testLastUpdatedTimeIsSetToZeroWhenStockIndexRuns()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $this->createKlevuProductSyncEntity($store, $product, null);

        $productsToSync = $this->getProductsToSync($product);
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT)
            );
        }

        $this->reindexList([IndexerStockProcessor::INDEXER_ID], [$product->getId()]);
        $this->reindexList([ProductSyncIndexer::INDEXER_ID], [$product->getId()]);

        $productsToSync = $this->getProductsToSync($product);
        foreach ($productsToSync as $productToSync) {
            $this->assertSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT)
            );
        }

        $this->rollbackKlevuProductSyncEntity($store);
        $this->tearDownPhp5();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testLastUpdatedTimeIsSetToZeroWhenStockIndexRunsForParentProducts()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $parent = $this->getProduct('klevu_simple_1');
        $product = $this->objectManager->get(ProductInterface::class);

        $this->createKlevuProductSyncEntity($store, $product, $parent);

        $productsToSync = $this->getProductsToSync($parent);
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT)
            );
        }

        $this->reindexList([IndexerStockProcessor::INDEXER_ID], [$parent->getId()]);
        $this->reindexList([ProductSyncIndexer::INDEXER_ID], [$parent->getId()]);

        $productsToSync = $this->getProductsToSync($parent);
        foreach ($productsToSync as $productToSync) {
            $this->assertSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT)
            );
        }

        $this->rollbackKlevuProductSyncEntity($store);
        $this->tearDownPhp5();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadCatalogRuleFixtures
     */
    public function testCatalogPriceRuleIndexer()
    {
        $this->setupPhp5();

        $productMetadata = $this->objectManager->get(ProductMetadataInterface::class);
        if (version_compare($productMetadata->getVersion(), '2.4.5', '<')) {
            // phpcs:ignore Generic.Files.LineLength
            // https://experienceleague.adobe.com/docs/commerce-knowledge-base/kb/support-tools/patches/mdva-43601-triggers-are-removed-from-catalogrule-product-price-table.html?lang=en#issue
            $this->markTestSkipped('Magento core trigger fix (MDVA-43601) is not present prior to Magento 2.4.5');
        }

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $this->createKlevuProductSyncEntity($store, $product, null);

        $productsToSync = $this->getProductsToSync($product);
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT)
            );
        }

        $this->reindexList([RuleProductProcessor::INDEXER_ID], [$product->getId()]);
        $this->reindexList([IndexerPriceProcessor::INDEXER_ID], [$product->getId()]);
        $this->reindexList([ProductSyncIndexer::INDEXER_ID], [$product->getId()]);

        $productsToSync = $this->getProductsToSync($product);
        foreach ($productsToSync as $productToSync) {
            $this->assertSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT)
            );
        }

        $this->rollbackKlevuProductSyncEntity($store);
        $this->tearDownPhp5();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testLastUpdatedTimeIsSetToZeroOnFullReindexOnlyForEntitiesInChangeLog()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product1 = $this->getProduct('klevu_simple_1');
        $product2 = $this->getProduct('klevu_simple_2');

        $product1->setPrice('1234.56');
        $this->productRepository->save($product1);

        $this->createKlevuProductSyncEntity($store, $product1, null);
        $this->createKlevuProductSyncEntity($store, $product2, null);

        $productsToSync = $this->getProductsToSync($product1);
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT),
                'Before Reindex All'
            );
        }

        $this->reindexAll([ProductSyncIndexer::INDEXER_ID]);

        $productsToSync = $this->getProductsToSync($product1);
        foreach ($productsToSync as $productToSync) {
            $this->assertSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT),
                'After Reindex All'
            );
        }

        $productsToSync = $this->getProductsToSync($product2);
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT),
                'Entity not in CL'
            );
        }

        $this->rollbackKlevuProductSyncEntity($store);
        $this->tearDownPhp5();
    }

    /**
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    private function assertTriggersExist()
    {
        $existingTriggerNames = $this->getExistingTriggerNames();

        $subscriptions = ['catalog_product_index_price', 'cataloginventory_stock_status'];
        $triggerActions = ['insert', 'update', 'delete'];
        $found = [];
        foreach ($subscriptions as $subscription) {
            foreach ($triggerActions as $action) {
                $anyPrefix = '(.*_)?';
                $keyRegEx = '/trg_' . $anyPrefix . $subscription . '_after_' . $action . '/';
                foreach ($existingTriggerNames as $existingTriggerName) {
                    if (preg_match($keyRegEx, $existingTriggerName)) {
                        $found[] = $existingTriggerName;
                    }
                }
            }
        }

        $expectedCount = count($subscriptions) * count($triggerActions);
        $this->assertCount($expectedCount, $found, 'Expected Trigger Count');
    }

    /**
     * Alternative setup method to accommodate lack of return type casting in PHP5.6,
     *  given setUp() requires a void return type
     *
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $resourceConnection = $this->objectManager->get(ResourceConnection::class);
        $this->connection = $resourceConnection->getConnection();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->setIndexesToUpdateOnSave();
        $this->setIndexesToScheduled();
        $this->truncateChangeLogTable();
    }

    /**
     * Alternative teardown method to accommodate lack of return type casting in PHP5.6,
     *  given tearDown() requires a void return type
     *
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function tearDownPhp5()
    {
        $this->truncateChangeLogTable();
        $this->setIndexesToUpdateOnSave();
    }

    /**
     * Returns list of all existing database trigger names in database
     *
     * @return string[]
     * @throws \Zend_Db_Statement_Exception
     */
    private function getExistingTriggerNames()
    {
        $triggersResult = $this->connection->query('SHOW TRIGGERS');

        return array_column($triggersResult->fetchAll(), 'Trigger');
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    private function getChangeLogData($productId)
    {
        $connection = $this->objectManager->get(ResourceConnection::class);
        $table = $connection->getTableName('klevu_product_sync_cl');
        $select = $this->connection->select();
        $select->from($table);
        $select->where('entity_id', ['eq' => $productId]);

        return $this->connection->fetchAll($select);
    }

    /**
     * @return void
     */
    private function truncateChangeLogTable()
    {
        $connection = $this->objectManager->get(ResourceConnection::class);
        try {
            $table = $connection->getTableName('klevu_product_sync_cl');
            $this->connection->delete($table);
        } catch (Exception $e) {
            // table does not exist yet, this is fine
        }
    }

    /**
     * @return void
     */
    private function setIndexesToScheduled()
    {
        try {
            $indexerRegistry = $this->objectManager->get(IndexerRegistry::class);
            $indexer = $indexerRegistry->get(ProductSyncIndexer::INDEXER_ID);
            $indexer->setScheduled(true);
        } catch (InvalidArgumentException $exception) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * @return void
     */
    private function setIndexesToUpdateOnSave()
    {
        try {
            $indexerRegistry = $this->objectManager->get(IndexerRegistry::class);
            $indexer = $indexerRegistry->get(ProductSyncIndexer::INDEXER_ID);
            $indexer->setScheduled(false);
        } catch (InvalidArgumentException $exception) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * @param string[] $indexerIds
     * @param int[] $entityIds
     *
     * @return void
     */
    private function reindexList(array $indexerIds, array $entityIds)
    {
        $indexerFactory = $this->objectManager->get(IndexerFactory::class);
        try {
            foreach ($indexerIds as $indexerId) {
                /** @var Indexer $indexer */
                $indexer = $indexerFactory->create();
                $indexer->load($indexerId);
                $indexer->reindexList($entityIds);
            }
        } catch (Exception $exception) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * @param string[] $indexerIds
     *
     * @return void
     */
    private function reindexAll(array $indexerIds)
    {
        $indexerFactory = $this->objectManager->get(IndexerFactory::class);
        try {
            foreach ($indexerIds as $indexerId) {
                $indexer = $indexerFactory->create();
                $indexer->load($indexerId);
                $indexer->reindexAll();
            }
        } catch (Exception $exception) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * @return string[]
     */
    private function getIndexerIds()
    {
        $indexerCollectionFactory = $this->objectManager->get(CollectionFactory::class);
        /** @var IndexerCollection */
        $indexerCollection = $indexerCollectionFactory->create();

        return $indexerCollection->getAllIds();
    }

    /**
     * @param StoreInterface $store
     * @param ProductInterface $product
     * @param ProductInterface|null $parent
     *
     * @return void
     * @throws Exception
     */
    private function createKlevuProductSyncEntity($store, $product, $parent = null)
    {
        $klevuModel = $this->objectManager->create(KlevuModel::class);
        $klevuModel->setData(KlevuModel::FIELD_STORE_ID, $store->getId());
        $klevuModel->setData(KlevuModel::FIELD_PRODUCT_ID, $product->getId());
        $klevuModel->setData(KlevuModel::FIELD_PARENT_ID, $parent ? $parent->getId() : null);
        $klevuModel->setData(KlevuModel::FIELD_LAST_SYNCED_AT, date('Y-m-d h:i:s'));
        $klevuModel->setData(KlevuModel::FIELD_TYPE, $klevuModel::OBJECT_TYPE_PRODUCT);
        $klevuModel->save();
    }

    /**
     * @param StoreInterface $store
     *
     * @return void
     */
    private function rollbackKlevuProductSyncEntity($store)
    {
        $resourceModel = $this->objectManager->get(KlevuResourceModel::class);
        $collection = $this->objectManager->get(ProductSyncCollection::class);
        $collection->addFieldToFilter(KlevuSync::FIELD_STORE_ID, ['eq' => $store->getId()]);
        $items = $collection->getItems();
        foreach ($items as $item) {
            try {
                $resourceModel->delete($item);
            } catch (Exception $e) {
                // this is fine
            }
        }
    }

    /**
     * @param ProductInterface $product
     *
     * @return KlevuModel[]
     */
    private function getProductsToSync(ProductInterface $product)
    {
        $collection = $this->objectManager->create(ProductSyncCollection::class);
        $collection->addFieldToFilter(
            [
                KlevuSync::FIELD_PRODUCT_ID,
                KlevuSync::FIELD_PARENT_ID
            ],
            [
                ['eq' => $product->getId()],
                ['eq' => $product->getId()]
            ]
        );

        return $collection->getItems();
    }

    /**
     * @param string $sku
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku)
    {
        return $this->productRepository->get($sku);
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
        $storeRepository = $this->objectManager->create(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * Loads catalog rule creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadCatalogRuleFixtures()
    {
        include __DIR__ . '/_files/catalogRuleFixtures.php';
    }

    /**
     * Rolls back catalog rule creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadCatalogRuleFixturesRollback()
    {
        include __DIR__ . '/_files/catalogRuleFixtures_rollback.php';
    }

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/_files/productFixtures.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/_files/productFixtures_rollback.php';
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
