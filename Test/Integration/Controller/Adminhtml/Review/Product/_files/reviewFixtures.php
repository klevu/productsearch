<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Review\Model\Rating;
use Magento\Review\Model\ResourceModel\Rating\Collection as RatingCollection;
use Magento\Review\Model\ResourceModel\Rating\Option\CollectionFactory as RatingOptionCollectionFactory;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$store1 = $storeManager->getStore('klevu_test_store_1');
$store2 = $storeManager->getStore('klevu_test_store_2');

/** @var Rating $ratingModel */
$ratingModel = $objectManager->create(Rating::class);
/** @var RatingCollection $ratingCollection */
$ratingCollection = $ratingModel->getCollection()
    ->setPageSize(3)
    ->setCurPage(1);
// activate each rating for each store
foreach ($ratingCollection as $rating) {
    $rating->setStores(
        array_unique(array_merge(
            (array)$rating->getStores(),
            [
                $store1->getId(),
                $store2->getId(),
            ]
        ))
    );
    $rating->setIsActive(1);
    $rating->save();
}

$ratingIds = $ratingCollection->getColumnValues('rating_id');

/** @var RatingOptionCollectionFactory $ratingOptionCollectionFactory */
$ratingOptionCollectionFactory = $objectManager->get(RatingOptionCollectionFactory::class);
$ratingOptionCollection = $ratingOptionCollectionFactory->create();
$ratingOptionIds = [];
foreach ($ratingOptionCollection as $ratingOption) {
    /** @var Option $ratingOption */
    if (!isset($ratingOptionIds[$ratingOption->getRatingId()])) {
        $ratingOptionIds[$ratingOption->getRatingId()] = [];
    }

    $ratingOptionIds[$ratingOption->getRatingId()][$ratingOption->getValue()] = $ratingOption->getId();
}
array_walk($ratingOptionIds, 'ksort');

if (!function_exists('createRatingDataFixture')) {
    /**
     * @param array $ratingValues
     * @param array $ratingIds
     * @param array $ratingOptionIds
     * @return array
     */
    function createRatingDataFixture(array $ratingValues, array $ratingIds, array $ratingOptionIds)
    {
        $return = [];
        foreach ($ratingValues as $i => $ratingValue) {
            if (!isset($ratingIds[$i])) {
                break;
            }

            $ratingId = $ratingIds[$i];
            if (!isset($ratingOptionIds[$ratingId])) {
                continue;
            }

            $return[$ratingId] = $ratingOptionIds[$ratingId][$ratingValue];
        }

        return $return;
    }
}

