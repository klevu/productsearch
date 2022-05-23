<?php

namespace Klevu\Search\Test\Integration\Model\Klevu;

use Klevu\Search\Model\Klevu\Klevu as KlevuSync;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuSyncResourceModel;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class OrmTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testCanLoadAndSave()
    {
        $this->setupPhp5();

        $sync = $this->createKlevuSyncEntity();
        $syncToLoad = $this->instantiateSync();
        $this->instantiateSyncResourceModel()->load($syncToLoad, $sync->getId());

        $this->assertSame((int)$sync->getId(), (int)$syncToLoad->getId());
        $this->assertSame((int)$sync->getProductId(), (int)$syncToLoad->getProductId());
        $this->assertSame($sync->getType(), $syncToLoad->getType());
    }

    public function testCanLoadMultipleItemsToSync()
    {
        $this->setupPhp5();

        $itemA = $this->createKlevuSyncEntity();
        $itemB = $this->createKlevuSyncEntity();
        $collection = $this->instantiateSyncCollection();

        $this->assertContains((int)$itemA->getId(), array_keys($collection->getItems()));
        $this->assertContains((int)$itemB->getId(), array_keys($collection->getItems()));
    }

    public function testInitCollectionByType()
    {
        $this->setupPhp5();

        $store = $this->getStore();

        $collection = $this->getInstantiatedCollection();

        $this->assertInstanceOf(KlevuSyncResourceModel\Collection::class, $collection);

        $columnsPart = $collection->getSelect()->getPart(Select::COLUMNS);
        $columns = array_merge([], ...array_filter($columnsPart));
        $this->assertContains(KlevuSync::FIELD_PRODUCT_ID, $columns);
        $this->assertContains(KlevuSync::FIELD_PARENT_ID, $columns);

        $where = $collection->getSelect()->getPart(Select::WHERE);
        $this->assertContains("(`" . KlevuSync::FIELD_TYPE . "` = '" . KlevuSync::OBJECT_TYPE_PRODUCT ."')", $where);
        $this->assertContains("AND (`" . KlevuSync::FIELD_STORE_ID . "` = '" . $store->getId() ."')", $where);
    }

    public function testFilterProductsToUpdateJoinsProductData()
    {
        $this->setupPhp5();

        $initiatedCollection = $this->getInstantiatedCollection();
        $filteredCollection = $initiatedCollection->filterProductsToUpdate();

        $this->assertInstanceOf(KlevuSyncResourceModel\Collection::class, $filteredCollection);

        $join = $filteredCollection->getSelect()->getPart(Select::FROM);
        $innerJoin = array_filter($join, function($table) {
           return $table['joinType'] === Select::INNER_JOIN;
        });

        $this->assertSame('catalog_product_entity', $innerJoin[array_keys($innerJoin)[0]]['tableName']);
    }

    public function testGetBatchDataForCollectionReturnsArray()
    {
        $this->setupPhp5();

        $itemA = $this->createKlevuSyncEntity();
        $itemB = $this->createKlevuSyncEntity();

        $collection = $this->getInstantiatedCollection();
        $syncResourceModel = $this->instantiateSyncResourceModel();
        $batchedData = $syncResourceModel->getBatchDataForCollection($collection, $this->getStore());

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($batchedData);
        } else {
            $this->assertTrue(is_array($batchedData), 'Is Array');
        }
        $this->assertCount(2, $batchedData);
        $batched = array_map(function($item) {
            return $item[KlevuSync::FIELD_ENTITY_ID];
        }, $batchedData);

        $this->assertContains($itemA->getId(), $batched);
        $this->assertContains($itemB->getId(), $batched);
    }

    /**
     * @return KlevuSync
     * @throws AlreadyExistsException
     */
    private function createKlevuSyncEntity()
    {
        $sync = $this->instantiateSync();
        $sync->setProductId(mt_rand(1, 4999999));
        $sync->setParentId(mt_rand(5000000, 9999999));
        $sync->setType(KlevuSync::OBJECT_TYPE_PRODUCT);
        $sync->setStoreId(Store::DISTRO_STORE_ID);
        $sync->setErrorFlag(0);

        $this->instantiateSyncResourceModel()->save($sync);

        return $sync;
    }

    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @return KlevuSync
     */
    private function instantiateSync()
    {
        return $this->objectManager->create(KlevuSync::class);
    }

    /**
     * @return KlevuSyncResourceModel
     */
    private function instantiateSyncResourceModel()
    {
        return $this->objectManager->create(KlevuSyncResourceModel::class);
    }

    /**
     * @return KlevuSyncResourceModel\Collection
     */
    private function instantiateSyncCollection()
    {
        return $this->objectManager->create(KlevuSyncResourceModel\Collection::class);

    }

    /**
     * @return KlevuSyncResourceModel\Collection
     * @throws \Magento\Framework\Exception\InvalidArgumentException
     */
    private function getInstantiatedCollection()
    {
        $store = $this->getStore();

        $type = KlevuSync::OBJECT_TYPE_PRODUCT;

        return $this->instantiateSyncCollection()->initCollectionByType($store, $type);
    }

    /**
     * @return StoreInterface
     */
    private function getStore()
    {
        $store = $this->objectManager->create(StoreInterface::class);
        $store->setId(Store::DISTRO_STORE_ID);

        return $store;
    }

}
