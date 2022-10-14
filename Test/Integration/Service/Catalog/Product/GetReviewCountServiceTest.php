<?php

namespace Klevu\Search\Test\Integration\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\GetReviewCountInterface;
use Klevu\Search\Model\Product\LoadAttribute;
use Klevu\Search\Service\Catalog\Product\GetReviewCount;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetReviewCountServiceTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $getRatingsCountService = $this->instantiateGetReviewCountService();

        $this->assertInstanceOf(GetReviewCountInterface::class, $getRatingsCountService);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testProductWithOneReviewReturnsCountOfOne()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $product = $this->getProduct('klevu_simple_1');

        $getReviewCountService = $this->instantiateGetReviewCountService();
        $ratingCount = $getReviewCountService->execute([$product]);

        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($ratingCount);
        } else {
            $this->assertTrue(is_int($ratingCount), 'Is Int');
        }
        $expectedRatingCount = 1;
        $this->assertSame($expectedRatingCount, $ratingCount);

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testOnlyCountsApprovedReviews()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $product = $this->getProduct('klevu_configurable_1');

        $getReviewCountService = $this->instantiateGetReviewCountService();
        $ratingCount = $getReviewCountService->execute([$product]);

        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($ratingCount);
        } else {
            $this->assertTrue(is_int($ratingCount), 'Is Int');
        }
        $expectedRatingCount = 1;
        $this->assertSame($expectedRatingCount, $ratingCount);

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testMultipleProductsReturnsCombinedCount()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $product = $this->getProduct('klevu_simple_1');
        $parentProduct = $this->getProduct('klevu_configurable_1');

        $getReviewCountService = $this->instantiateGetReviewCountService();
        $ratingCount = $getReviewCountService->execute([$product, $parentProduct]);

        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($ratingCount);
        } else {
            $this->assertTrue(is_int($ratingCount), 'Is Int');
        }
        $expectedRatingCount = 2;
        $this->assertSame($expectedRatingCount, $ratingCount);

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testReviewsForSameProductAreNotCountedTwice()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $product = $this->getProduct('klevu_simple_1');
        $parentProduct = $this->getProduct('klevu_configurable_1');

        $getReviewCountService = $this->instantiateGetReviewCountService();
        $ratingCount = $getReviewCountService->execute([$product, $parentProduct, $product]);

        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($ratingCount);
        } else {
            $this->assertTrue(is_int($ratingCount), 'Is Int');
        }
        $expectedRatingCount = 2;
        $this->assertSame($expectedRatingCount, $ratingCount);

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testProductWithOneReviewInAnotherStoreReturnsCountOfZero()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_2');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $product = $this->getProduct('klevu_simple_1', $store);

        $getReviewCountService = $this->instantiateGetReviewCountService();
        $ratingCount = $getReviewCountService->execute([$product]);

        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($ratingCount);
        } else {
            $this->assertTrue(is_int($ratingCount), 'Is Int');
        }
        $expectedRatingCount = 0;
        $this->assertSame($expectedRatingCount, $ratingCount);

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testSimpleProductAsConfigurableChildReturnsCombinedCountForStore2()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_2');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $product = $this->getProduct('klevu_simple_1', $store);
        $parentProduct = $this->getProduct('klevu_configurable_1', $store);

        $getReviewCountService = $this->instantiateGetReviewCountService();
        $ratingCount = $getReviewCountService->execute([$product, $parentProduct]);

        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($ratingCount);
        } else {
            $this->assertTrue(is_int($ratingCount), 'Is Int');
        }
        $expectedRatingCount = 1;
        $this->assertSame($expectedRatingCount, $ratingCount);

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return GetReviewCount
     */
    private function instantiateGetReviewCountService()
    {
        return $this->objectManager->create(GetReviewCount::class);
    }

    /**
     * @param string $sku
     * @param StoreInterface|null $store
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku, $store = null)
    {
        if (null === $store) {
            $store =$this->getStore('klevu_test_store_1');
        }
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);

        $product = $productRepository->get($sku);
        $loadProduct = $this->objectManager->create(LoadAttribute::class);

        $productCollection = $loadProduct->loadProductDataCollection([$product->getId()],
            $store ? $store->getId() : null);

        return $productCollection->getItemById($product->getId());
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
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadReviewFixtures()
    {
        include __DIR__ . '/../_files/reviewFixturesWithRating.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadReviewFixturesRollback()
    {
        include __DIR__ . '/../_files/reviewFixturesWithRating_rollback.php';
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../_files/productFixtures.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../_files/productFixtures_rollback.php';
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }
}