if (empty($REVIEW_FIXTURES_GROUP)) {
    $REVIEW_FIXTURES_GROUP = null;
}
$reviewsData = [];
if (in_array($REVIEW_FIXTURES_GROUP, ['PendingToApproved', null], true)) {
    $productFixture = $productRepository->get('klevu_simple_reviewtest_pendingtoapproved');

    $reviewsData = array_merge(
        $reviewsData,
        [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Pending To Approved: With Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_PENDING,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
                'rating' => createRatingDataFixture(['3', '4', '5'], $ratingIds, $ratingOptionIds),
            ],
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Pending To Approved: Without Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_PENDING,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
            ],
        ]
    );
}
if (in_array($REVIEW_FIXTURES_GROUP, ['DisapprovedToApproved', null], true)) {
    $productFixture = $productRepository->get('klevu_simple_reviewtest_disapprovedtoapproved');

    $reviewsData = array_merge(
        $reviewsData,
        [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Disapproved To Approved: With Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_NOT_APPROVED,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
                'rating' => createRatingDataFixture(['2', '3', '4'], $ratingIds, $ratingOptionIds),
            ],
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Disapproved To Approved: Without Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_NOT_APPROVED,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
            ],
        ]
    );
}
if (in_array($REVIEW_FIXTURES_GROUP, ['ApprovedToPending', null], true)) {
    $productFixture = $productRepository->get('klevu_simple_reviewtest_approvedtopending');;

    $reviewsData = array_merge(
        $reviewsData,
        [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Approved To Pending: With Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_APPROVED,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
                'rating' => createRatingDataFixture(['3', '3', '3'], $ratingIds, $ratingOptionIds),
            ],
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Approved To Pending: Without Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_APPROVED,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
            ],
        ]
    );
}
if (in_array($REVIEW_FIXTURES_GROUP, ['DisapprovedToPending', null], true)) {
    $productFixture = $productRepository->get('klevu_simple_reviewtest_disapprovedtopending');

    $reviewsData = array_merge(
        $reviewsData,
        [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Disapproved To Pending: With Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_NOT_APPROVED,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
                'rating' => createRatingDataFixture(['3', '2', '1'], $ratingIds, $ratingOptionIds),
            ],
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Disapproved To Pending: Without Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_NOT_APPROVED,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
            ],
        ]
    );
}
if (in_array($REVIEW_FIXTURES_GROUP, ['ApprovedToDisapproved', null], true)) {
    $productFixture = $productRepository->get('klevu_simple_reviewtest_approvedtodisapproved');

    $reviewsData = array_merge(
        $reviewsData,
        [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Approved To Disapproved: With Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_APPROVED,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
                'rating' => createRatingDataFixture(['1', '2', '3'], $ratingIds, $ratingOptionIds),
            ],
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Approved To Disapproved: Without Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_APPROVED,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
            ],
        ]
    );
}
if (in_array($REVIEW_FIXTURES_GROUP, ['PendingToDisapproved', null], true)) {
    $productFixture = $productRepository->get('klevu_simple_reviewtest_pendingtodisapproved');

    $reviewsData = array_merge(
        $reviewsData,
        [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Pending To Disapproved: With Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_PENDING,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
                'rating' => createRatingDataFixture(['2', '3', '1'], $ratingIds, $ratingOptionIds),
            ],
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Pending To Disapproved: Without Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_PENDING,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
            ],
        ]
    );
}
if (in_array($REVIEW_FIXTURES_GROUP, ['ApprovedWithRating', null], true)) {
    $productFixture = $productRepository->get('klevu_simple_reviewtest_approvedwithrating');

    $reviewsData = array_merge(
        $reviewsData,
        [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Approved: With Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_APPROVED,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
                'rating' => createRatingDataFixture(['2', '2', '2'], $ratingIds, $ratingOptionIds),
            ],
        ]
    );
}
if (in_array($REVIEW_FIXTURES_GROUP, ['ApprovedWithoutRating', null], true)) {
    $productFixture = $productRepository->get('klevu_simple_reviewtest_approvedwithoutrating');

    $reviewsData = array_merge(
        $reviewsData,
        [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Approved: Without Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_APPROVED,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
            ],
        ]
    );
}
if (in_array($REVIEW_FIXTURES_GROUP, ['PendingWithRating', null], true)) {
    $productFixture = $productRepository->get('klevu_simple_reviewtest_pendingwithrating');

    $reviewsData = array_merge(
        $reviewsData,
        [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Pending: With Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_PENDING,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
                'rating' => createRatingDataFixture(['4', '2', '3'], $ratingIds, $ratingOptionIds),
            ],
        ]
    );
}
if (in_array($REVIEW_FIXTURES_GROUP, ['PendingWithoutRating', null], true)) {
    $productFixture = $productRepository->get('klevu_simple_reviewtest_pendingwithoutrating');

    $reviewsData = array_merge(
        $reviewsData,
        [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Pending: Without Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_PENDING,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
            ],
        ]
    );
}
if (in_array($REVIEW_FIXTURES_GROUP, ['DisapprovedWithRating', null], true)) {
    $productFixture = $productRepository->get('klevu_simple_reviewtest_disapprovedwithrating');

    $reviewsData = array_merge(
        $reviewsData,
        [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Disapproved: With Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_NOT_APPROVED,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
                'rating' => createRatingDataFixture(['5', '3', '4'], $ratingIds, $ratingOptionIds),
            ],
        ]
    );
}
if (in_array($REVIEW_FIXTURES_GROUP, ['DisapprovedWithoutRating', null], true)) {
    $productFixture = $productRepository->get('klevu_simple_reviewtest_disapprovedwithoutrating');

    $reviewsData = array_merge(
        $reviewsData,
        [
            [
                'review' => [
                    'data' => [
                        'nickname' => 'Integration Test Fixture',
                        'title' => 'Disapproved: Without Rating',
                        'detail' => 'Review text',
                    ],
                ],
                'status' => Review::STATUS_NOT_APPROVED,
                'productId' => $productFixture->getId(),
                'storeId' => $store1->getId(),
                'stores' => [$store1->getId()],
            ],
        ]
    );
}

foreach ($reviewsData as $reviewData) {
    $review = $objectManager->create(
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
}
