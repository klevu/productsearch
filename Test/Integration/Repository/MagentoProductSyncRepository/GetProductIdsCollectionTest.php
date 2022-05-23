<?php

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
     * @magentoConfigFixture default_store klevu_search/product_sync/catalogvisibility 1
     * @magentoConfigFixture default_store klevu_search/product_sync/batch_size 500
     */
    public function testCatalogVisibleProducts_AreReturned_WhenIncludedInAdmin()
    {
        $visibility = MagentoProductSyncRepositoryInterface::NOT_VISIBLE_INCLUDED;
        $productSkus = $this->getProductSkus($visibility);

        if ($this->isProductTypeAvailable('simple')) {
            // simple products enabled, in stock
            $this->assertContains('klevu_synctest_simple_en-y_v-none_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-catalog_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-both_s-yes', $productSkus);
            // simple products enabled, out of stock
            $this->assertContains('klevu_synctest_simple_en-y_v-none_s-no', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-catalog_s-no', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-both_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            // virtual products enabled, in stock
            $this->assertContains('klevu_synctest_virtual_en-y_v-none_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-catalog_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-both_s-yes', $productSkus);
            // virtual products enabled, out of stock
            $this->assertContains('klevu_synctest_virtual_en-y_v-none_s-no', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-catalog_s-no', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-both_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            // downloadable products enabled, in stock
            $this->assertContains('klevu_synctest_downloadable_en-y_v-none_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-catalog_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-both_s-yes', $productSkus);
            // downloadable products enabled, out of stock
            $this->assertContains('klevu_synctest_downloadable_en-y_v-none_s-no', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-catalog_s-no', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-both_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            // giftcard products enabled, in stock
            $this->assertContains('klevu_synctest_giftcard_en-y_v-none_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-catalog_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-both_s-yes', $productSkus);
            // giftcard products enabled, out of stock
            $this->assertContains('klevu_synctest_giftcard_en-y_v-none_s-no', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-catalog_s-no', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-both_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            // grouped products enabled
            $this->assertContains('klevu_synctest_grouped_en-y_v-both', $productSkus);
            $this->assertContains('klevu_synctest_grouped_en-y_v-catalog', $productSkus);
            $this->assertContains('klevu_synctest_grouped_en-y_v-search', $productSkus);
            $this->assertContains('klevu_synctest_grouped_en-y_v-none', $productSkus);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            // bundle products enabled, in stock
            $this->assertContains('klevu_synctest_bundle_en-y_v-both-s-yes', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-search-s-yes', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-catalog-s-yes', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-none-s-yes', $productSkus);
            // bundle products enabled, out of stock
            $this->assertContains('klevu_synctest_bundle_en-y_v-both-s-no', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-search-s-no', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-catalog-s-no', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-none-s-no', $productSkus);
        }
        $this->commonAssertions($productSkus);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/catalogvisibility 0
     */
    public function testCatalogVisibleProducts_AreNotReturned_WhenExcludedInAdmin()
    {
        $visibility = MagentoProductSyncRepositoryInterface::NOT_VISIBLE_INCLUDED;
        $productSkus = $this->getProductSkus($visibility);

        if ($this->isProductTypeAvailable('simple')) {
            // simple products enabled, in stock
            $this->assertContains('klevu_synctest_simple_en-y_v-none_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-both_s-yes', $productSkus);
            // simple products enabled, out of stock
            $this->assertContains('klevu_synctest_simple_en-y_v-none_s-no', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-both_s-no', $productSkus);
            // catalog visible products
            $this->assertNotContains('klevu_synctest_simple_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_simple_en-y_v-catalog_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            // virtual products enabled, in stock
            $this->assertContains('klevu_synctest_virtual_en-y_v-none_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-both_s-yes', $productSkus);
            // virtual products enabled, out of stock
            $this->assertContains('klevu_synctest_virtual_en-y_v-none_s-no', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-both_s-no', $productSkus);
            // catalog visible products
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-catalog_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            // downloadable products enabled, in stock
            $this->assertContains('klevu_synctest_downloadable_en-y_v-none_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-both_s-yes', $productSkus);
            // downloadable products enabled, out of stock
            $this->assertContains('klevu_synctest_downloadable_en-y_v-none_s-no', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-both_s-no', $productSkus);
            // catalog visible products
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-catalog_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            // giftcard products enabled, in stock
            $this->assertContains('klevu_synctest_giftcard_en-y_v-none_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-both_s-yes', $productSkus);
            // giftcard products enabled, out of stock
            $this->assertContains('klevu_synctest_giftcard_en-y_v-none_s-no', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-both_s-no', $productSkus);
            // catalog visible products
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-catalog_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            // grouped products enabled
            $this->assertContains('klevu_synctest_grouped_en-y_v-both', $productSkus);
            $this->assertContains('klevu_synctest_grouped_en-y_v-search', $productSkus);
            $this->assertContains('klevu_synctest_grouped_en-y_v-none', $productSkus);
            // catalog visible products
            $this->assertNotContains('klevu_synctest_grouped_en-y_v-catalog', $productSkus);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            // bundle products enabled, in stock
            $this->assertContains('klevu_synctest_bundle_en-y_v-both-s-yes', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-search-s-yes', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-none-s-yes', $productSkus);
            // bundle products enabled, out of stock
            $this->assertContains('klevu_synctest_bundle_en-y_v-both-s-no', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-search-s-no', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-none-s-no', $productSkus);
            // catalog visible products
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-catalog-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-catalog-s-no', $productSkus);
        }
        $this->commonAssertions($productSkus);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/catalogvisibility 1
     */
    public function testOnlyNoneVisibleProducts_AreReturned_WhenVisibilitySetTo_NotVisibleOnly()
    {
        $visibility = MagentoProductSyncRepositoryInterface::NOT_VISIBLE_ONLY;
        $productSkus = $this->getProductSkus($visibility);

        if ($this->isProductTypeAvailable('simple')) {
            // none visible products
            $this->assertContains('klevu_synctest_simple_en-y_v-none_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-none_s-no', $productSkus);
            // simple products enabled, in stock
            $this->assertNotContains('klevu_synctest_simple_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_simple_en-y_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_simple_en-y_v-both_s-yes', $productSkus);
            // simple products enabled, out of stock
            $this->assertNotContains('klevu_synctest_simple_en-y_v-catalog_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_simple_en-y_v-search_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_simple_en-y_v-both_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            // none visible products
            $this->assertContains('klevu_synctest_virtual_en-y_v-none_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-none_s-no', $productSkus);
            // virtual products enabled, in stock
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-both_s-yes', $productSkus);
            // virtual products enabled, out of stock
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-catalog_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-search_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-both_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            // none visible products
            $this->assertContains('klevu_synctest_downloadable_en-y_v-none_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-none_s-no', $productSkus);
            // downloadable products enabled, in stock
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-both_s-yes', $productSkus);
            // downloadable products enabled, out of stock
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-catalog_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-search_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-both_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            // none visible products
            $this->assertContains('klevu_synctest_giftcard_en-y_v-none_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-none_s-no', $productSkus);
            // giftcard products enabled, in stock
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-both_s-yes', $productSkus);
            // giftcard products enabled, out of stock
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-catalog_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-search_s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-both_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            // none visible products
            $this->assertContains('klevu_synctest_grouped_en-y_v-none', $productSkus);
            // grouped products enabled
            $this->assertNotContains('klevu_synctest_grouped_en-y_v-both', $productSkus);
            $this->assertNotContains('klevu_synctest_grouped_en-y_v-catalog', $productSkus);
            $this->assertNotContains('klevu_synctest_grouped_en-y_v-search', $productSkus);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            // none visible products
            $this->assertContains('klevu_synctest_bundle_en-y_v-none-s-yes', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-none-s-no', $productSkus);
            // bundle products enabled, in stock
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-both-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-search-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-catalog-s-yes', $productSkus);
            // bundle products enabled, out of stock
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-both-s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-search-s-no', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-catalog-s-no', $productSkus);
        }
        $this->commonAssertions($productSkus);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/catalogvisibility 1
     */
    public function testNoneVisibleProducts_AreNotReturned_WhenVisibilitySetTo_ExcludeNotVisible_AndWhenCatalogIncludedInAdmin()
    {
        $visibility = MagentoProductSyncRepositoryInterface::NOT_VISIBLE_EXCLUDED;
        $productSkus = $this->getProductSkus($visibility);

        if ($this->isProductTypeAvailable('simple')) {
            // simple products enabled, in stock
            $this->assertContains('klevu_synctest_simple_en-y_v-catalog_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-both_s-yes', $productSkus);
            // simple products enabled, out of stock
            $this->assertContains('klevu_synctest_simple_en-y_v-catalog_s-no', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-both_s-no', $productSkus);
            // none visible products
            $this->assertNotContains('klevu_synctest_simple_en-y_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_simple_en-y_v-none_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            // virtual products enabled, in stock
            $this->assertContains('klevu_synctest_virtual_en-y_v-catalog_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-both_s-yes', $productSkus);
            // virtual products enabled, out of stock
            $this->assertContains('klevu_synctest_virtual_en-y_v-catalog_s-no', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-both_s-no', $productSkus);
            // none visible products
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-none_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            // downloadable products enabled, in stock
            $this->assertContains('klevu_synctest_downloadable_en-y_v-catalog_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-both_s-yes', $productSkus);
            // downloadable products enabled, out of stock
            $this->assertContains('klevu_synctest_downloadable_en-y_v-catalog_s-no', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-both_s-no', $productSkus);
            // none visible products
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-none_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            // giftcard products enabled, in stock
            $this->assertContains('klevu_synctest_giftcard_en-y_v-catalog_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-both_s-yes', $productSkus);
            // giftcard products enabled, out of stock
            $this->assertContains('klevu_synctest_giftcard_en-y_v-catalog_s-no', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-both_s-no', $productSkus);
            // none visible products
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-none_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            // grouped products enabled
            $this->assertContains('klevu_synctest_grouped_en-y_v-both', $productSkus);
            $this->assertContains('klevu_synctest_grouped_en-y_v-catalog', $productSkus);
            $this->assertContains('klevu_synctest_grouped_en-y_v-search', $productSkus);
            // none visible products
            $this->assertNotContains('klevu_synctest_grouped_en-y_v-none', $productSkus);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            // bundle products enabled, in stock
            $this->assertContains('klevu_synctest_bundle_en-y_v-both-s-yes', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-search-s-yes', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-catalog-s-yes', $productSkus);
            // bundle products enabled, out of stock
            $this->assertContains('klevu_synctest_bundle_en-y_v-both-s-no', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-search-s-no', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-catalog-s-no', $productSkus);
            // none visible products
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-none-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-none-s-no', $productSkus);
        }
        $this->commonAssertions($productSkus);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/catalogvisibility 0
     */
    public function testNoneVisibleAndCatalogProducts_AreNotReturned_WhenVisibilitySetTo_ExcludeNotVisible_AndWhenCatalogExcludedInAdmin()
    {
        $visibility = MagentoProductSyncRepositoryInterface::NOT_VISIBLE_EXCLUDED;
        $productSkus = $this->getProductSkus($visibility);

        if ($this->isProductTypeAvailable('simple')) {
            // simple products enabled, in stock
            $this->assertContains('klevu_synctest_simple_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-both_s-yes', $productSkus);
            // simple products enabled, out of stock
            $this->assertContains('klevu_synctest_simple_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_simple_en-y_v-both_s-no', $productSkus);
            // catalog visible products
            $this->assertNotContains('klevu_synctest_simple_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_simple_en-y_v-catalog_s-no', $productSkus);
            // none visible products
            $this->assertNotContains('klevu_synctest_simple_en-y_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_simple_en-y_v-none_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            // virtual products enabled, in stock
            $this->assertContains('klevu_synctest_virtual_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-both_s-yes', $productSkus);
            // virtual products enabled, out of stock
            $this->assertContains('klevu_synctest_virtual_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_virtual_en-y_v-both_s-no', $productSkus);
            // catalog visible products
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-catalog_s-no', $productSkus);
            // none visible products
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-y_v-none_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            // downloadable products enabled, in stock
            $this->assertContains('klevu_synctest_downloadable_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-both_s-yes', $productSkus);
            // downloadable products enabled, out of stock
            $this->assertContains('klevu_synctest_downloadable_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_downloadable_en-y_v-both_s-no', $productSkus);
            // catalog visible products
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-catalog_s-no', $productSkus);
            // none visible products
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-y_v-none_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            // giftcard products enabled, in stock
            $this->assertContains('klevu_synctest_giftcard_en-y_v-search_s-yes', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-both_s-yes', $productSkus);
            // giftcard products enabled, out of stock
            $this->assertContains('klevu_synctest_giftcard_en-y_v-search_s-no', $productSkus);
            $this->assertContains('klevu_synctest_giftcard_en-y_v-both_s-no', $productSkus);
            // catalog visible products
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-catalog_s-no', $productSkus);
            // none visible products
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-y_v-none_s-no', $productSkus);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            // grouped products enabled
            $this->assertContains('klevu_synctest_grouped_en-y_v-both', $productSkus);
            $this->assertContains('klevu_synctest_grouped_en-y_v-search', $productSkus);
            // catalog visible products
            $this->assertNotContains('klevu_synctest_grouped_en-y_v-catalog', $productSkus);
            // none visible products
            $this->assertNotContains('klevu_synctest_grouped_en-y_v-none', $productSkus);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            // bundle products enabled, in stock
            $this->assertContains('klevu_synctest_bundle_en-y_v-both-s-yes', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-search-s-yes', $productSkus);
            // bundle products enabled, out of stock
            $this->assertContains('klevu_synctest_bundle_en-y_v-both-s-no', $productSkus);
            $this->assertContains('klevu_synctest_bundle_en-y_v-search-s-no', $productSkus);
            // catalog visible products
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-catalog-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-catalog-s-no', $productSkus);
            // none visible products
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-none-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-y_v-none-s-no', $productSkus);
        }
        $this->commonAssertions($productSkus);
    }

    /**
     * @param array $productSkus
     *
     * @return void
     */
    private function commonAssertions(array $productSkus)
    {
        if ($this->isProductTypeAvailable('simple')) {
            // simple products disabled
            $this->assertNotContains('klevu_synctest_simple_en-n_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_simple_en-n_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_simple_en-n_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_simple_en-n_v-both_s-yes', $productSkus);
        }
        if ($this->isProductTypeAvailable('virtual')) {
            // virtual products disabled
            $this->assertNotContains('klevu_synctest_virtual_en-n_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-n_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-n_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_virtual_en-n_v-both_s-yes', $productSkus);
        }
        if ($this->isProductTypeAvailable('downloadable')) {
            // downloadable products disabled
            $this->assertNotContains('klevu_synctest_downloadable_en-n_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-n_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-n_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_downloadable_en-n_v-both_s-yes', $productSkus);
        }
        if ($this->isProductTypeAvailable('giftcard')) {
            // giftcard products disabled
            $this->assertNotContains('klevu_synctest_giftcard_en-n_v-none_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-n_v-catalog_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-n_v-search_s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_giftcard_en-n_v-both_s-yes', $productSkus);
        }
        if ($this->isProductTypeAvailable('grouped')) {
            // grouped products disabled
            $this->assertNotContains('klevu_synctest_grouped_en-n_v-both', $productSkus);
            $this->assertNotContains('klevu_synctest_grouped_en-n_v-catalog', $productSkus);
            $this->assertNotContains('klevu_synctest_grouped_en-n_v-search', $productSkus);
            $this->assertNotContains('klevu_synctest_grouped_en-n_v-none', $productSkus);
        }
        if ($this->isProductTypeAvailable('bundle')) {
            // bundle products disabled
            $this->assertNotContains('klevu_synctest_bundle_en-n_v-both-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-n_v-search-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-n_v-catalog-s-yes', $productSkus);
            $this->assertNotContains('klevu_synctest_bundle_en-n_v-none-s-yes', $productSkus);
        }
        if ($this->isProductTypeAvailable('configurable')) {
            // configurable products enabled
            $this->assertNotContains('klevu_synctest_configurable_1', $productSkus);
            $this->assertNotContains('klevu_synctest_configurable_2', $productSkus);
            $this->assertNotContains('klevu_synctest_configurable_3', $productSkus);
        }
    }

    /**
     * @param string $visibility
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getProductSkus($visibility)
    {
        $this->setupPhp5();

        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $store = $storeManager->getStore('default');

        /** @var MagentoProductSyncRepository $productRepository */
        $productRepository = $this->objectManager->get(MagentoProductSyncRepository::class);
        $productCollection = $productRepository->getProductIdsCollection($store, $visibility);
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
        $availableProductTypes = array_map(function(ProductType $productType) {
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
