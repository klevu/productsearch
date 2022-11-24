<?php
/** phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps */

namespace Klevu\Search\Test\Integration\Repository\MagentoProductSyncRepository;

use Klevu\Search\Api\MagentoProductSyncRepositoryInterface;
use Klevu\Search\Repository\MagentoProductSyncRepository;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductTypeListInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductType;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetProductIdsCollectionTest extends TestCase
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
    public function testGetProductIdsCollection_ExcludeOos_ExcludeCatalogVisibility()
    {
        $this->setupPhp5();

        $expectedResult = [];
        if ($this->isProductTypeAvailable('simple')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_smp_enabled_catalog-search_in-stock-has-qty',
                'klevu_synctest_smp_enabled_search_in-stock-has-qty',
            ]);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_virtual_en-y_v-search_s-yes',
                'klevu_synctest_virtual_en-y_v-both_s-yes',
            ]);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            $expectedResult = array_merge($expectedResult, [
                // downloadable products enabled, in stock
                'klevu_synctest_downloadable_en-y_v-search_s-yes',
                'klevu_synctest_downloadable_en-y_v-both_s-yes',
            ]);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_giftcard_en-y_v-search_s-yes',
                'klevu_synctest_giftcard_en-y_v-both_s-yes',
            ]);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            $expectedResult = array_merge($expectedResult, [
                // grouped products enabled
                'klevu_synctest_grouped_en-y_v-both',
                'klevu_synctest_grouped_en-y_v-search',
            ]);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            $expectedResult = array_merge($expectedResult, [
                // bundle products enabled, in stock
                'klevu_synctest_bundle_en-y_v-both-s-yes',
                'klevu_synctest_bundle_en-y_v-search-s-yes',
            ]);
        }

        $actualResult = $this->getProductSkus(
            MagentoProductSyncRepositoryInterface::NOT_VISIBLE_EXCLUDED,
            false
        );

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
    public function testGetProductIdsCollection_ExcludeOos_IncludeCatalogVisibility()
    {
        $this->setupPhp5();

        $expectedResult = [];
        if ($this->isProductTypeAvailable('simple')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_smp_enabled_catalog-search_in-stock-has-qty',
                'klevu_synctest_smp_enabled_catalog_in-stock-has-qty',
                'klevu_synctest_smp_enabled_search_in-stock-has-qty',
            ]);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_virtual_en-y_v-catalog_s-yes',
                'klevu_synctest_virtual_en-y_v-search_s-yes',
                'klevu_synctest_virtual_en-y_v-both_s-yes',
            ]);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_downloadable_en-y_v-catalog_s-yes',
                'klevu_synctest_downloadable_en-y_v-search_s-yes',
                'klevu_synctest_downloadable_en-y_v-both_s-yes',
            ]);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_giftcard_en-y_v-catalog_s-yes',
                'klevu_synctest_giftcard_en-y_v-search_s-yes',
                'klevu_synctest_giftcard_en-y_v-both_s-yes',
            ]);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_grouped_en-y_v-both',
                'klevu_synctest_grouped_en-y_v-catalog',
                'klevu_synctest_grouped_en-y_v-search',
            ]);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_bundle_en-y_v-both-s-yes',
                'klevu_synctest_bundle_en-y_v-search-s-yes',
                'klevu_synctest_bundle_en-y_v-catalog-s-yes',
            ]);
        }

        $actualResult = $this->getProductSkus(
            MagentoProductSyncRepositoryInterface::NOT_VISIBLE_EXCLUDED,
            false
        );

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
    public function testGetProductIdsCollection_IncludeOos_ExcludeCatalogVisibility()
    {
        $this->setupPhp5();

        $expectedResult = [];
        if ($this->isProductTypeAvailable('simple')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_smp_enabled_catalog-search_in-stock-has-qty',
                'klevu_synctest_smp_enabled_catalog-search_in-stock-no-qty',
                'klevu_synctest_smp_enabled_catalog-search_out-of-stock-has-qty',
                'klevu_synctest_smp_enabled_search_in-stock-has-qty',
            ]);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_virtual_en-y_v-search_s-yes',
                'klevu_synctest_virtual_en-y_v-search_s-no',
                'klevu_synctest_virtual_en-y_v-both_s-yes',
                'klevu_synctest_virtual_en-y_v-both_s-no',
            ]);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_downloadable_en-y_v-search_s-yes',
                'klevu_synctest_downloadable_en-y_v-search_s-no',
                'klevu_synctest_downloadable_en-y_v-both_s-yes',
                'klevu_synctest_downloadable_en-y_v-both_s-no',
            ]);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_giftcard_en-y_v-search_s-yes',
                'klevu_synctest_giftcard_en-y_v-both_s-yes',
                'klevu_synctest_giftcard_en-y_v-search_s-no',
                'klevu_synctest_giftcard_en-y_v-both_s-no',
            ]);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_grouped_en-y_v-both',
                'klevu_synctest_grouped_en-y_v-search',
            ]);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_bundle_en-y_v-both-s-yes',
                'klevu_synctest_bundle_en-y_v-both-s-no',
                'klevu_synctest_bundle_en-y_v-search-s-yes',
                'klevu_synctest_bundle_en-y_v-search-s-no',
            ]);
        }

        $actualResult = $this->getProductSkus(
            MagentoProductSyncRepositoryInterface::NOT_VISIBLE_EXCLUDED,
            true
        );

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
    public function testGetProductIdsCollection_IncludeOos_IncludeCatalogVisibility()
    {

        $this->setupPhp5();

        $expectedResult = [];
        if ($this->isProductTypeAvailable('simple')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_smp_enabled_catalog-search_in-stock-has-qty',
                'klevu_synctest_smp_enabled_catalog-search_in-stock-no-qty',
                'klevu_synctest_smp_enabled_catalog-search_out-of-stock-has-qty',
                'klevu_synctest_smp_enabled_catalog_in-stock-has-qty',
                'klevu_synctest_smp_enabled_search_in-stock-has-qty',
            ]);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_virtual_en-y_v-catalog_s-yes',
                'klevu_synctest_virtual_en-y_v-catalog_s-no',
                'klevu_synctest_virtual_en-y_v-search_s-yes',
                'klevu_synctest_virtual_en-y_v-search_s-no',
                'klevu_synctest_virtual_en-y_v-both_s-yes',
                'klevu_synctest_virtual_en-y_v-both_s-no',
            ]);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_downloadable_en-y_v-catalog_s-yes',
                'klevu_synctest_downloadable_en-y_v-search_s-yes',
                'klevu_synctest_downloadable_en-y_v-both_s-yes',
                'klevu_synctest_downloadable_en-y_v-catalog_s-no',
                'klevu_synctest_downloadable_en-y_v-search_s-no',
                'klevu_synctest_downloadable_en-y_v-both_s-no',
            ]);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_giftcard_en-y_v-catalog_s-yes',
                'klevu_synctest_giftcard_en-y_v-search_s-yes',
                'klevu_synctest_giftcard_en-y_v-both_s-yes',
                'klevu_synctest_giftcard_en-y_v-catalog_s-no',
                'klevu_synctest_giftcard_en-y_v-search_s-no',
                'klevu_synctest_giftcard_en-y_v-both_s-no',
            ]);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_grouped_en-y_v-both',
                'klevu_synctest_grouped_en-y_v-catalog',
                'klevu_synctest_grouped_en-y_v-search',
            ]);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_bundle_en-y_v-both-s-yes',
                'klevu_synctest_bundle_en-y_v-search-s-yes',
                'klevu_synctest_bundle_en-y_v-catalog-s-yes',
                'klevu_synctest_bundle_en-y_v-both-s-no',
                'klevu_synctest_bundle_en-y_v-search-s-no',
                'klevu_synctest_bundle_en-y_v-catalog-s-no',
            ]);
        }

        $actualResult = $this->getProductSkus(
            MagentoProductSyncRepositoryInterface::NOT_VISIBLE_EXCLUDED,
            true
        );

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
     * @magentoConfigFixture default_store klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture default_store klevu_search/product_sync/batch_size 500
     */
    public function testGetProductIdsCollection_ExcludeOos_NotVisibleOnly()
    {
        $this->setupPhp5();

        $expectedResult = [];
        if ($this->isProductTypeAvailable('simple')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_smp_enabled_notvisible_in-stock-has-qty',
            ]);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_virtual_en-y_v-none_s-yes',
            ]);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            $expectedResult = array_merge($expectedResult, [
                // downloadable products enabled, in stock
                'klevu_synctest_downloadable_en-y_v-none_s-yes',
            ]);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_giftcard_en-y_v-none_s-yes',
            ]);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            $expectedResult = array_merge($expectedResult, [
                // grouped products enabled
                'klevu_synctest_grouped_en-y_v-none',
            ]);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            $expectedResult = array_merge($expectedResult, [
                // bundle products enabled, in stock
                'klevu_synctest_bundle_en-y_v-none-s-yes',
            ]);
        }

        $actualResult = $this->getProductSkus(
            MagentoProductSyncRepositoryInterface::NOT_VISIBLE_ONLY,
            false
        );

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
    public function testGetProductIdsCollection_IncludeOos_NotVisibleOnly()
    {
        $this->setupPhp5();

        $expectedResult = [];
        if ($this->isProductTypeAvailable('simple')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_smp_enabled_notvisible_in-stock-has-qty',
                'klevu_synctest_smp_enabled_notvisible_in-stock-no-qty',
                'klevu_synctest_smp_enabled_notvisible_out-of-stock-has-qty',
            ]);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_virtual_en-y_v-none_s-yes',
                'klevu_synctest_virtual_en-y_v-none_s-no',
            ]);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_downloadable_en-y_v-none_s-yes',
                'klevu_synctest_downloadable_en-y_v-none_s-no',
            ]);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_giftcard_en-y_v-none_s-yes',
                'klevu_synctest_giftcard_en-y_v-none_s-no',
            ]);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            $expectedResult = array_merge($expectedResult, [
                // grouped products enabled
                'klevu_synctest_grouped_en-y_v-none',
            ]);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            $expectedResult = array_merge($expectedResult, [
                'klevu_synctest_bundle_en-y_v-none-s-yes',
                'klevu_synctest_bundle_en-y_v-none-s-no',
            ]);
        }

        $actualResult = $this->getProductSkus(
            MagentoProductSyncRepositoryInterface::NOT_VISIBLE_ONLY,
            true
        );

        sort($expectedResult);
        sort($actualResult);

        $this->assertSame(
            array_fill_keys($expectedResult, null),
            array_fill_keys($actualResult, null)
        );
    }

    /**
     * @param string $visibility
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getProductSkus($visibility, $includeOosProducts)
    {
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $store = $storeManager->getStore('default');

        /** @var MagentoProductSyncRepository $productRepository */
        $productRepository = $this->objectManager->get(MagentoProductSyncRepository::class);
        $productCollection = $productRepository->getProductIdsCollection($store, $visibility, $includeOosProducts);
        $products = $productCollection->getItems();

        return array_map(function (ProductInterface $product) {
            return $product->getData(Product::SKU);
        }, $products);
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
