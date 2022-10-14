<?php

namespace Klevu\Search\Test\Integration\Provider\Catalog\Product\Review;

use Klevu\Search\Api\Provider\Catalog\Product\Review\AllRatingsDataProviderInterface;
use Klevu\Search\Model\Product\LoadAttribute;
use Klevu\Search\Provider\Catalog\Product\Review\MagentoAllRatingsDataProvider;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class MagentoAllRatingsDataProviderTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $dataProvider = $this->instantiateMagentoAllRatingsDataProvider();
        $this->assertInstanceOf(AllRatingsDataProviderInterface::class, $dataProvider);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testReturnedSingleApprovedRatingForOneProduct()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1', $store);

        $dataProvider = $this->instantiateMagentoAllRatingsDataProvider();
        $data = $dataProvider->getData((int)$store->getId());

        $expected = [
            RatingDataMapper::RATING_COUNT => '3',
            RatingDataMapper::RATING_PRODUCT_ID => $product->getId(),
            RatingDataMapper::RATING_STORE => $store->getId(),
            RatingDataMapper::RATING_SUM => '240',
            RatingDataMapper::REVIEW_COUNT => '1'
        ];
        $this->assertDataIsCorrect($expected, $data);

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testReturnsOnlyApprovedRatings()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_2');
        $product = $this->getProduct('klevu_configurable_1', $store);

        $dataProvider = $this->instantiateMagentoAllRatingsDataProvider();
        $data = $dataProvider->getData((int)$store->getId());

        $expected = [
            RatingDataMapper::RATING_COUNT => '3',
            RatingDataMapper::RATING_PRODUCT_ID => $product->getId(),
            RatingDataMapper::RATING_STORE => $store->getId(),
            RatingDataMapper::RATING_SUM => '180',
            RatingDataMapper::REVIEW_COUNT => '1'
        ];
        $this->assertDataIsCorrect($expected, $data);

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testReturnsMultipleRatings()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_3', $store);

        $dataProvider = $this->instantiateMagentoAllRatingsDataProvider();
        $data = $dataProvider->getData((int)$store->getId());

        $expected = [
            RatingDataMapper::RATING_COUNT => '9',
            RatingDataMapper::RATING_PRODUCT_ID => $product->getId(),
            RatingDataMapper::RATING_STORE => $store->getId(),
            RatingDataMapper::RATING_SUM => '180',
            RatingDataMapper::REVIEW_COUNT => '3'
        ];
        $this->assertDataIsCorrect($expected, $data);

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures_RatingAttributesTest
     * @magentoDataFixture loadReviewFixtures
     */
    public function testReturnsEmptyRatingsForProductsWithAttributeDataAndNoReviews()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $storeId = (int)$store->getId();

        $dataProvider = $this->instantiateMagentoAllRatingsDataProvider();
        $data = $dataProvider->getData((int)$store->getId());

        $expectedReturnedProducts = [
            $this->getProduct('klevu_simple_1', $store),
            $this->getProduct('klevu_simple_3', $store),
            $this->getProduct('klevu_configurable_1', $store),
        ];
        $expectedReturnedEmptyProducts = [
            $this->getProduct('klevu_simple_with_rating_with_reviewcount_allstores', $store),
            $this->getProduct('klevu_simple_with_rating_with_reviewcount_store1', $store),
            $this->getProduct('klevu_simple_with_rating_without_reviewcount_allstores', $store),
            $this->getProduct('klevu_simple_with_rating_without_reviewcount_store1', $store),
            $this->getProduct('klevu_simple_without_rating_with_reviewcount_allstores', $store),
            $this->getProduct('klevu_simple_without_rating_with_reviewcount_store1', $store),
        ];

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($data);
        } else {
            $this->assertTrue(is_array($data), 'Returned data is array');
        }
        $this->assertCount(count($expectedReturnedProducts) + count($expectedReturnedEmptyProducts), $data);

        foreach ($expectedReturnedProducts as $product) {
            /** @var ProductInterface $product */
            $filteredData = array_filter($data, static function ($datum) use ($product) {
                // Intentionally not checking existence as if this key is missing it should error in the test
                return (int)$datum['entity_pk_value'] === (int)$product->getId();
            });
            $this->assertCount(1, $filteredData, 'Product ' . $product->getSku() . ' with reviews returned exactly once');
            $ratingItem = current($filteredData);
            $this->assertEquals($storeId, $ratingItem['store'], 'Store ID for ' . $product->getSku());
            $this->assertNotEmpty($ratingItem['sum'], 'Sum for ' . $product->getSku());
            $this->assertNotEmpty($ratingItem['review_count'], 'Review Count for ' . $product->getSku());
        }

        foreach ($expectedReturnedEmptyProducts as $product) {
            /** @var ProductInterface $product */
            $filteredData = array_filter($data, static function ($datum) use ($product) {
                // Intentionally not checking existence as if this key is missing it should error in the test
                return (int)$datum['entity_pk_value'] === (int)$product->getId();
            });
            $this->assertCount(1, $filteredData, 'Product ' . $product->getSku() . ' without reviews returned exactly once');
            $ratingItem = current($filteredData);
            $this->assertEquals($storeId, $ratingItem['store'], 'Store ID for ' . $product->getSku());
            $this->assertEmpty($ratingItem['sum'], 'Sum for ' . $product->getSku());
            $this->assertEmpty($ratingItem['review_count'], 'Review Count for ' . $product->getSku());
        }

        static::loadProductFixtures_RatingAttributesTestRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @param array $expected
     * @param array $actual
     *
     * @return void
     */
    private function assertDataIsCorrect($expected, $actual)
    {
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($actual);
        } else {
            $this->assertTrue(is_array($actual), 'Is Array');
        }

        $filteredData = array_filter($actual, function ($review) use ($expected) {
            return (int)$review[RatingDataMapper::RATING_PRODUCT_ID] === (int)$expected[RatingDataMapper::RATING_PRODUCT_ID];
        });
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($filteredData);
        } else {
            $this->assertTrue(is_array($filteredData), 'Is Array');
        }
        $keys = array_keys($filteredData);
        $this->assertArrayHasKey($keys[0], $filteredData);
        $reviewData = $filteredData[$keys[0]];

        $this->assertArrayHasKey(RatingDataMapper::RATING_COUNT, $reviewData);
        $this->assertSame($expected[RatingDataMapper::RATING_COUNT], $reviewData[RatingDataMapper::RATING_COUNT]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_PRODUCT_ID, $reviewData);
        $this->assertSame($expected[RatingDataMapper::RATING_PRODUCT_ID],
            $reviewData[RatingDataMapper::RATING_PRODUCT_ID]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_STORE, $reviewData);
        $this->assertSame($expected[RatingDataMapper::RATING_STORE], $reviewData[RatingDataMapper::RATING_STORE]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_SUM, $reviewData);
        $this->assertSame($expected[RatingDataMapper::RATING_SUM], $reviewData[RatingDataMapper::RATING_SUM]);

        $this->assertArrayHasKey(RatingDataMapper::REVIEW_COUNT, $reviewData);
        $this->assertSame($expected[RatingDataMapper::REVIEW_COUNT], $reviewData[RatingDataMapper::REVIEW_COUNT]);
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
     * @return MagentoAllRatingsDataProvider
     */
    private function instantiateMagentoAllRatingsDataProvider()
    {
        return $this->objectManager->create(MagentoAllRatingsDataProvider::class);
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
     * @param string $sku
     * @param StoreInterface $store
     *
     * @return ProductInterface|Product
     * @throws NoSuchEntityException
     */
    private function getProduct($sku, $store)
    {
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
    public static function loadProductFixtures_RatingAttributesTest()
    {
        self::loadProductFixtures();

        include __DIR__ . '/../../../../Service/Catalog/_files/productFixtures_ratingAttributes.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures_RatingAttributesTestRollback()
    {
        include __DIR__ . '/../../../../Service/Catalog/_files/productFixtures_ratingAttributes_rollback.php';

        self::loadProductFixturesRollback();
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
