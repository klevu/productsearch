<?php

namespace Klevu\Search\Test\Integration\Model\Observer;

use Klevu\Search\Model\Attribute\Rating as RatingAttribute;
use Klevu\Search\Model\Product\LoadAttribute;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\Rating;
use Magento\Review\Model\Rating\Option;
use Magento\Review\Model\ResourceModel\Rating\Collection as RatingCollection;
use Magento\Review\Model\ResourceModel\Rating\CollectionFactory as RatingCollectionFactory;
use Magento\Review\Model\ResourceModel\Rating\Option\CollectionFactory as RatingOptionCollectionFactory;
use Magento\Review\Model\Review;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class RatingsUpdateObserverTest extends TestCase
{
    const EVENT_NAME = "review_save_after";

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var array[]
     */
    private $ratingIds = [];

    /**
     * @var int[]
     */
    private $ratingOptionIds = [];

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoAppArea adminhtml
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testProductRatingIsUpdatedWhenReviewIsSaved()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $origProduct = $this->getProduct('klevu_simple_1', $store);

        $reviewsData = [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Nickname 1',
                        'title' => 'Review Summary 1',
                        'detail' => 'Review text 1',
                    ],
                ],
                'status' => Review::STATUS_APPROVED,
                'productId' => $origProduct->getId(),
                'storeId' => $store->getId(),
                'stores' => [
                    $store->getId(),
                ],
                'rating' => $this->createRatingDataFixture(['3', '4', '5']),
            ],
        ];
        $this->createReviews($reviewsData);

        $product = $this->getProduct('klevu_simple_1', $store);
        $rating = $product->getData(RatingAttribute::ATTRIBUTE_CODE);

        $expectedRating = '80';
        $this->assertSame($expectedRating, $rating);

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoAppArea adminhtml
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testProductRatingIsNullWhenReviewIsNotAddedToStore()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $origProduct = $this->getProduct('klevu_simple_1', $store);

        $reviewsData = [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Nickname 1',
                        'title' => 'Review Summary 1',
                        'detail' => 'Review text 1',
                    ],
                ],
                'status' => Review::STATUS_APPROVED,
                'productId' => $origProduct->getId(),
                'storeId' => $store->getId(),
                'stores' => [
                    $store->getId(),
                ],
                'rating' => $this->createRatingDataFixture(['5', '4', '3']),
            ],
        ];
        $this->createReviews($reviewsData);

        $otherStore = $this->getStore('klevu_test_store_2');
        $product = $this->getProduct('klevu_simple_1', $otherStore);
        $rating = $product->getData(RatingAttribute::ATTRIBUTE_CODE);

        if (method_exists($this, 'assertNull')) {
            $this->assertNull($rating);
        } else {
            $this->assertTrue(is_null($rating), 'Is Null');
        }

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoAppArea adminhtml
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testProductRatingIsNullWhenReviewIsNotApproved()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $origProduct = $this->getProduct('klevu_simple_1', $store);

        $reviewsData = [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Nickname 1',
                        'title' => 'Review Summary 1',
                        'detail' => 'Review text 1',
                    ],
                ],
                'status' => Review::STATUS_PENDING,
                'productId' => $origProduct->getId(),
                'storeId' => $store->getId(),
                'stores' => [
                    $store->getId(),
                ],
                'rating' => $this->createRatingDataFixture(['3', '5', '4']),
            ],
        ];
        $this->createReviews($reviewsData);

        $otherStore = $this->getStore('klevu_test_store_2');
        $product = $this->getProduct('klevu_simple_1', $otherStore);
        $rating = $product->getData(RatingAttribute::ATTRIBUTE_CODE);

        if (method_exists($this, 'assertNull')) {
            $this->assertNull($rating);
        } else {
            $this->assertTrue(is_null($rating), 'Is Null');
        }

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoAppArea adminhtml
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testRatingCalculationIsTriggeredByProductDelete()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $origProduct = $this->getProduct('klevu_simple_1', $store);

        $review1 = [
            'review' => [
                'data' => [
                    'nickname' => 'Nickname 1',
                    'title' => 'Review Summary 1',
                    'detail' => 'Review text 1',
                ],
            ],
            'status' => Review::STATUS_APPROVED,
            'productId' => $origProduct->getId(),
            'storeId' => $store->getId(),
            'stores' => [
                $store->getId(),
            ],
            'rating' => $this->createRatingDataFixture(['3', '4', '5']),
        ];
        $review2 = [
            'review' => [
                'data' => [
                    'nickname' => 'Nickname 2',
                    'title' => 'Review Summary 2',
                    'detail' => 'Review text 2',
                ],
            ],
            'status' => Review::STATUS_APPROVED,
            'productId' => $origProduct->getId(),
            'storeId' => $store->getId(),
            'stores' => [
                $store->getId(),
            ],
            'rating' => $this->createRatingDataFixture(['1', '1', '1']),
        ];

        $reviewsData = [$review1, $review2];

        $reviews = $this->createReviews($reviewsData);

        $this->deleteReview([$reviews[1]]);

        $product = $this->getProduct('klevu_simple_1', $store);
        $rating = $product->getData(RatingAttribute::ATTRIBUTE_CODE);

        $expectedRating = '80';
        $this->assertSame($expectedRating, $rating);

        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @param array $reviewsData
     *
     * @return array
     * @throws NoSuchEntityException
     */
    private function createReviews(array $reviewsData)
    {
        $reviews = [];
        $store1 = $this->getStore('klevu_test_store_1');
        $store2 = $this->getStore('klevu_test_store_2');

        /** @var Rating $ratingModel */
        $ratingModel = $this->objectManager->create(Rating::class);
        /** @var RatingCollection $ratingCollection */
        $ratingCollection = $ratingModel->getCollection()
            ->setPageSize(3)
            ->setCurPage(1);
        // activate each rating for each store
        foreach ($ratingCollection as $rating) {
            $existingStores = $rating->getStores();
            if (!is_array($existingStores)) {
                $existingStores = [];
            }
            $rating->setStores(array_unique(array_merge(
                $existingStores,
                [
                    $store1->getId(),
                    $store2->getId(),
                ]
            )));
            $rating->setIsActive(1);
            $rating->save();
        }

        foreach ($reviewsData as $reviewData) {
            $review = $this->objectManager->create(
                Review::class,
                $reviewData['review']
            );
            $review->setEntityId(
                $review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE)
            )->setEntityPkValue(
                $reviewData['productId']
            )->setStatusId(
                $reviewData['status']
            )->setStoreId(
                $reviewData['storeId']
            )->setStores(
                $reviewData['stores']
            )->save();

            foreach ($ratingCollection as $rating) {
                if (!isset($reviewData['rating'][$rating->getId()])) {
                    continue;
                }

                $rating->setReviewId($review->getId())
                    ->addOptionVote($reviewData['rating'][$rating->getId()], $reviewData['productId']);
            }

            $this->dispatchEvent($review);
            $reviews[] = $review;
        }

        return $reviews;
    }

    /**
     * @param Review[] $reviews
     *
     * @return void
     * @throws \Exception
     */
    private function deleteReview(array $reviews)
    {
        foreach ($reviews as $review) {
            $review->delete();
        }
    }

    /**
     * @param Review $review
     *
     * @return void
     */
    private function dispatchEvent(Review $review)
    {
        $event = $this->objectManager->create(Event::class);
        $event->setName(self::EVENT_NAME);
        $event->setData([
            'data_object' => $review,
            'object' => $review,
            'name' => self::EVENT_NAME,
        ]);
        /** @var EventManager $eventManager */
        $eventManager = $this->objectManager->create(EventManager::class);
        $eventManager->dispatch(
            self::EVENT_NAME,
            [
                'object' => $review,
                'data_object' => $review,
                'event' => $event,
            ]
        );
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    protected function getStore($storeCode)
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->create(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @param string $sku
     * @param StoreInterface $store
     *
     * @return ProductInterface
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
            $store ? $store->getId() : null
        );

        return $productCollection->getItemById($product->getId());
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();

        /** @var RatingCollectionFactory $ratingCollectionFactory */
        $ratingCollectionFactory = $this->objectManager->get(RatingCollectionFactory::class);
        $ratingCollection = $ratingCollectionFactory->create();
        $ratingCollection->setPageSize(3);
        $ratingCollection->setCurPage(1);
        $this->ratingIds = $ratingCollection->getColumnValues('rating_id');

        /** @var RatingOptionCollectionFactory $ratingOptionCollectionFactory */
        $ratingOptionCollectionFactory = $this->objectManager->get(RatingOptionCollectionFactory::class);
        $ratingOptionCollection = $ratingOptionCollectionFactory->create();
        $this->ratingOptionIds = [];
        foreach ($ratingOptionCollection as $ratingOption) {
            /** @var Option $ratingOption */
            if (!isset($this->ratingOptionIds[$ratingOption->getRatingId()])) {
                $this->ratingOptionIds[$ratingOption->getRatingId()] = [];
            }

            $this->ratingOptionIds[$ratingOption->getRatingId()][$ratingOption->getValue()] = $ratingOption->getId();
        }
        array_walk($this->ratingOptionIds, 'ksort');
    }

    /**
     * @param array $ratingValues
     * @return array
     */
    private function createRatingDataFixture(array $ratingValues)
    {
        $return = [];
        foreach ($ratingValues as $i => $ratingValue) {
            if (!isset($this->ratingIds[$i])) {
                break;
            }

            $ratingId = $this->ratingIds[$i];
            if (!isset($this->ratingOptionIds[$ratingId])) {
                continue;
            }

            $return[$ratingId] = $this->ratingOptionIds[$ratingId][$ratingValue];
        }

        return $return;
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../Service/Catalog/_files/productFixtures.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../Service/Catalog/_files/productFixtures_rollback.php';
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }
}
