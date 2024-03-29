<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Plugin\CatalogRule\Model\Indexer\IndexBuilder;

use Klevu\Search\Model\Klevu\Klevu as KlevuModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuResourceModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\Collection as ProductSyncCollection;
use Klevu\Search\Plugin\CatalogRule\Model\Indexer\IndexBuilder\FullReindexPlugin;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogRule\Model\Indexer\IndexBuilder;
use Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\ObjectManagerInterface;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Interception\PluginList;
use PHPUnit\Framework\TestCase;

class FullReindexPluginTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var string
     */
    private $pluginName = 'Klevu_Search::CatalogRuleFullReindex';

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
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
            FullReindexPlugin::class,
            $pluginInfo[$this->pluginName]['instance']
        );
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixtureBeforeTransaction loadAttributeFixtures
     * @magentoDataFixtureBeforeTransaction loadCatalogRuleByAttributeFixtures
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testAroundReindexFull_SetsLastSyncedAt_ForAllIds()
    {
        $this->setupPhp5();

        $this->setIndexerToUpdateBySchedule();

        $store = $this->getStore('default');

        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product1 = $this->getProduct('klevu_simple_1'); // in catalog rule
        $product1->setData('klevu_test_attribute', 'test_attribute_value')->save();
        $product1 = $productRepository->save($product1);

        $product2 = $this->getProduct('klevu_simple_2'); // not in catalog rule

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
                'Before aroundReindexFull'
            );
        }

        $this->reindex([
            ProductRuleProcessor::INDEXER_ID,
        ]);

        $productsToSync = $this->getProductsToSync($product1, $store);
        foreach ($productsToSync as $productToSync) {
            $this->assertSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT),
                'After aroundReindexFull In Rule'
            );
        }
        $productsToSync = $this->getProductsToSync($product2, $store);
        foreach ($productsToSync as $productToSync) {
            $this->assertNotSame(
                '0000-00-00 00:00:00',
                $productToSync->getData(KlevuModel::FIELD_LAST_SYNCED_AT),
                'After aroundReindexFull Not in Rule'
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
    private function setIndexerToUpdateBySchedule()
    {
        $indexerRegistry = $this->objectManager->get(IndexerRegistry::class);
        $indexer = $indexerRegistry->get(ProductRuleProcessor::INDEXER_ID);
        if (!$indexer->isScheduled()) {
            $indexer->setScheduled(true);
        }
    }

    /**
     * @param string[] $indexes
     *
     * @return void
     */
    private function reindex(array $indexes)
    {
        $indexerFactory = $this->objectManager->get(IndexerFactory::class);
        foreach ($indexes as $index) {
            $indexer = $indexerFactory->create();
            try {
                $indexer->load($index);
                $indexer->reindexAll();
            } catch (\InvalidArgumentException $e) {
                // Support for older versions of Magento which may not have all indexers
                continue;
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
     * Loads attribute creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadAttributeFixtures()
    {
        include __DIR__ . '/../../../../../Provider/CatalogRule/_files/attributeFixtures.php';
    }

    /**
     * Rolls back attribute creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadAttributeFixturesRollback()
    {
        include __DIR__ . '/../../../../../Provider/CatalogRule/_files/attributeFixtures_rollback.php';
    }

    /**
     * Loads catalog rule creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadCatalogRuleByAttributeFixtures()
    {
        include __DIR__ . '/../../../../../Provider/CatalogRule/_files/ruleByAttributeFixtures.php';
    }

    /**
     * Rolls back catalog rule creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadCatalogRuleByAttributeFixturesRollback()
    {
        include __DIR__ . '/../../../../../Provider/CatalogRule/_files/ruleByAttributeFixtures_rollback.php';
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
