<?php

namespace Klevu\Search\Test\Integration\Model\Product\Sync;

use Klevu\Search\Model\Product\LoadAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ObjectMethodStoreDataCacheTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_website_1_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_website_1_store_2_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     */
    public function testDataIsNotCachedBetweenStores()
    {
        $this->setUpPhp5();

        $storeManager = $this->objectManager->create(StoreManagerInterface::class);

        $store1 = $this->getStore('klevu_test_website_1_store_1');
        $store2 = $this->getStore('klevu_test_website_1_store_2');

        $product1 = $this->getProduct('klevu_simple_1', $store1);
        $product2 = $this->getProduct('klevu_simple_1', $store2);

        $products1 = [
            ['product_id' => $product1->getId(), 'parent_id' => '0']
        ];

        $storeManager->setCurrentStore($store1);

        $loadAttributes = $this->objectManager->create(LoadAttributeInterface::class);
        $loadAttributes->addProductSyncData($products1);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($products1);
        } else {
            $this->assertTrue(is_array($products1), 'Is Array');
        }
        $keys = array_keys($products1);
        $data1 = $products1[$keys[0]];
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($data1);
        } else {
            $this->assertTrue(is_array($data1), 'Is Array');
        }
        $this->assertArrayHasKey('desc', $data1);
        $this->assertSame('Description ' . $store1->getName(), $data1['desc']);
        $this->assertArrayHasKey('name', $data1);
        $this->assertSame('Name ' . $store1->getName(), $data1['name']);
        $this->assertArrayHasKey('shortDesc', $data1);
        $this->assertSame('Short Description ' . $store1->getName(), $data1['shortDesc']);

        $products2 = [
            ['product_id' => $product2->getId(), 'parent_id' => '0']
        ];

        $storeManager->setCurrentStore($store2);

        $loadAttributes = $this->objectManager->create(LoadAttributeInterface::class);
        $loadAttributes->addProductSyncData($products2);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($products2);
        } else {
            $this->assertTrue(is_array($products2), 'Is Array');
        }
        $keys = array_keys($products2);
        $data2 = $products2[$keys[0]];
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($data2);
        } else {
            $this->assertTrue(is_array($data2), 'Is Array');
        }
        $this->assertArrayHasKey('desc', $data2);
        $this->assertSame('Description ' . $store2->getName(), $data2['desc']);
        $this->assertArrayHasKey('name', $data2);
        $this->assertSame('Name ' . $store2->getName(), $data2['name']);
        $this->assertArrayHasKey('shortDesc', $data2);
        $this->assertSame('Short Description ' . $store2->getName(), $data2['shortDesc']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_website_1_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_website_1_store_2_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadConfigurableProductAttributesFixtures
     * @magentoDataFixture loadConfigurableProductFixtures
     */
    public function testDataIsNotCachedBetweenStoresForParentProducts()
    {
        $this->setUpPhp5();

        $storeManager = $this->objectManager->create(StoreManagerInterface::class);

        $store1 = $this->getStore('klevu_test_website_1_store_1');
        $store2 = $this->getStore('klevu_test_website_1_store_2');

        $childProduct1 = $this->getProduct('klevu_simple_child_1', $store1);
        $parentProduct1 = $this->getProduct('klevu_configurable_1', $store1);

        $childProduct2 = $this->getProduct('klevu_simple_child_1', $store2);
        $parentProduct2 = $this->getProduct('klevu_configurable_1', $store2);

        $products1 = [
            ['product_id' => $childProduct1->getId(), 'parent_id' => $parentProduct1->getId()]
        ];

        $storeManager->setCurrentStore($store1);

        $loadAttributes = $this->objectManager->create(LoadAttributeInterface::class);
        $loadAttributes->addProductSyncData($products1);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($products1);
        } else {
            $this->assertTrue(is_array($products1), 'Is Array');
        }
        $keys = array_keys($products1);
        $data1 = $products1[$keys[0]];
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($data1);
        } else {
            $this->assertTrue(is_array($data1), 'Is Array');
        }
        $this->assertArrayHasKey('desc', $data1);
        $this->assertSame('Simple Child Description ' . $store1->getName(), $data1['desc']);
        $this->assertArrayHasKey('name', $data1);
        $this->assertSame('Configurable Name ' . $store1->getName(), $data1['name']);
        $this->assertArrayHasKey('shortDesc', $data1);
        $this->assertSame('Simple Child Short Description ' . $store1->getName(), $data1['shortDesc']);
        $this->assertArrayHasKey('url', $data1);

        $products2 = [
            ['product_id' => $childProduct2->getId(), 'parent_id' => $parentProduct2->getId()]
        ];

        $storeManager->setCurrentStore($store2);

        $loadAttributes = $this->objectManager->create(LoadAttributeInterface::class);
        $loadAttributes->addProductSyncData($products2);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($products2);
        } else {
            $this->assertTrue(is_array($products2), 'Is Array');
        }
        $keys = array_keys($products2);
        $data2 = $products2[$keys[0]];
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($data2);
        } else {
            $this->assertTrue(is_array($data2), 'Is Array');
        }
        $this->assertArrayHasKey('desc', $data2);
        $this->assertSame('Simple Child Description ' . $store2->getName(), $data2['desc']);
        $this->assertArrayHasKey('name', $data2);
        $this->assertSame('Configurable Name ' . $store2->getName(), $data2['name']);
        $this->assertArrayHasKey('shortDesc', $data2);
        $this->assertSame('Simple Child Short Description ' . $store2->getName(), $data2['shortDesc']);
        $this->assertArrayHasKey('url', $data2);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @param string $sku
     * @param StoreInterface $store
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku, StoreInterface $store)
    {
        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);

        return $productRepository->get($sku,false, $store->getId());
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
     * Loads simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSimpleProductFixtures()
    {
        include __DIR__ . '/_files/multi_store_per_website/productFixtures.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSimpleProductFixturesRollback()
    {
        include __DIR__ . '/_files/multi_store_per_website/productFixtures_rollback.php';
    }

    /**
     * Loads configurable product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadConfigurableProductFixtures()
    {
        include __DIR__ . '/_files/multi_store_per_website/productConfigurableFixtures.php';
    }

    /**
     * Rolls back configurable product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadConfigurableProductFixturesRollback()
    {
        include __DIR__ . '/_files/multi_store_per_website/productConfigurableFixtures_rollback.php';
    }

    /**
     * Loads attribute creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadConfigurableProductAttributesFixtures()
    {
        include __DIR__ . '/_files/multi_store_per_website/productAttributeFixtures.php';
    }

    /**
     * Rolls back attribute creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadConfigurableProductAttributesFixturesRollback()
    {
        include __DIR__ . '/_files/multi_store_per_website/productAttributeFixtures_rollback.php';
    }

    /**
     * Loads store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/_files/multi_store_per_website/websiteFixtures.php';
    }

    /**
     * Rolls back store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/_files/multi_store_per_website/websiteFixtures_rollback.php';
    }
}
