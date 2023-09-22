<?php

namespace Klevu\Search\Test\Integration\Model\Indexer\Sync;

use Exception;
use InvalidArgumentException;
use Klevu\Search\Model\Indexer\Sync\ProductStockSyncIndexer;
use Klevu\Search\Model\Klevu\Klevu as KlevuModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuResourceModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\Collection as ProductSyncCollection;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogInventory\Model\Adminhtml\Stock\Item as StockItem;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Indexer\StateInterface;
use Magento\Indexer\Model\Indexer;
use Magento\Indexer\Model\Indexer\Collection as IndexerCollection;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ProductSyncStockIndexerTest extends TestCase
{
    const KLEVU_PRODUCT_SYNC_STOCK_CL_TABLE = 'klevu_product_sync_stock_cl';

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
    public function testChangeLogTableIsPopulated_WhenStockUpdated()
    {
        // testing changes in subscription to table cataloginventory_stock_item
        $this->setupPhp5();

        $product = $this->getProduct('klevu_simple_1');
        $extensionAttributes = $product->getExtensionAttributes();
        /** @var StockItem $stockItem */
        $stockItem = $extensionAttributes->getStockItem();
        $stockItem->setData('is_in_stock', 0);

        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $productRepository->save($product);

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
    public function testChangeLogTableIsPopulated_WhenProductIsDisabled()
    {
        // testing changes in subscription to table catalog_product_entity_int
        $this->setupPhp5();

        $product = $this->getProduct('klevu_simple_1');
        $product->setStatus(Status::STATUS_DISABLED);
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $productRepository->save($product);

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
    public function testLastUpdatedTimeIsSetToZero_WhenKlevuStockIndexRuns()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $this->createKlevuProductSyncEntity($store, $product, null);

        $productsToSync = $this->getProductsToSync($product, $store);
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT)
            );
        }
        $this->reindexList([ProductStockSyncIndexer::INDEXER_ID], [$product->getId()]);

        $productsToSync = $this->getProductsToSync($product, $store);
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
    public function testLastUpdatedTimeIsSetToZero_OnFullReindex_OnlyForEntitiesInChangeLog()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product1 = $this->getProduct('klevu_simple_1');
        $product2 = $this->getProduct('klevu_simple_2');

        $extensionAttributes = $product1->getExtensionAttributes();
        /** @var StockItem $stockItem */
        $stockItem = $extensionAttributes->getStockItem();
        $stockItem->setData('is_in_stock', 0);
        $this->productRepository->save($product1);

        $this->createKlevuProductSyncEntity($store, $product1, null);
        $this->createKlevuProductSyncEntity($store, $product2, null);

        $productsToSync = $this->getProductsToSync($product1, $store);
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT),
                'Before Reindex All'
            );
        }

        $this->reindexAll([ProductStockSyncIndexer::INDEXER_ID]);

        $productsToSync = $this->getProductsToSync($product1, $store);
        foreach ($productsToSync as $productToSync) {
            $this->assertSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT),
                'After Reindex All'
            );
        }

        $productsToSync = $this->getProductsToSync($product2, $store);
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

        $subscriptions = [
            'cataloginventory_stock_item',
            'catalog_product_entity_int',
        ];
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
        $table = $connection->getTableName(self::KLEVU_PRODUCT_SYNC_STOCK_CL_TABLE);
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
            $table = $connection->getTableName(self::KLEVU_PRODUCT_SYNC_STOCK_CL_TABLE);
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
            $indexer = $indexerRegistry->get(ProductStockSyncIndexer::INDEXER_ID);
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
            $indexer = $indexerRegistry->get(ProductStockSyncIndexer::INDEXER_ID);
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
                $state = $indexer->getState();
                $state->setStatus(StateInterface::STATUS_VALID);
                $state->save();
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
        $collection->addFieldToFilter(KlevuModel::FIELD_STORE_ID, ['eq' => $store->getId()]);
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
     * @param StoreInterface $store
     *
     * @return KlevuModel[]
     */
    private function getProductsToSync(ProductInterface $product, StoreInterface $store)
    {
        $collection = $this->objectManager->create(ProductSyncCollection::class);
        $collection->addFieldToFilter(
            [
                KlevuModel::FIELD_PRODUCT_ID,
                KlevuModel::FIELD_PARENT_ID
            ],
            [
                ['eq' => $product->getId()],
                ['eq' => $product->getId()]
            ]
        );
        $collection->addFieldToFilter(
            KlevuModel::FIELD_STORE_ID, ['eq' => $store->getId()]
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
