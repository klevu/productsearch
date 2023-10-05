<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Test\Integration\Plugin\CatalogRule\Model\Indexer\IndexBuilder;

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

use Klevu\Search\Model\Klevu\Klevu as KlevuModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuResourceModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\Collection as ProductSyncCollection;
use Klevu\Search\Plugin\CatalogRule\Model\Indexer\IndexBuilder\ReindexByIdsPlugin;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\CatalogRule\Model\Indexer\IndexBuilder;
use Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Interception\PluginList;
use PHPUnit\Framework\TestCase;

class ReindexByIdsPluginTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var string
     */
    private $pluginName = 'Klevu_Search::CatalogRuleReindexByIds';
    /**
     * @var ProductRepositoryInterface|null
     */
    private $productRepository = null;

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * @magentoAppArea global
     */
    public function testTheModuleDoesNotInterceptsCallsToTheFieldInGlobalScope()
    {
        $this->setupPhp5();

        $pluginInfo = $this->getSystemConfigPluginInfo();
        $this->assertArrayHasKey($this->pluginName, $pluginInfo);
        $this->assertSame(
            ReindexByIdsPlugin::class,
            $pluginInfo[$this->pluginName]['instance']
        );
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testAfterReindexByIds_SetsLastSyncedAt_ForSuppliedIds_UpdateOnSave()
    {
        $this->setupPhp5();

        $this->setIndexerToUpdateOnSave();

        $store = $this->getStore('klevu_test_store_1');
        $product1 = $this->getProduct('klevu_simple_1');
        $product2 = $this->getProduct('klevu_simple_2');
        $product3 = $this->getProduct('klevu_configurable_1');

        $this->createKlevuProductSyncEntity($store, $product1, $product3);
        $this->createKlevuProductSyncEntity($store, $product2, $product3);

        $productsToSync = $this->getProductsToSync($product3, $store);
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT),
                'Before afterReindexByIds'
            );
        }

        $product3->setName($product3->getName() . ' ');
        $productResourceModel = $this->objectManager->get(ProductResourceModel::class);
        $productResourceModel->save($product3);

        $productsToSync = $this->getProductsToSync($product3, $store);
        foreach ($productsToSync as $productToSync) {
            $this->assertSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT),
                'After afterReindexByIds'
            );
        }

        $this->rollbackKlevuProductSyncEntity($store);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testAfterReindexById_SetsLastSyncedAt_ForSuppliedId_UpdateOnSave()
    {
        $this->setupPhp5();

        $this->setIndexerToUpdateOnSave();

        $store = $this->getStore('klevu_test_store_1');
        $product1 = $this->getProduct('klevu_simple_1');
        $product2 = $this->getProduct('klevu_simple_2');

        $this->createKlevuProductSyncEntity($store, $product1, null);
        $this->createKlevuProductSyncEntity($store, $product2, null);

        $productsToSync = array_merge(
            $this->getProductsToSync($product1, $store),
            $this->getProductsToSync($product2, $store)
        );
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT),
                'Before afterReindexById'
            );
        }

        $product1->setName($product1->getName() . ' ');
        $productResourceModel = $this->objectManager->get(ProductResourceModel::class);
        $productResourceModel->save($product1);

        $productsToSync = $this->getProductsToSync($product1, $store);
        foreach ($productsToSync as $productToSync) {
            $this->assertSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT),
                'After afterReindexById'
            );
        }
        $productsToSync = $this->getProductsToSync($product2, $store);
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT),
                'After afterReindexById'
            );
        }

        $this->rollbackKlevuProductSyncEntity($store);
    }

    /**
     * @return array[]
     */
    private function getSystemConfigPluginInfo()
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->get(PluginList::class);

        return $pluginList->get(IndexBuilder::class, []);
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
            KlevuModel::FIELD_STORE_ID,
            ['eq' => $store->getId()]
        );

        return $collection->getItems();
    }

    /**
     * @param StoreInterface $store
     * @param ProductInterface $product
     * @param ProductInterface|null $parent
     *
     * @return void
     * @throws \Exception
     */
    private function createKlevuProductSyncEntity(StoreInterface $store, ProductInterface $product, $parent = null)
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
    private function rollbackKlevuProductSyncEntity(StoreInterface $store)
    {
        $resourceModel = $this->objectManager->get(KlevuResourceModel::class);
        $collection = $this->objectManager->get(ProductSyncCollection::class);
        $collection->addFieldToFilter(KlevuModel::FIELD_STORE_ID, ['eq' => $store->getId()]);
        $items = $collection->getItems();
        foreach ($items as $item) {
            try {
                $resourceModel->delete($item);
            } catch (\Exception $e) {
                // this is fine
            }
        }
    }

    /**
     * @return void
     */
    private function setIndexerToUpdateOnSave()
    {
        $indexerRegistry = $this->objectManager->get(IndexerRegistry::class);
        $indexer = $indexerRegistry->get(ProductRuleProcessor::INDEXER_ID);
        if ($indexer->isScheduled()) {
            $indexer->setScheduled(false);
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
        include __DIR__ . '/../../../../../Model/Indexer/Sync/_files/productFixtures.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../../../../Model/Indexer/Sync/_files/productFixtures_rollback.php';
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/websiteFixtures_rollback.php';
    }
}
