<?php

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\Website;

$objectManager = Bootstrap::getObjectManager();

/** @var Website $baseWebsite */
$baseWebsite = $objectManager->create(Website::class);
$baseWebsite->load('base', 'code');

/** @var Website $website1 */
$website1 = $objectManager->create(Website::class);
$website1->load('klevu_test_website_1', 'code');

/** @var Website $website2 */
$website2 = $objectManager->create(Website::class);
$website2->load('klevu_test_website_2', 'code');

$fixtures = [
    'PendingToApproved' => [
        [
            'sku' => 'klevu_simple_reviewtest_pendingtoapproved',
            'name' => '[Klevu][Review Test] Simple Product for Reviews: Pending to Approved',
            'description' => '[Klevu][Review Test] Simple Product for Reviews',
            'short_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'attribute_set_id' => 4,
            'website_ids' => array_filter([
                $website1->getId(),
                $website2->getId(),
            ]),
            'price' => 10,
            'weight' => 1,
            'tax_class_id' => 2,
            'meta_title' => '[Klevu][Review Test] Simple Product for Reviews',
            'meta_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'url_key' => 'klevu_simple_reviewtest_pendingtoapproved' . crc32((string)rand()),
            'rating' => null,
            'review_count' => null,
        ],
    ],
    'DisapprovedToApproved' => [
        [
            'sku' => 'klevu_simple_reviewtest_disapprovedtoapproved',
            'name' => '[Klevu][Review Test] Simple Product for Reviews: Disapproved to Approved',
            'description' => '[Klevu][Review Test] Simple Product for Reviews',
            'short_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'attribute_set_id' => 4,
            'website_ids' => array_filter([
                $website1->getId(),
                $website2->getId(),
            ]),
            'price' => 10,
            'weight' => 1,
            'tax_class_id' => 2,
            'meta_title' => '[Klevu][Review Test] Simple Product for Reviews',
            'meta_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'url_key' => 'klevu_simple_reviewtest_disapprovedtoapproved' . crc32((string)rand()),
            'rating' => null,
            'review_count' => null,
        ],
    ],
    'ApprovedToPending' => [
        [
            'sku' => 'klevu_simple_reviewtest_approvedtopending',
            'name' => '[Klevu][Review Test] Simple Product for Reviews: Approved to Pending',
            'description' => '[Klevu][Review Test] Simple Product for Reviews',
            'short_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'attribute_set_id' => 4,
            'website_ids' => array_filter([
                $website1->getId(),
                $website2->getId(),
            ]),
            'price' => 10,
            'weight' => 1,
            'tax_class_id' => 2,
            'meta_title' => '[Klevu][Review Test] Simple Product for Reviews',
            'meta_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'url_key' => 'klevu_simple_reviewtest_approvedtopending' . crc32((string)rand()),
            'rating' => null,
            'review_count' => null,
        ],
    ],
    'DisapprovedToPending' => [
        [
            'sku' => 'klevu_simple_reviewtest_disapprovedtopending',
            'name' => '[Klevu][Review Test] Simple Product for Reviews: Disapproved to Pending',
            'description' => '[Klevu][Review Test] Simple Product for Reviews',
            'short_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'attribute_set_id' => 4,
            'website_ids' => array_filter([
                $website1->getId(),
                $website2->getId(),
            ]),
            'price' => 10,
            'weight' => 1,
            'tax_class_id' => 2,
            'meta_title' => '[Klevu][Review Test] Simple Product for Reviews',
            'meta_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'url_key' => 'klevu_simple_reviewtest_disapprovedtopending' . crc32((string)rand()),
            'rating' => null,
            'review_count' => null,
        ],
    ],
    'ApprovedToDisapproved' => [
        [
            'sku' => 'klevu_simple_reviewtest_approvedtodisapproved',
            'name' => '[Klevu][Review Test] Simple Product for Reviews: Approved to Disapproved',
            'description' => '[Klevu][Review Test] Simple Product for Reviews',
            'short_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'attribute_set_id' => 4,
            'website_ids' => array_filter([
                $website1->getId(),
                $website2->getId(),
            ]),
            'price' => 10,
            'weight' => 1,
            'tax_class_id' => 2,
            'meta_title' => '[Klevu][Review Test] Simple Product for Reviews',
            'meta_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'url_key' => 'klevu_simple_reviewtest_approvedtodisapproved' . crc32((string)rand()),
            'rating' => null,
            'review_count' => null,
        ],
    ],
    'PendingToDisapproved' => [
        [
            'sku' => 'klevu_simple_reviewtest_pendingtodisapproved',
            'name' => '[Klevu][Review Test] Simple Product for Reviews: Pending to Disapproved',
            'description' => '[Klevu][Review Test] Simple Product for Reviews',
            'short_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'attribute_set_id' => 4,
            'website_ids' => array_filter([
                $website1->getId(),
                $website2->getId(),
            ]),
            'price' => 10,
            'weight' => 1,
            'tax_class_id' => 2,
            'meta_title' => '[Klevu][Review Test] Simple Product for Reviews',
            'meta_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'url_key' => 'klevu_simple_reviewtest_pendingtodisapproved' . crc32((string)rand()),
            'rating' => null,
            'review_count' => null,
        ],
    ],
    'ApprovedWithRating' => [
        [
            'sku' => 'klevu_simple_reviewtest_approvedwithrating',
            'name' => '[Klevu][Review Test] Simple Product for Reviews: Approved With Rating',
            'description' => '[Klevu][Review Test] Simple Product for Reviews',
            'short_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'attribute_set_id' => 4,
            'website_ids' => array_filter([
                $website1->getId(),
                $website2->getId(),
            ]),
            'price' => 10,
            'weight' => 1,
            'tax_class_id' => 2,
            'meta_title' => '[Klevu][Review Test] Simple Product for Reviews',
            'meta_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'url_key' => 'klevu_simple_reviewtest_approvedwithrating' . crc32((string)rand()),
            'rating' => null,
            'review_count' => null,
        ],
    ],
    'ApprovedWithoutRating' => [
        [
            'sku' => 'klevu_simple_reviewtest_approvedwithoutrating',
            'name' => '[Klevu][Review Test] Simple Product for Reviews: Approved Without Rating',
            'description' => '[Klevu][Review Test] Simple Product for Reviews',
            'short_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'attribute_set_id' => 4,
            'website_ids' => array_filter([
                $website1->getId(),
                $website2->getId(),
            ]),
            'price' => 10,
            'weight' => 1,
            'tax_class_id' => 2,
            'meta_title' => '[Klevu][Review Test] Simple Product for Reviews',
            'meta_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'url_key' => 'klevu_simple_reviewtest_approvedwithoutrating' . crc32((string)rand()),
            'rating' => null,
            'review_count' => null,
        ],
    ],
    'PendingWithRating' => [
        [
            'sku' => 'klevu_simple_reviewtest_pendingwithrating',
            'name' => '[Klevu][Review Test] Simple Product for Reviews: Pending With Rating',
            'description' => '[Klevu][Review Test] Simple Product for Reviews',
            'short_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'attribute_set_id' => 4,
            'website_ids' => array_filter([
                $website1->getId(),
                $website2->getId(),
            ]),
            'price' => 10,
            'weight' => 1,
            'tax_class_id' => 2,
            'meta_title' => '[Klevu][Review Test] Simple Product for Reviews',
            'meta_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'url_key' => 'klevu_simple_reviewtest_pendingwithrating' . crc32((string)rand()),
            'rating' => null,
            'review_count' => null,
        ],
    ],
    'PendingWithoutRating' => [
        [
            'sku' => 'klevu_simple_reviewtest_pendingwithoutrating',
            'name' => '[Klevu][Review Test] Simple Product for Reviews: Pending Without Rating',
            'description' => '[Klevu][Review Test] Simple Product for Reviews',
            'short_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'attribute_set_id' => 4,
            'website_ids' => array_filter([
                $website1->getId(),
                $website2->getId(),
            ]),
            'price' => 10,
            'weight' => 1,
            'tax_class_id' => 2,
            'meta_title' => '[Klevu][Review Test] Simple Product for Reviews',
            'meta_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'url_key' => 'klevu_simple_reviewtest_pendingwithoutrating' . crc32((string)rand()),
            'rating' => null,
            'review_count' => null,
        ],
    ],
    'DisapprovedWithRating' => [
        [
            'sku' => 'klevu_simple_reviewtest_disapprovedwithrating',
            'name' => '[Klevu][Review Test] Simple Product for Reviews: Disapproved With Rating',
            'description' => '[Klevu][Review Test] Simple Product for Reviews',
            'short_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'attribute_set_id' => 4,
            'website_ids' => array_filter([
                $website1->getId(),
                $website2->getId(),
            ]),
            'price' => 10,
            'weight' => 1,
            'tax_class_id' => 2,
            'meta_title' => '[Klevu][Review Test] Simple Product for Reviews',
            'meta_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'url_key' => 'klevu_simple_reviewtest_disapprovedwithrating' . crc32((string)rand()),
            'rating' => null,
            'review_count' => null,
        ],
    ],
    'DisapprovedWithoutRating' => [
        [
            'sku' => 'klevu_simple_reviewtest_disapprovedwithoutrating',
            'name' => '[Klevu][Review Test] Simple Product for Reviews: Disapproved Without Rating',
            'description' => '[Klevu][Review Test] Simple Product for Reviews',
            'short_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'attribute_set_id' => 4,
            'website_ids' => array_filter([
                $website1->getId(),
                $website2->getId(),
            ]),
            'price' => 10,
            'weight' => 1,
            'tax_class_id' => 2,
            'meta_title' => '[Klevu][Review Test] Simple Product for Reviews',
            'meta_description' => '[Klevu][Review Test] Simple Product for Reviews',
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'use_config_manage_stock' => 1,
                'qty' => 100,
                'is_qty_decimal' => 0,
                'is_in_stock' => 1,
            ],
            'url_key' => 'klevu_simple_reviewtest_disapprovedwithoutrating' . crc32((string)rand()),
            'rating' => null,
            'review_count' => null,
        ],
    ],
];

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

if (!empty($PRODUCT_FIXTURES_GROUP)) {
    $fixturesToInstall = isset($fixtures[$PRODUCT_FIXTURES_GROUP])
        ? $fixtures[$PRODUCT_FIXTURES_GROUP]
        : [];
} else {
    $fixturesToInstall = array_merge([], ...$fixtures);
}

foreach ($fixturesToInstall as $fixture) {
    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);

    $productRepository->save($product);
}

$indexerFactory = $objectManager->get(IndexerFactory::class);
$indexes = [
    'catalog_product_attribute',
    'catalog_product_price',
    'inventory',
    'cataloginventory_stock',
];
foreach ($indexes as $index) {
    $indexer = $indexerFactory->create();
    try {
        $indexer->load($index);
        $indexer->reindexAll();
    } catch (\InvalidArgumentException $e) {
        // Support for older versions of Magento which may not have all indexers
        continue;
    }
}
