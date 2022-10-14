<?php

namespace Klevu\Search\Test\Integration\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\GetRatingStarsInterface;
use Klevu\Search\Model\Product\LoadAttribute;
use Klevu\Search\Service\Catalog\Product\GetRatingStars;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetRatingStarsServiceTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $getRatingStarsService = $this->instantiateGetRatingStarsService();

        $this->assertInstanceOf(GetRatingStarsInterface::class, $getRatingStarsService);
    }

    public function testReturnsNullWhenNoProductsProvided()
    {
        $this->setupPhp5();

        $products = [];

        $getRatingStarsService = $this->instantiateGetRatingStarsService();
        $ratingStars = $getRatingStarsService->execute($products);

        $this->assertNUll($ratingStars);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testReturnsNullWhenNoProductHasNoRating()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $product = $this->getProduct('klevu_simple_2', $store);

        $getRatingsCountService = $this->instantiateGetRatingStarsService();
        $stars = $getRatingsCountService->execute([$product]);

        $this->assertNull($stars);

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
    public function testGetStarsForSimpleProductInStore1()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $product = $this->getProduct('klevu_simple_1', $store);

        $getRatingsCountService = $this->instantiateGetRatingStarsService();
        $stars = $getRatingsCountService->execute([$product]);

        $expected = 4.0;
        $this->assertSame($expected, $stars);

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
    public function testGetStarsReturnsNullIfNoReviewAssignedToStore()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_2');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $product = $this->getProduct('klevu_simple_1', $store);

        $getRatingsCountService = $this->instantiateGetRatingStarsService();
        $stars = $getRatingsCountService->execute([$product]);

        $expected = null;
        $this->assertSame($expected, $stars);

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

        $product = $this->getProduct('klevu_configurable_1', $store);

        $getRatingsCountService = $this->instantiateGetRatingStarsService();
        $stars = $getRatingsCountService->execute([$product]);

        $expected = 3.0;
        $this->assertSame($expected, $stars);

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
    public function testGetStarsReturnsAverageRatingForMultipleProducts()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $productSimple = $this->getProduct('klevu_simple_1', $store);
        $productConfigurable = $this->getProduct('klevu_configurable_1', $store);

        $getRatingsCountService = $this->instantiateGetRatingStarsService();
        $stars = $getRatingsCountService->execute([$productSimple, $productConfigurable]);

        $expected = 3.5;
        $this->assertSame($expected, $stars);

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
    public function testOnlyCountsReviewsAssignedToStore()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_2');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $productSimple = $this->getProduct('klevu_simple_1', $store);
        $productConfigurable = $this->getProduct('klevu_configurable_1', $store);

        $getRatingsCountService = $this->instantiateGetRatingStarsService();
        $stars = $getRatingsCountService->execute([$productSimple, $productConfigurable]);

        $expected = 3.0;
        $this->assertSame($expected, $stars);

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

        $productSimple = $this->getProduct('klevu_simple_1', $store);
        $productConfigurable = $this->getProduct('klevu_configurable_1', $store);

        $getRatingsCountService = $this->instantiateGetRatingStarsService();
        $stars = $getRatingsCountService->execute([$productSimple, $productConfigurable, $productSimple]);

        $expected = 3.5;
        $this->assertSame($expected, $stars);

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
    public function testReturnsAverageRatingForMultipleProductsWithMultipleRatings()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $storeManager = $this->objectManager->create(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $product1 = $this->getProduct('klevu_simple_1', $store); // has 1 rating, average 4.0
        $product2 = $this->getProduct('klevu_simple_3', $store); // has 3 ratings, average 1.0

        $getRatingsCountService = $this->instantiateGetRatingStarsService();
        $stars = $getRatingsCountService->execute([$product1, $product2]);

        $expected = 1.75;
        $this->assertSame($expected, $stars);

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
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

        $productCollection = $loadProduct->loadProductDataCollection(
            [$product->getId()],
            $store ? $store->getId() : null
        );

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
     * @return GetRatingStars
     */
    private function instantiateGetRatingStarsService()
    {
        return $this->objectManager->get(GetRatingStars::class);
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
