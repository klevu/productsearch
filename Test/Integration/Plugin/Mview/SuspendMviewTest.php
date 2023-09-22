<?php

namespace Klevu\Search\Test\Integration\Plugin\Mview;

use Exception;
use InvalidArgumentException;
use Klevu\Search\Model\Indexer\Sync\ProductStockSyncIndexer;
use Klevu\Search\Model\Indexer\Sync\ProductSyncIndexer;
use Klevu\Search\Model\Klevu\Klevu as KlevuModel;
use Klevu\Search\Model\Klevu\Klevu as KlevuSync;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuResourceModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\Collection as ProductSyncCollection;
use Klevu\Search\Plugin\Mview\View as MviewViewPlugin;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Model\Adminhtml\Stock\Item as StockItem;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Mview\View as MviewView;
use Magento\Framework\Mview\View\Changelog;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\Interception\PluginList;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea global
 * @magentoDbIsolation disabled
 */
class SuspendMviewTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var string
     */
    private $pluginName = 'Klevu_Search::KlevuSyncSetVersionIdForReindexAll';
    /**
     * @var AdapterInterface
     */
    private $connection;
    /**
     * @var ProductRepositoryInterface|mixed
     */
    private $productRepository;

    public function testTheModuleInterceptsCallsToTheFieldInGlobalScope()
    {
        $this->setupPhp5();

        $pluginInfo = $this->getSystemConfigPluginInfo();
        $this->assertArrayHasKey($this->pluginName, $pluginInfo);
        $this->assertSame(
            MviewViewPlugin::class,
            $pluginInfo[$this->pluginName]['instance']
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testSetVersionId_IsCalledOnState_ForKlevuProductSyncIndexer()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $this->createKlevuProductSyncEntity($store, $product, null);

        $indexer = ProductSyncIndexer::INDEXER_ID;
        $this->setIndexesToUpdateOnSave($indexer);
        $this->setIndexesToScheduled($indexer);
        $this->truncateChangeLogTable($indexer);

        $product->setPrice('1234.56');
        $this->productRepository->save($product);

        $view = $this->objectManager->get(MviewView::class);
        $view->load($indexer);

        $changeLog = $view->getChangelog();
        $changeLogVersionId = $changeLog->getVersion();

        $state = $view->getState();
        $stateVersionId = (int)$state->getVersionId();

        $this->assertNotSame($changeLogVersionId, $stateVersionId);

        $view->suspend();

        $updatedStateVersionId = (int)$state->getVersionId();

        $this->assertSame($stateVersionId, $updatedStateVersionId);
        $this->assertNotSame($changeLogVersionId, $updatedStateVersionId);

        $this->rollbackKlevuProductSyncEntity($store);
        $this->truncateChangeLogTable($indexer);
        $this->setIndexesToUpdateOnSave($indexer);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testSetVersionId_IsCalledOnState_ForKlevuProductStockSyncIndexer()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $indexer = ProductStockSyncIndexer::INDEXER_ID;
        $this->setIndexesToUpdateOnSave($indexer);
        $this->setIndexesToScheduled($indexer);
        $this->truncateChangeLogTable($indexer);

        $extensionAttributes = $product->getExtensionAttributes();
        /** @var StockItem $stockItem */
        $stockItem = $extensionAttributes->getStockItem();
        $stockItem->setData('is_in_stock', 0);
        $this->productRepository->save($product);

        $this->createKlevuProductSyncEntity($store, $product, null);

        $view = $this->objectManager->get(MviewView::class);
        $view->load($indexer);

        $changeLog = $view->getChangelog();
        $changeLogVersionId = $changeLog->getVersion();

        $state = $view->getState();
        $stateVersionId = (int)$state->getVersionId();

        $this->assertNotSame($changeLogVersionId, $stateVersionId);

        $view->suspend();

        $updatedStateVersionId = (int)$state->getVersionId();

        $this->assertSame($stateVersionId, $updatedStateVersionId);
        $this->assertNotSame($changeLogVersionId, $updatedStateVersionId);

        $this->rollbackKlevuProductSyncEntity($store);
        $this->truncateChangeLogTable($indexer);
        $this->setIndexesToUpdateOnSave($indexer);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $resourceConnection = $this->objectManager->get(ResourceConnection::class);
        $this->connection = $resourceConnection->getConnection();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * @return array[]
     */
    private function getSystemConfigPluginInfo()
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->get(PluginList::class);

        return $pluginList->get(MviewView::class, []);
    }

    /**
     * @return void
     */
    private function setIndexesToScheduled($indexer)
    {
        try {
            $indexerRegistry = $this->objectManager->get(IndexerRegistry::class);
            $indexer = $indexerRegistry->get($indexer);
            $indexer->setScheduled(true);
        } catch (InvalidArgumentException $exception) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * @param string $indexer
     *
     * @return void
     */
    private function setIndexesToUpdateOnSave($indexer)
    {
        try {
            $indexerRegistry = $this->objectManager->get(IndexerRegistry::class);
            $indexer = $indexerRegistry->get($indexer);
            $indexer->setScheduled(false);
        } catch (InvalidArgumentException $exception) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * @return void
     */
    private function truncateChangeLogTable($indexer)
    {
        $connection = $this->objectManager->get(ResourceConnection::class);
        try {
            $table = $connection->getTableName($indexer) . '_' . Changelog::NAME_SUFFIX;
            $this->connection->delete($table);
        } catch (Exception $e) {
            // table does not exist yet, this is fine
        }
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
        $klevuModel = $this->objectManager->get(KlevuModel::class);
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
        include __DIR__ . '/../../_files/productFixtures.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../_files/productFixtures_rollback.php';
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
