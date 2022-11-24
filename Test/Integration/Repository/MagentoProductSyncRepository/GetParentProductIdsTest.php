<?php
/** phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps */

namespace Klevu\Search\Test\Integration\Repository\MagentoProductSyncRepository;

use Klevu\Search\Repository\MagentoProductSyncRepository;
use Magento\Catalog\Api\ProductTypeListInterface;
use Magento\Catalog\Model\ProductType;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetParentProductIdsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default_store klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture default_store klevu_search/product_sync/batch_size 500
     */
    public function testGetParentProductIds_ExcludeOos_ExcludeCatalogVisibility()
    {
        $this->setupPhp5();

        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $store = $storeManager->getStore('default');

        // Disabled products should not be included
        // Catalog-Search and Search visibility because we disable catalogvisibility
        // Only in stock products should be included
        $expectedResult = [
            'klevu_synctest_conf_enabled_catalog-search_in-stock-has-qty',
            'klevu_synctest_conf_enabled_search_in-stock-has-qty',
        ];

        /** @var MagentoProductSyncRepository $productRepository */
        $productRepository = $this->objectManager->get(MagentoProductSyncRepository::class);
        $parentIds = $productRepository->getParentProductIds($store, false);
        $actualResult = $this->getAllProductSkusByEntityIds($parentIds);

        sort($expectedResult);
        sort($actualResult);

        $this->assertSame(
            array_fill_keys($expectedResult, null),
            array_fill_keys($actualResult, null)
        );
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default_store klevu_search/product_sync/catalogvisibility 1
     * @magentoConfigFixture default_store klevu_search/product_sync/batch_size 500
     */
    public function testGetParentProductIds_ExcludeOos_IncludeCatalogVisibility()
    {
        $this->setupPhp5();

        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $store = $storeManager->getStore('default');

        // Disabled products should not be included
        // Catalog-Search, Catalog, and Search visibility because we enable catalogvisibility
        // Only in stock products should be included
        $expectedResult = [
            'klevu_synctest_conf_enabled_catalog-search_in-stock-has-qty',
            'klevu_synctest_conf_enabled_catalog_in-stock-has-qty',
            'klevu_synctest_conf_enabled_search_in-stock-has-qty',
        ];

        /** @var MagentoProductSyncRepository $productRepository */
        $productRepository = $this->objectManager->get(MagentoProductSyncRepository::class);
        $parentIds = $productRepository->getParentProductIds($store, false);
        $actualResult = $this->getAllProductSkusByEntityIds($parentIds);

        sort($expectedResult);
        sort($actualResult);

        $this->assertSame(
            array_fill_keys($expectedResult, null),
            array_fill_keys($actualResult, null)
        );
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default_store klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture default_store klevu_search/product_sync/batch_size 500
     */
    public function testGetParentProductIds_IncludeOos_ExcludeCatalogVisibility()
    {
        $this->setupPhp5();

        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $store = $storeManager->getStore('default');

        // Disabled products should not be included
        // Catalog-Search and Search visibility because we disnable catalogvisibility
        // In stock and OOS products should be included
        $expectedResult = [
            'klevu_synctest_conf_enabled_catalog-search_in-stock-has-qty',
            'klevu_synctest_conf_enabled_search_in-stock-has-qty',
            'klevu_synctest_conf_enabled_catalog-search_out-of-stock-has-qty',
        ];

        /** @var MagentoProductSyncRepository $productRepository */
        $productRepository = $this->objectManager->get(MagentoProductSyncRepository::class);
        $parentIds = $productRepository->getParentProductIds($store, true);
        $actualResult = $this->getAllProductSkusByEntityIds($parentIds);

        sort($expectedResult);
        sort($actualResult);

        $this->assertSame(
            array_fill_keys($expectedResult, null),
            array_fill_keys($actualResult, null)
        );
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default_store klevu_search/product_sync/catalogvisibility 1
     * @magentoConfigFixture default_store klevu_search/product_sync/batch_size 500
     */
    public function testGetParentProductIds_IncludeOos_IncludeCatalogVisibility()
    {
        $this->setupPhp5();

        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $store = $storeManager->getStore('default');

        // Disabled products should not be included
        // Catalog-Search and Search visibility because we disnable catalogvisibility
        // In stock and OOS products should be included
        $expectedResult = [
            'klevu_synctest_conf_enabled_catalog-search_in-stock-has-qty',
            'klevu_synctest_conf_enabled_catalog_in-stock-has-qty',
            'klevu_synctest_conf_enabled_search_in-stock-has-qty',
            'klevu_synctest_conf_enabled_catalog-search_out-of-stock-has-qty',
        ];

        /** @var MagentoProductSyncRepository $productRepository */
        $productRepository = $this->objectManager->get(MagentoProductSyncRepository::class);
        $parentIds = $productRepository->getParentProductIds($store, true);
        $actualResult = $this->getAllProductSkusByEntityIds($parentIds);

        sort($expectedResult);
        sort($actualResult);

        $this->assertSame(
            array_fill_keys($expectedResult, null),
            array_fill_keys($actualResult, null)
        );
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/catalogvisibility 1
     * @magentoConfigFixture default_store klevu_search/product_sync/batch_size 500
     */
//    public function testAllEnabledConfigurableProducts_AreReturned()
//    {
//        $this->setupPhp5();
//
//        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
//        $store = $storeManager->getStore('default');
//
//        $productRepository = $this->objectManager->get(MagentoProductSyncRepository::class);
//        /** @var ProductCollection $productCollection */
//        $parentIds = $productRepository->getParentProductIds($store);
//        $skus = $this->getAllProductSkusByEntityIds($parentIds);
//
//        if ($this->isProductTypeAvailable('configurable')) {
//            // configurable products enabled, in stock
//            $this->assertContains('klevu_synctest_configurable_en-y_v-both_s-yes_chd-yes', $skus);
//            $this->assertContains('klevu_synctest_configurable_en-y_v-search_s-yes_chd-yes', $skus);
//            $this->assertContains('klevu_synctest_configurable_en-y_v-catalog_s-yes_chd-yes', $skus);
//            $this->assertContains('klevu_synctest_configurable_en-y_v-none_s-yes_chd-yes', $skus);
//
//            // configurable products enabled, out of stock
//            $this->assertContains('klevu_synctest_configurable_en-y_v-both_s-no_chd-yes', $skus);
//            $this->assertContains('klevu_synctest_configurable_en-y_v-search_s-no_chd-yes', $skus);
//            $this->assertContains('klevu_synctest_configurable_en-y_v-catalog_s-no_chd-yes', $skus);
//            $this->assertContains('klevu_synctest_configurable_en-y_v-none_s-no_chd-yes', $skus);
//        }
//        $this->commonAssertions($skus);
//    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture default_store klevu_search/product_sync/batch_size 500
     */
//    public function testEnabledConfigurableProducts_AreReturned_ExceptCatalogVisible_WhenDisabledInAdmin()
//    {
//        $this->setupPhp5();
//
//        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
//        $store = $storeManager->getStore('default');
//
//        $productRepository = $this->objectManager->get(MagentoProductSyncRepository::class);
//        /** @var ProductCollection $productCollection */
//        $parentIds = $productRepository->getParentProductIds($store);
//        $skus = $this->getAllProductSkusByEntityIds($parentIds);
//
//        if ($this->isProductTypeAvailable('configurable')) {
//            // configurable products enabled, in stock
//            $this->assertContains('klevu_synctest_configurable_en-y_v-both_s-yes_chd-yes', $skus);
//            $this->assertContains('klevu_synctest_configurable_en-y_v-search_s-yes_chd-yes', $skus);
//            $this->assertContains('klevu_synctest_configurable_en-y_v-none_s-yes_chd-yes', $skus);
//
//            // configurable products enabled, out of stock
//            $this->assertContains('klevu_synctest_configurable_en-y_v-both_s-no_chd-yes', $skus);
//            $this->assertContains('klevu_synctest_configurable_en-y_v-search_s-no_chd-yes', $skus);
//            $this->assertContains('klevu_synctest_configurable_en-y_v-none_s-no_chd-yes', $skus);
//
//            // configurable products enabled, visibility catalog
//            $this->assertNotContains('klevu_synctest_configurable_en-y_v-catalog_s-yes_chd-yes', $skus);
//            $this->assertNotContains('klevu_synctest_configurable_en-y_v-catalog_s-no_chd-yes', $skus);
//        }
//        $this->commonAssertions($skus);
//    }

    private function commonAssertions(array $productSkus)
    {
        if ($this->isProductTypeAvailable('configurable')) {
            // configurable products disabled
            $this->assertNotContains('klevu_synctest_configurable_en-n_v-both_s-yes_chd-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_configurable_en-n_v-search_s-yes_chd-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_configurable_en-n_v-catalog_s-yes_chd-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_configurable_en-n_v-none_s-yes_chd-yes', $productSkus);
        }
        if ($this->isProductTypeAvailable('simple')) {
            // simple products enabled, in stock
            $this->assertNotContains('klevu_synctest_smp_en-y_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_smp_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_smp_en-y_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_smp_en-y_v-both_s-yes', $productSkus);
            // simple products enabled, out of stock
            $this->assertNotContains('klevu_synctest_smp_en-y_v-none_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_smp_en-y_v-catalog_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_smp_en-y_v-search_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_smp_en-y_v-both_s-no', $productSkus);
            // simple products disabled
            $this->assertNotContains('klevu_synctest_smp_en-n_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_smp_en-n_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_smp_en-n_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_smp_en-n_v-both_s-yes', $productSkus);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            // virtual products enabled, in stock
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-both_s-yes', $productSkus);
            // virtual products enabled, out of stock
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-none_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-catalog_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-search_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-both_s-no', $productSkus);
            // virtual products disabled
            $this->assertNotContains('klevu_synctest_virtual_en-n_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-n_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-n_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-n_v-both_s-yes', $productSkus);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            // downloadable products enabled, in stock
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-both_s-yes', $productSkus);
            // downloadable products enabled, out of stock
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-none_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-catalog_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-search_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-both_s-no', $productSkus);
            // downloadable products disabled
            $this->assertNotContains('klevu_synctest_downloadable_en-n_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-n_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-n_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-n_v-both_s-yes', $productSkus);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            // giftcard products enabled, in stock
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-both_s-yes', $productSkus);
            // giftcard products enabled, out of stock
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-none_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-catalog_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-search_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-both_s-no', $productSkus);
            // giftcard products disabled
            $this->assertNotContains('klevu_synctest_giftcard_en-n_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-n_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-n_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-n_v-both_s-yes', $productSkus);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            // grouped products enabled
            $this->assertNotContains('klevu_synctest_grouped_en-y_v-both', $productSkus);
            $this->assertNotContains('klevu_synctest_grouped_en-y_v-catalog', $productSkus);
            $this->assertNotContains('klevu_synctest_grouped_en-y_v-search', $productSkus);
            $this->assertNotContains('klevu_synctest_grouped_en-y_v-none', $productSkus);
            // grouped products disabled
            $this->assertNotContains('klevu_synctest_grouped_en-n_v-both', $productSkus);
            $this->assertNotContains('klevu_synctest_grouped_en-n_v-catalog', $productSkus);
            $this->assertNotContains('klevu_synctest_grouped_en-n_v-search', $productSkus);
            $this->assertNotContains('klevu_synctest_grouped_en-n_v-none', $productSkus);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            // bundle products enabled, in stock
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-both-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-search-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-catalog-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-none-s-yes', $productSkus);
            // bundle products enabled, out of stock
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-both-s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-search-s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-catalog-s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-none-s-no', $productSkus);
            // bundle products disabled
            $this->assertNotContains('klevu_synctest_bundle_en-n_v-both-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-n_v-search-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-n_v-catalog-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-n_v-none-s-yes', $productSkus);
        }
    }

    /**
     * @param $entityIds
     *
     * @return array
     */
    private function getAllProductSkusByEntityIds($entityIds = [])
    {
        if (!$entityIds) {
            return [];
        }
        $resource = $this->objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('catalog_product_entity');

        $sql = sprintf(
            "SELECT `sku` FROM %s WHERE `entity_id` in (%s)",
            $tableName,
            implode(',', $entityIds)
        );

        return $connection->fetchCol($sql);
    }

    /**
     * @param string $productType
     *
     * @return bool
     */
    private function isProductTypeAvailable($productType)
    {
        $productTypeList = $this->objectManager->create(ProductTypeListInterface::class);
        $availableProductTypes = array_map(function (ProductType $productType) {
            return $productType->getName();
        }, $productTypeList->getProductTypes());

        return in_array($productType, $availableProductTypes, true);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Loads product collection creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        require __DIR__ . '/../_files/productFixtures.php';
    }

    /**
     * Rolls back order creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        require __DIR__ . '/../_files/productFixtures_rollback.php';
    }
}
