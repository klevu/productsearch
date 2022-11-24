<?php
/** phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps */

namespace Klevu\Search\Test\Integration\Repository\MagentoProductSyncRepository;

use Klevu\Search\Repository\MagentoProductSyncRepository;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductTypeListInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductType;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetChildProductIdsCollectionTest extends TestCase
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
    public function testGetChildProductIdsCollection_ExcludeOos_ExcludeCatalogVisibility()
    {
        $this->setupPhp5();

        // Disabled products should not be included
        // All visibilities should be included (because catalog visibility applies to the parent)
        // Only in stock products with quanity should be included
        $expectedResult = [
            'klevu_synctest_smp_enabled_catalog-search_in-stock-has-qty',
            'klevu_synctest_smp_enabled_catalog_in-stock-has-qty',
            'klevu_synctest_smp_enabled_search_in-stock-has-qty',
            'klevu_synctest_smp_enabled_notvisible_in-stock-has-qty',
        ];
        $actualResult = $this->getProductSkus(MagentoProductSyncRepository::NOT_VISIBLE_EXCLUDED, false);

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
    public function testGetChildProductIdsCollection_ExcludeOos_IncludeCatalogVisibility()
    {
        $this->setupPhp5();

        // Disabled products should not be included
        // All visibilities should be included (because catalog visibility applies to the parent)
        // Only in stock products with quanity should be included
        $expectedResult = [
            'klevu_synctest_smp_enabled_catalog-search_in-stock-has-qty',
            'klevu_synctest_smp_enabled_catalog_in-stock-has-qty',
            'klevu_synctest_smp_enabled_search_in-stock-has-qty',
            'klevu_synctest_smp_enabled_notvisible_in-stock-has-qty',
        ];
        $actualResult = $this->getProductSkus(MagentoProductSyncRepository::NOT_VISIBLE_EXCLUDED, false);

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
    public function testGetChildProductIdsCollection_IncludeOos_ExcludeCatalogVisibility()
    {
        $this->setupPhp5();

        // Disabled products should not be included
        // All visibilities should be included (because catalog visibility applies to the parent)
        // In stock and OOS should be included
        $expectedResult = [
            'klevu_synctest_smp_enabled_catalog-search_in-stock-has-qty',
            'klevu_synctest_smp_enabled_catalog-search_in-stock-no-qty',
            'klevu_synctest_smp_enabled_catalog-search_out-of-stock-has-qty',
            'klevu_synctest_smp_enabled_catalog_in-stock-has-qty',
            'klevu_synctest_smp_enabled_search_in-stock-has-qty',
            'klevu_synctest_smp_enabled_notvisible_in-stock-has-qty',
            'klevu_synctest_smp_enabled_notvisible_in-stock-no-qty',
            'klevu_synctest_smp_enabled_notvisible_out-of-stock-has-qty',
        ];
        $actualResult = $this->getProductSkus(MagentoProductSyncRepository::NOT_VISIBLE_EXCLUDED, true);

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
    public function testGetChildProductIdsCollection_IncludeOos_IncludeCatalogVisibility()
    {
        $this->setupPhp5();

        // Disabled products should not be included
        // All visibilities should be included (because catalog visibility applies to the parent)
        // In stock and OOS should be included
        $expectedResult = [
            'klevu_synctest_smp_enabled_catalog-search_in-stock-has-qty',
            'klevu_synctest_smp_enabled_catalog-search_in-stock-no-qty',
            'klevu_synctest_smp_enabled_catalog-search_out-of-stock-has-qty',
            'klevu_synctest_smp_enabled_catalog_in-stock-has-qty',
            'klevu_synctest_smp_enabled_search_in-stock-has-qty',
            'klevu_synctest_smp_enabled_notvisible_in-stock-has-qty',
            'klevu_synctest_smp_enabled_notvisible_in-stock-no-qty',
            'klevu_synctest_smp_enabled_notvisible_out-of-stock-has-qty',
        ];
        $actualResult = $this->getProductSkus(MagentoProductSyncRepository::NOT_VISIBLE_EXCLUDED, true);

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
    private function getProductSkus($parentVisibility, $includeOosProducts)
    {
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $store = $storeManager->getStore('default');

        $productRepository = $this->objectManager->get(MagentoProductSyncRepository::class);
        /** @var ProductCollection $productCollection */
        $productCollection = $productRepository->getChildProductIdsCollection(
            $store,
            $parentVisibility,
            $includeOosProducts
        );
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
