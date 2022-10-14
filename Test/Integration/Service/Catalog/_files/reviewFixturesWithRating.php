<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Review\Model\Rating;
use Magento\Review\Model\Rating\Option as RatingOption;
use Magento\Review\Model\ResourceModel\Rating\Collection as RatingCollection;
use Magento\Review\Model\ResourceModel\Rating\Option\CollectionFactory as RatingOptionCollectionFactory;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$productSimple1 = $productRepository->get('klevu_simple_1');
// leave klevu_simple_2 with no ratings
$productSimple3 = $productRepository->get('klevu_simple_3');
$productConfigurable = $productRepository->get('klevu_configurable_1');

$storeManager = $objectManager->create(StoreManagerInterface::class);
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
    /** @var RatingOption $ratingOption */
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
        'productId' => $productSimple1->getId(),
        'storeId' => $store1->getId(),
        'stores' => [
            $store1->getId(),
        ],
        'rating' => createRatingDataFixture(['3', '4', '5'], $ratingIds, $ratingOptionIds),
    ],
    [
        'review' => [
            'data' => [
                'nickname' => 'Nickname 2',
                'title' => 'Review Summary 2',
                'detail' => 'Review text 2',
            ],
        ],
        'status' => Review::STATUS_APPROVED,
        'productId' => $productConfigurable->getId(),
        'storeId' => $store1->getId(),
        'stores' => [
            $store1->getId(),
            $store2->getId(),
        ],
        'rating' => createRatingDataFixture(['2', '3', '4'], $ratingIds, $ratingOptionIds),
    ],
    [
        'review' => [
            'data' => [
                'nickname' => 'Nickname 3',
                'title' => 'Review Summary 3',
                'detail' => 'Review text 3',
            ],
        ],
        'status' => Review::STATUS_NOT_APPROVED,
        'productId' => $productConfigurable->getId(),
        'storeId' => $store1->getId(),
        'stores' => [
            $store1->getId(),
        ],
        'rating' => createRatingDataFixture(['1', '1', '1'], $ratingIds, $ratingOptionIds),

    ],
    [
        'review' => [
            'data' => [
                'nickname' => 'Nickname 4',
                'title' => 'Review Summary 4',
                'detail' => 'Review text 4',
            ],
        ],
        'status' => Review::STATUS_PENDING,
        'productId' => $productConfigurable->getId(),
        'storeId' => $store1->getId(),
        'stores' => [
            $store1->getId(),
        ],
        'rating' => createRatingDataFixture(['4', '5', '4'], $ratingIds, $ratingOptionIds),
    ],
    [
        'review' => [
            'data' => [
                'nickname' => 'Nickname 5',
                'title' => 'Review Summary 5',
                'detail' => 'Review text 5',
            ],
        ],
        'status' => Review::STATUS_APPROVED,
        'productId' => $productSimple3->getId(),
        'storeId' => $store1->getId(),
        'stores' => [
            $store1->getId(),
            $store2->getId(),
        ],
        'rating' => createRatingDataFixture(['1', '1', '1'], $ratingIds, $ratingOptionIds),
    ],
    [
        'review' => [
            'data' => [
                'nickname' => 'Nickname 6',
                'title' => 'Review Summary 6',
                'detail' => 'Review text 6',
            ],
        ],
        'status' => Review::STATUS_APPROVED,
        'productId' => $productSimple3->getId(),
        'storeId' => $store1->getId(),
        'stores' => [
            $store1->getId(),
            $store2->getId(),
        ],
        'rating' => createRatingDataFixture(['1', '1', '1'], $ratingIds, $ratingOptionIds),
    ],
    [
        'review' => [
            'data' => [
                'nickname' => 'Nickname 7',
                'title' => 'Review Summary 7',
                'detail' => 'Review text 7',
            ],
        ],
        'status' => Review::STATUS_APPROVED,
        'productId' => $productSimple3->getId(),
        'storeId' => $store1->getId(),
        'stores' => [
            $store1->getId(),
            $store2->getId(),
        ],
        'rating' => createRatingDataFixture(['1', '1', '1'], $ratingIds, $ratingOptionIds),
    ],
];

foreach ($reviewsData as $reviewData) {
    $review = $objectManager->create(
        Review::class,
        $reviewData['review']
    );
    $review->setEntityId(
        $review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE)
    );
    $review->setEntityPkValue(
        $reviewData['productId']
    );
    $review->setStatusId(
        $reviewData['status']
    );
    $review->setStoreId(
        $reviewData['storeId']
    );
    $review->setStores(
        $reviewData['stores']
    );
    $review->save();

    foreach ($ratingCollection as $rating) {
        if (!isset($reviewData['rating'][$rating->getId()])) {
            continue;
        }

        $rating->setReviewId($review->getId())
            ->addOptionVote($reviewData['rating'][$rating->getId()], $reviewData['productId']);
    }
}
