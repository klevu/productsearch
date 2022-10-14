<?php

namespace Klevu\Search\Test\Integration\Provider\Catalog\Product\Review;

use Klevu\Search\Api\Provider\Catalog\Product\Review\RatingDataProviderInterface;
use Klevu\Search\Model\Product\LoadAttribute;
use Klevu\Search\Provider\Catalog\Product\Review\MagentoRatingDataProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class MagentoRatingDataProviderTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $objectManager = ObjectManager::getInstance();
        $getRatingsCountService = $objectManager->get(MagentoRatingDataProvider::class);

        $this->assertInstanceOf(RatingDataProviderInterface::class, $getRatingsCountService);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testReturnsSumCountAndAverage()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $ratingDataProvider = $this->instantiateRatingDataProvider();
        $ratingData = $ratingDataProvider->getData($product->getId(), $store->getId());

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($ratingData);
        } else {
            $this->assertTrue(is_array($ratingData), 'Is Array');
        }
        $this->assertArrayHasKey('sum', $ratingData);
        $this->assertSame(240.0, $ratingData['sum']);

        $this->assertArrayHasKey('count', $ratingData);
        $this->assertSame(3, $ratingData['count']);

        $this->assertArrayHasKey('average', $ratingData);
        $this->assertSame(80.0, $ratingData['average']);

        $this->assertArrayHasKey('store', $ratingData);
        $this->assertSame((int)$store->getId(), $ratingData['store']);

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
    public function testReturnsNullValuesIfNoRatings()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_2');
        $product = $this->getProduct('klevu_simple_1');

        $ratingDataProvider = $this->instantiateRatingDataProvider();
        $ratingData = $ratingDataProvider->getData($product->getId(), $store->getId());

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($ratingData);
        } else {
            $this->assertTrue(is_array($ratingData), 'Is Array');
        }
        $this->assertArrayHasKey('sum', $ratingData);
        $this->assertNull($ratingData['sum']);

        $this->assertArrayHasKey('count', $ratingData);
        $this->assertSame(0, $ratingData['count']);

        $this->assertArrayHasKey('average', $ratingData);
        $this->assertNull($ratingData['average']);

        $this->assertArrayHasKey('store', $ratingData);
        $this->assertSame((int)$store->getId(), $ratingData['store']);

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
    public function testGetsStoreIdFromCurrentStoreWhenNotPassedIn()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $ratingDataProvider = $this->instantiateRatingDataProvider();
        $ratingData = $ratingDataProvider->getData($product->getId());

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($ratingData);
        } else {
            $this->assertTrue(is_array($ratingData), 'Is Array');
        }
        $this->assertArrayHasKey('sum', $ratingData);
        $this->assertSame(240.0, $ratingData['sum']);

        $this->assertArrayHasKey('count', $ratingData);
        $this->assertSame(3, $ratingData['count']);

        $this->assertArrayHasKey('average', $ratingData);
        $this->assertSame(80.0, $ratingData['average']);

        $this->assertArrayHasKey('store', $ratingData);
        $this->assertSame((int)$store->getId(), $ratingData['store']);

        static::loadReviewFixturesRollback();
        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
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
     * @return MagentoRatingDataProvider
     */
    private function instantiateRatingDataProvider()
    {
        return $this->objectManager->get(MagentoRatingDataProvider::class);
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
        include __DIR__ . '/../../../../Service/Catalog/_files/reviewFixturesWithRating.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadReviewFixturesRollback()
    {
        include __DIR__ . '/../../../../Service/Catalog/_files/reviewFixturesWithRating_rollback.php';
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../../../Service/Catalog/_files/productFixtures.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../../../Service/Catalog/_files/productFixtures_rollback.php';
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
