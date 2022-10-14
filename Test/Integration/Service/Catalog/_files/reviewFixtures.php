<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$productSimple = $productRepository->get('klevu_simple_1');
$productConfigurable = $productRepository->get('klevu_configurable_1');

$storeManager = $objectManager->get(StoreManagerInterface::class);
$store1 = $storeManager->getStore('klevu_test_store_1');
$store2 = $storeManager->getStore('klevu_test_store_2');

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
        'productId' => $productSimple->getId(),
        'storeId' => $store1->getId(),
        'stores' => [$store1->getId()],
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
        'stores' => [$store1->getId()],
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
        'stores' => [$store1->getId()],
    ],
];

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
}
