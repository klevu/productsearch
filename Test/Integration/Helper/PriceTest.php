<?php

namespace Klevu\Search\Test\Integration\Helper;

use Klevu\Search\Helper\Price as PriceHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class PriceTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * Scenario: Grouped products containing themselves as a child product cause an infinite loop
     *           when calculating original price value
     *    Given: A grouped product has been created
     *      and: A simple product has been assigned as a child
     *      and: The parent grouped product has been assigned as a child
     *      and: Show Out Stock products config setting is enabled
     *      amd: The simple product is in stock
     *     When: Original price calculations are performed on the parent product
     *     Then: It should return an accurate value
     *      and: Not encounter a script timeout
     *
     * @magentoAppArea frontend
     * @magentoAppIsolation disabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default_store cataloginventory/options/show_out_of_stock 1
     * @magentoDataFixture loadProductFixtures_GroupedRecursive
     * @small
     */
    public function testGetGroupProductOriginalPrice_RecursiveGroupedProduct_DisplayOutOfStock()
    {
        $this->setupPhp5();

        /** @var PriceHelper $priceHelper */
        $priceHelper = $this->objectManager->get(PriceHelper::class);

        /** @var Product $product */
        $product = $this->productRepository->get('klevu_grouped_recursive_test');
        $this->assertNull($product->getPrice());

        $origMaxExecutionTime = ini_get('max_execution_time');
        ini_set('max_execution_time', 10);

        // This method will set the updated price on the passed product directly
        $priceHelper->getGroupProductOriginalPrice(
            $product,
            $this->storeManager->getDefaultStoreView()
        );

        ini_set('max_execution_time', $origMaxExecutionTime);

        $this->assertSame(10.0, $product->getPrice());
    }

    /**
     * Scenario: Grouped products containing themselves as a child product cause an infinite loop
     *           when calculating original price value
     *    Given: A grouped product has been created
     *      and: A simple product has been assigned as a child
     *      and: The parent grouped product has been assigned as a child
     *      and: Show Out Stock products config setting is enabled
     *      amd: The simple product is in stock
     *     When: Original price calculations are performed on the parent product
     *     Then: It should return an accurate value
     *      and: Not encounter a script timeout
     *
     * @magentoAppArea frontend
     * @magentoAppIsolation disabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default_store cataloginventory/options/show_out_of_stock 0
     * @magentoDataFixture loadProductFixtures_GroupedRecursive
     * @small
     */
    public function testGetGroupProductOriginalPrice_RecursiveGroupedProduct_NotDisplayOutOfStock()
    {
        $this->setupPhp5();

        /** @var PriceHelper $priceHelper */
        $priceHelper = $this->objectManager->get(PriceHelper::class);

        /** @var Product $product */
        $product = $this->productRepository->get('klevu_grouped_recursive_test');

        $origMaxExecutionTime = ini_get('max_execution_time');
        ini_set('max_execution_time', 10);

        // This method will set the updated price on the passed product directly
        $priceHelper->getGroupProductOriginalPrice(
            $product,
            $this->storeManager->getDefaultStoreView()
        );

        ini_set('max_execution_time', $origMaxExecutionTime);

        $this->assertSame(10.0, $product->getPrice());
    }

    /**
     * Scenario: Grouped products containing themselves as a child product cause an infinite loop
     *           when calculating min price value
     *    Given: A grouped product has been created
     *      and: A simple product has been assigned as a child
     *      and: The parent grouped product has been assigned as a child
     *      and: Show Out Stock products config setting is enabled
     *      amd: The simple product is in stock
     *     When: Minimum price calculations are performed on the parent product
     *     Then: It should return an accurate value
     *      and: Not encounter a script timeout
     *
     * @magentoAppArea frontend
     * @magentoAppIsolation disabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default_store cataloginventory/options/show_out_of_stock 1
     * @magentoDataFixture loadProductFixtures_GroupedRecursive
     * @small
     */
    public function testGetGroupProductMinPrice_RecursiveGroupedProduct_DisplayOutOfStock()
    {
        $this->setupPhp5();

        /** @var PriceHelper $priceHelper */
        $priceHelper = $this->objectManager->get(PriceHelper::class);

        $origMaxExecutionTime = ini_get('max_execution_time');
        ini_set('max_execution_time', 10);

        $actualResult = $priceHelper->getGroupProductMinPrice(
            $this->productRepository->get('klevu_grouped_recursive_test'),
            $this->storeManager->getDefaultStoreView()
        );

        ini_set('max_execution_time', $origMaxExecutionTime);

        $this->assertSame(4.99, $actualResult);
    }

    /**
     * Scenario: Grouped products containing themselves as a child product cause an infinite loop
     *           when calculating min price value
     *    Given: A grouped product has been created
     *      and: A simple product has been assigned as a child
     *      and: The parent grouped product has been assigned as a child
     *      and: Show Out Stock products config setting is disabled
     *      amd: The simple product is in stock
     *     When: Minimum price calculations are performed on the parent product
     *     Then: It should return an accurate value
     *      and: Not encounter a script timeout
     *
     * @magentoAppArea frontend
     * @magentoAppIsolation disabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default_store cataloginventory/options/show_out_of_stock 0
     * @magentoDataFixture loadProductFixtures_GroupedRecursive
     * @small
     */
    public function testGetGroupProductMinPrice_RecursiveGroupedProduct_NotDisplayOutOfStock()
    {
        $this->setupPhp5();

        /** @var PriceHelper $priceHelper */
        $priceHelper = $this->objectManager->get(PriceHelper::class);

        $origMaxExecutionTime = ini_get('max_execution_time');
        ini_set('max_execution_time', 10);

        $actualResult = $priceHelper->getGroupProductMinPrice(
            $this->productRepository->get('klevu_grouped_recursive_test'),
            $this->storeManager->getDefaultStoreView()
        );

        ini_set('max_execution_time', $origMaxExecutionTime);

        $this->assertSame(4.99, $actualResult);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures_GroupedRecursive()
    {
        require __DIR__ . '/../_files/productFixtures_groupedRecursive.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures_GroupedRecursiveRollback()
    {
        require __DIR__ . '/../_files/productFixtures_groupedRecursive_rollback.php';
    }
}
