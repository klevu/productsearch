<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Product\Sync\Price;

use Klevu\Search\Model\Product\Product as KlevuProduct;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\TaxRateInterface;
use Magento\Tax\Api\Data\TaxRateSearchResultsInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class SimpleProductTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation disabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/tax/calculation/based_on shipping
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/based_on origin
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/based_on origin
     * @magentoConfigFixture default/shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/country_id GB
     * @magentoConfigFixture default/shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/region_id 0
     * @magentoConfigFixture default/shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/postcode *
     * @magentoConfigFixture default/tax/defaults/country GB
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/country GB
     * @magentoConfigFixture default/tax/defaults/region 0
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/region 0
     * @magentoConfigFixture default/tax/calculation/price_includes_tax 0
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/price_includes_tax 0
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/price_includes_tax 0
     * @magentoConfigFixture default/tax/display/type 3
     * @magentoConfigFixture klevu_test_store_1_store tax/display/type 3
     * @magentoConfigFixture default/tax/display/typeinsearch 1
     * @magentoConfigFixture klevu_test_store_1_store tax/display/typeinsearch 1
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadTaxClassFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function test_CatalogExcludes_DisplayBoth_SearchExcludes()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1', $store->getId());
        $currency = $product->getCurrency();

        $klevuProduct = $this->instantiateKlevuProduct();
        $salePrice = $klevuProduct->getSalePriceData(null, $product, [], $store);
        $startPrice = $klevuProduct->getStartPriceData(null, $product, [], $store);
        $toPrice = $klevuProduct->getToPriceData(null, $product, [], $store);
        $price = $klevuProduct->getPriceData(null, $product, [], $store);
        $groupPrices = $klevuProduct->getGroupPricesData($product, $store);
        $otherPrices = $klevuProduct->getOtherPrices($product, $currency, $store);

        $this->assertSame(10.0, $salePrice, 'Sale Price');
        $this->assertSame(10.0, $startPrice, 'Start Price');
        $this->assertNull($toPrice, 'To Price');
        $this->assertSame(10.0, $price, 'Price');
        foreach ($groupPrices as $groupPrice) {
            $this->assertSame('NOT LOGGED IN', $groupPrice['label'], 'Group Price Label');
            $this->assertSame(11.0, (float)$groupPrice['values'], 'Tier Price Value');
        }
        $this->assertSame('salePrice_-0:11', $otherPrices);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation disabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/tax/calculation/based_on origin
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/based_on origin
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/based_on origin
     * @magentoConfigFixture default/shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/country_id GB
     * @magentoConfigFixture default/shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/region_id 0
     * @magentoConfigFixture default/shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/postcode *
     * @magentoConfigFixture default/tax/defaults/country GB
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/country GB
     * @magentoConfigFixture default/tax/defaults/region 0
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/region 0
     * @magentoConfigFixture default/tax/calculation/price_includes_tax 0
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/price_includes_tax 0
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/price_includes_tax 0
     * @magentoConfigFixture default/tax/display/type 3
     * @magentoConfigFixture klevu_test_store_1_store tax/display/type 3
     * @magentoConfigFixture default/tax/display/typeinsearch 2
     * @magentoConfigFixture klevu_test_store_1_store tax/display/typeinsearch 2
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadTaxClassFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function test_CatalogExcludes_DisplayBoth_SearchIncludes()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1', $store->getId());
        $currency = $product->getCurrency();

        $klevuProduct = $this->instantiateKlevuProduct();
        $salePrice = $klevuProduct->getSalePriceData(null, $product, [], $store);
        $startPrice = $klevuProduct->getStartPriceData(null, $product, [], $store);
        $toPrice = $klevuProduct->getToPriceData(null, $product, [], $store);
        $price = $klevuProduct->getPriceData(null, $product, [], $store);
        $groupPrices = $klevuProduct->getGroupPricesData($product, $store);
        $otherPrices = $klevuProduct->getOtherPrices($product, $currency, $store);

        $this->assertSame(12.0, $salePrice, 'Sale Price');
        $this->assertSame(12.0, $startPrice, 'Start Price');
        $this->assertNull($toPrice, 'To Price');
        $this->assertSame(12.0, $price, 'Price');
        foreach ($groupPrices as $groupPrice) {
            $this->assertSame('NOT LOGGED IN', $groupPrice['label'], 'Tier Price Label');
            $this->assertSame(13.2, (float)$groupPrice['values'], 'Tier Price Value');
        }
        $this->assertSame('salePrice_-0:13.2', $otherPrices);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation disabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/tax/calculation/based_on shipping
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/based_on origin
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/based_on origin
     * @magentoConfigFixture default/shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/country_id GB
     * @magentoConfigFixture default/shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/region_id 0
     * @magentoConfigFixture default/shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/postcode *
     * @magentoConfigFixture default/tax/defaults/country GB
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/country GB
     * @magentoConfigFixture default/tax/defaults/region 0
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/region 0
     * @magentoConfigFixture default/tax/calculation/price_includes_tax 1
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/price_includes_tax 1
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/price_includes_tax 1
     * @magentoConfigFixture default/tax/display/type 3
     * @magentoConfigFixture klevu_test_store_1_store tax/display/type 3
     * @magentoConfigFixture default/tax/display/typeinsearch 1
     * @magentoConfigFixture klevu_test_store_1_store tax/display/typeinsearch 1
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadTaxClassFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function test_CatalogIncludes_DisplayBoth_SearchExcludes()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1', $store->getId());
        $currency = $product->getCurrency();

        $klevuProduct = $this->instantiateKlevuProduct();
        $salePrice = $klevuProduct->getSalePriceData(null, $product, [], $store);
        $startPrice = $klevuProduct->getStartPriceData(null, $product, [], $store);
        $toPrice = $klevuProduct->getToPriceData(null, $product, [], $store);
        $price = $klevuProduct->getPriceData(null, $product, [], $store);
        $groupPrices = $klevuProduct->getGroupPricesData($product, $store);
        $otherPrices = $klevuProduct->getOtherPrices($product, $currency, $store);

        $this->assertSame(8.33, $salePrice, 'Sale Price');
        $this->assertSame(8.33, $startPrice, 'Start Price');
        $this->assertNull($toPrice, 'To Price');
        $this->assertSame(8.33, $price, 'Price');
        foreach ($groupPrices as $groupPrice) {
            $this->assertSame('NOT LOGGED IN', $groupPrice['label'], 'Tier Price Label');
            $this->assertSame(9.17, (float)$groupPrice['values'], 'Tier Price Value');
        }
        $this->assertSame('salePrice_-0:9.17', $otherPrices);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation disabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/tax/calculation/based_on origin
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/based_on origin
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/based_on origin
     * @magentoConfigFixture default/shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/country_id GB
     * @magentoConfigFixture default/shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/region_id 0
     * @magentoConfigFixture default/shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/postcode *
     * @magentoConfigFixture default/tax/defaults/country GB
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/country GB
     * @magentoConfigFixture default/tax/defaults/region 0
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/region 0
     * @magentoConfigFixture default/tax/calculation/price_includes_tax 1
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/price_includes_tax 1
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/price_includes_tax 1
     * @magentoConfigFixture default/tax/display/type 3
     * @magentoConfigFixture klevu_test_store_1_store tax/display/type 3
     * @magentoConfigFixture default/tax/display/typeinsearch 2
     * @magentoConfigFixture klevu_test_store_1_store tax/display/typeinsearch 2
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadTaxClassFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function test_CatalogIncludes_DisplayBoth_SearchIncludes()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1', $store->getId());
        $currency = $product->getCurrency();

        $klevuProduct = $this->instantiateKlevuProduct();
        $salePrice = $klevuProduct->getSalePriceData(null, $product, [], $store);
        $startPrice = $klevuProduct->getStartPriceData(null, $product, [], $store);
        $toPrice = $klevuProduct->getToPriceData(null, $product, [], $store);
        $price = $klevuProduct->getPriceData(null, $product, [], $store);
        $groupPrices = $klevuProduct->getGroupPricesData($product, $store);
        $otherPrices = $klevuProduct->getOtherPrices($product, $currency, $store);

        $this->assertSame(10.0, $salePrice, 'Sale Price');
        $this->assertSame(10.0, $startPrice, 'Start Price');
        $this->assertNull($toPrice, 'To Price');
        $this->assertSame(10.0, $price, 'Price');
        foreach ($groupPrices as $groupPrice) {
            $this->assertSame('NOT LOGGED IN', $groupPrice['label'], 'Group Price Label');
            $this->assertSame(11.0, (float)$groupPrice['values'], 'Group Price Value');
        }
        $this->assertSame('salePrice_-0:11', $otherPrices);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation disabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/tax/calculation/based_on origin
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/based_on origin
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/based_on origin
     * @magentoConfigFixture default/shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/country_id GB
     * @magentoConfigFixture default/shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/region_id 0
     * @magentoConfigFixture default/shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/postcode *
     * @magentoConfigFixture default/tax/defaults/country GB
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/country GB
     * @magentoConfigFixture default/tax/defaults/region 0
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/region 0
     * @magentoConfigFixture default/tax/calculation/price_includes_tax 0
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/price_includes_tax 0
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/price_includes_tax 0
     * @magentoConfigFixture default/tax/display/type 1
     * @magentoConfigFixture klevu_test_store_1_store tax/display/type 1
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadTaxClassFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function test_CatalogExcludes_DisplayExcludes()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1', $store->getId());
        $currency = $product->getCurrency();

        $klevuProduct = $this->instantiateKlevuProduct();
        $salePrice = $klevuProduct->getSalePriceData(null, $product, [], $store);
        $startPrice = $klevuProduct->getStartPriceData(null, $product, [], $store);
        $toPrice = $klevuProduct->getToPriceData(null, $product, [], $store);
        $price = $klevuProduct->getPriceData(null, $product, [], $store);
        $groupPrices = $klevuProduct->getGroupPricesData($product, $store);
        $otherPrices = $klevuProduct->getOtherPrices($product, $currency, $store);

        $this->assertSame(10.0, $salePrice, 'Sale Price');
        $this->assertSame(10.0, $startPrice, 'Start Price');
        $this->assertNull($toPrice, 'To Price');
        $this->assertSame(10.0, $price, 'Price');
        foreach ($groupPrices as $groupPrice) {
            $this->assertSame('NOT LOGGED IN', $groupPrice['label'], 'Group Price Label');
            $this->assertSame(11.0, (float)$groupPrice['values'], 'Tier Price Value');
        }
        $this->assertSame('salePrice_-0:11', $otherPrices);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation disabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/tax/calculation/based_on shipping
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/based_on origin
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/based_on origin
     * @magentoConfigFixture default/shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/country_id GB
     * @magentoConfigFixture default/shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/region_id 0
     * @magentoConfigFixture default/shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/postcode *
     * @magentoConfigFixture default/tax/defaults/country GB
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/country GB
     * @magentoConfigFixture default/tax/defaults/region 0
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/region 0
     * @magentoConfigFixture default/tax/calculation/price_includes_tax 0
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/price_includes_tax 0
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/price_includes_tax 0
     * @magentoConfigFixture default/tax/display/type 2
     * @magentoConfigFixture klevu_test_store_1_store tax/display/type 2
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadTaxClassFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function test_CatalogExcludes_DisplayIncludes()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1', $store->getId());
        $currency = $product->getCurrency();

        $klevuProduct = $this->instantiateKlevuProduct();
        $salePrice = $klevuProduct->getSalePriceData(null, $product, [], $store);
        $startPrice = $klevuProduct->getStartPriceData(null, $product, [], $store);
        $toPrice = $klevuProduct->getToPriceData(null, $product, [], $store);
        $price = $klevuProduct->getPriceData(null, $product, [], $store);
        $groupPrices = $klevuProduct->getGroupPricesData($product, $store);
        $otherPrices = $klevuProduct->getOtherPrices($product, $currency, $store);

        $this->assertSame(12.0, $salePrice, 'Sale Price');
        $this->assertSame(12.0, $startPrice, 'Start Price');
        $this->assertNull($toPrice, 'To Price');
        $this->assertSame(12.0, $price, 'Price');
        foreach ($groupPrices as $groupPrice) {
            $this->assertSame('NOT LOGGED IN', $groupPrice['label'], 'Group Price Label');
            $this->assertSame(13.2, (float)$groupPrice['values'], 'Tier Price Value');
        }
        $this->assertSame('salePrice_-0:13.2', $otherPrices);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation disabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/tax/calculation/based_on origin
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/based_on origin
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/based_on origin
     * @magentoConfigFixture default/shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/country_id GB
     * @magentoConfigFixture default/shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/region_id 0
     * @magentoConfigFixture default/shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/postcode *
     * @magentoConfigFixture default/tax/defaults/country GB
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/country GB
     * @magentoConfigFixture default/tax/defaults/region 0
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/region 0
     * @magentoConfigFixture default/tax/calculation/price_includes_tax 1
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/price_includes_tax 1
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/price_includes_tax 1
     * @magentoConfigFixture default/tax/display/type 1
     * @magentoConfigFixture klevu_test_store_1_store tax/display/type 1
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadTaxClassFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function test_CatalogIncludes_DisplayExcludes()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1', $store->getId());
        $currency = $product->getCurrency();

        $klevuProduct = $this->instantiateKlevuProduct();
        $salePrice = $klevuProduct->getSalePriceData(null, $product, [], $store);
        $startPrice = $klevuProduct->getStartPriceData(null, $product, [], $store);
        $toPrice = $klevuProduct->getToPriceData(null, $product, [], $store);
        $price = $klevuProduct->getPriceData(null, $product, [], $store);
        $groupPrices = $klevuProduct->getGroupPricesData($product, $store);
        $otherPrices = $klevuProduct->getOtherPrices($product, $currency, $store);

        $this->assertSame(8.33, $salePrice, 'Sale Price');
        $this->assertSame(8.33, $startPrice, 'Start Price');
        $this->assertNull($toPrice, 'To Price');
        $this->assertSame(8.33, $price, 'Price');
        foreach ($groupPrices as $groupPrice) {
            $this->assertSame('NOT LOGGED IN', $groupPrice['label'], 'Group Price Label');
            $this->assertSame(9.17, (float)$groupPrice['values'], 'Tier Price Value');
        }
        $this->assertSame('salePrice_-0:9.17', $otherPrices);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation disabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/tax/calculation/based_on shipping
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/based_on origin
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/based_on origin
     * @magentoConfigFixture default/shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/country_id GB
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/country_id GB
     * @magentoConfigFixture default/shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/region_id 0
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/region_id 0
     * @magentoConfigFixture default/shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_website_1_website shipping/origin/postcode *
     * @magentoConfigFixture klevu_test_store_1_store shipping/origin/postcode *
     * @magentoConfigFixture default/tax/defaults/country GB
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/country GB
     * @magentoConfigFixture default/tax/defaults/region 0
     * @magentoConfigFixture klevu_test_store_1_store tax/defaults/region 0
     * @magentoConfigFixture default/tax/calculation/price_includes_tax 1
     * @magentoConfigFixture klevu_test_website_1_website tax/calculation/price_includes_tax 1
     * @magentoConfigFixture klevu_test_store_1_store tax/calculation/price_includes_tax 1
     * @magentoConfigFixture default/tax/display/type 2
     * @magentoConfigFixture klevu_test_store_1_store tax/display/type 2
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadTaxClassFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function test_CatalogIncludes_DisplayIncludes()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1', $store->getId());
        $currency = $product->getCurrency();

        $klevuProduct = $this->instantiateKlevuProduct();
        $salePrice = $klevuProduct->getSalePriceData(null, $product, [], $store);
        $startPrice = $klevuProduct->getStartPriceData(null, $product, [], $store);
        $toPrice = $klevuProduct->getToPriceData(null, $product, [], $store);
        $groupPrices = $klevuProduct->getGroupPricesData($product, $store);
        $otherPrices = $klevuProduct->getOtherPrices($product, $currency, $store);

        $this->assertSame(10.0, $salePrice, 'Sale Price');
        $this->assertSame(10.0, $startPrice, 'Start Price');
        $this->assertNull($toPrice, 'To Price');
        foreach ($groupPrices as $groupPrice) {
            $this->assertSame('NOT LOGGED IN', $groupPrice['label'], 'Group Price Label');
            $this->assertSame(11.0, (float)$groupPrice['values'], 'Tier Price Value');
        }
        $this->assertSame('salePrice_-0:11', $otherPrices);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
    }

    /**
     * @param mixed[] $arguments
     *
     * @return KlevuProduct
     */
    private function instantiateKlevuProduct(array $arguments = [])
    {
        return $this->objectManager->create(
            KlevuProduct::class,
            $arguments
        );
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode)
    {
        $storeRepository = $this->objectManager->create(StoreRepositoryInterface::class);
        $store = $storeRepository->get($storeCode);
        $this->storeManager->setCurrentStore($store);

        return $store;
    }

    /**
     * @param string $sku
     * @param int $storeId
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku, $storeId)
    {
        $taxRate = $this->getProductTaxRate();

        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        /** @var Product $product */
        $product = $productRepository->get($sku, false, $storeId);
        $product->setData('tax_class_id', $taxRate->getId());
        $productRepository->save($product);

        return $product;
    }

    /**
     * @return TaxRateInterface
     * @throws InputException
     */
    private function getProductTaxRate()
    {
        $nameFilter = $this->objectManager->create(Filter::class);
        $nameFilter->setField('class_name');
        $nameFilter->setValue('Taxable Goods');
        $nameFilter->setConditionType('eq');

        $typeFilter = $this->objectManager->create(Filter::class);
        $typeFilter->setField('class_type');
        $typeFilter->setValue('PRODUCT');
        $typeFilter->setConditionType('eq');

        $searchBuilderFactory = $this->objectManager->create(SearchCriteriaBuilderFactory::class);
        $searchBuilder = $searchBuilderFactory->create();
        $searchBuilder->addFilter($nameFilter);
        $searchBuilder->addFilter($typeFilter);
        $searchCriteria = $searchBuilder->create();

        $taxClassRepository = $this->objectManager->create(TaxClassRepositoryInterface::class);
        /** @var TaxRateSearchResultsInterface $taxRateResult */
        $taxRateResult = $taxClassRepository->getList($searchCriteria);
        /** @var TaxRateInterface[] $taxRates */
        $taxRates = $taxRateResult->getItems();
        $keys = array_keys($taxRates);

        return $taxRates[$keys[0]];
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
     * Loads tax creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadTaxClassFixtures()
    {
        include __DIR__ . '/../../../../_files/taxClassFixtures.php';
    }

    /**
     * Rolls back tax creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadTaxClassFixturesRollback()
    {
        include __DIR__ . '/../../../../_files/taxClassFixtures_rollback.php';
    }

    /**
     * Loads store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../../_files/websiteFixtures_rollback.php';
    }
}
