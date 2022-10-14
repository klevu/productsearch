<?php

namespace Klevu\Search\Test\Integration\Service\Catalog\Product\Review;

use Klevu\Search\Api\Service\Catalog\Product\Review\GetAverageRatingInterface;
use Klevu\Search\Model\Product\LoadAttribute;
use Klevu\Search\Service\Catalog\Product\Review\GetAverageRating;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetAverageRatingServiceTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setupPhp5();

        $getRatingsCountService = $this->instantiateGetAverageRating();

        $this->assertInstanceOf(GetAverageRatingInterface::class, $getRatingsCountService);
    }

    public function testReturnsNullWhenNoProductsProvided()
    {
        $this->setupPhp5();

        $products = [];

        $getRatingsCountService = $this->instantiateGetAverageRating();
        $averageRating = $getRatingsCountService->execute($products);

        $this->assertnull($averageRating);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testReturnsNullWhenNoProductHasNoRating()
    {
        $this->setupPhp5();

        $products = [
            $this->getProduct('klevu_simple_1')
        ];

        $getRatingsCountService = $this->instantiateGetAverageRating();
        $averageRating = $getRatingsCountService->execute($products);

        $this->assertnull($averageRating);

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
    public function testReturnsFloatWhenProductHasRating()
    {
        $this->setupPhp5();

        $product1 = $this->getProduct('klevu_simple_1');
        $products = [
            $product1
        ];

        $getRatingsCountService = $this->instantiateGetAverageRating();
        $averageRating = $getRatingsCountService->execute($products);

        $this->assertSame(80.0, $averageRating);

        static::loadReviewFixturesRollback();
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
    public function testReturnsAverageRatingForMultipleProducts()
    {
        $this->setupPhp5();

        $product1 = $this->getProduct('klevu_simple_1');
        $product2 = $this->getProduct('klevu_configurable_1');
        $products = [
            $product1,
            $product2,
        ];

        $getRatingsCountService = $this->instantiateGetAverageRating();
        $averageRating = $getRatingsCountService->execute($products);

        $this->assertSame(70.0, $averageRating);

        static::loadReviewFixturesRollback();
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
        $this->setupPhp5();

        $product1 = $this->getProduct('klevu_simple_1'); // has 1 rating, average 80
        $product2 = $this->getProduct('klevu_simple_3'); // has 3 ratings, average 20
        $products = [
            $product1,
            $product2
        ];

        $getRatingsCountService = $this->instantiateGetAverageRating();
        $averageRating = $getRatingsCountService->execute($products);

        $this->assertSame(35.0, $averageRating);

        static::loadReviewFixturesRollback();
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
    public function testDoesNotCountTheSameProductMultipleTimes()
    {
        $this->setupPhp5();

        $product1 = $this->getProduct('klevu_simple_1');
        $product2 = $this->getProduct('klevu_configurable_1');
        $products = [
            $product1,
            $product1,
            $product1,
            $product2,
        ];

        $getRatingsCountService = $this->instantiateGetAverageRating();
        $averageRating = $getRatingsCountService->execute($products);

        $this->assertSame(70.0, $averageRating);

        static::loadReviewFixturesRollback();
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
    public function testProductsWithoutRatingAreIgnored()
    {
        $this->setupPhp5();

        $product1 = $this->getProduct('klevu_simple_1');
        $product2 = $this->getProduct('klevu_simple_2'); // has no rating
        $products = [
            $product1,
            $product2
        ];

        $getRatingsCountService = $this->instantiateGetAverageRating();
        $averageRating = $getRatingsCountService->execute($products);

        $this->assertSame(80.0, $averageRating);

        static::loadReviewFixturesRollback();
        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return void
     * @TODO convert to protected function setUp() and remove calls in each test once support for PHP5.6 is dropped
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @return GetAverageRating|mixed
     */
    private function instantiateGetAverageRating()
    {
        return $this->objectManager->create(GetAverageRating::class);
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
            $store = $this->getStore('klevu_test_store_1');
        }
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);

        $product = $productRepository->get($sku);
        $loadProduct = $this->objectManager->create(LoadAttribute::class);

        $productCollection = $loadProduct->loadProductDataCollection(
            [$product->getId()],
            $store->getId()
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
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadReviewFixtures()
    {
        include __DIR__ . '/../../_files/reviewFixturesWithRating.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadReviewFixturesRollback()
    {
        include __DIR__ . '/../../_files/reviewFixturesWithRating_rollback.php';
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../_files/productFixtures.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../_files/productFixtures_rollback.php';
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../../_files/websiteFixtures_rollback.php';
    }
}
