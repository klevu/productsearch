<?php

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\Website;

// Because we don't always call these fixtures from annotations, the rollback fails to
//  run when a test fails, leaving data in the db
include 'productFixtures_ratings_rollback.php';

$objectManager = Bootstrap::getObjectManager();

/** @var Website $baseWebsite */
$baseWebsite = $objectManager->create(Website::class);
$baseWebsite->load('base', 'code');

/** @var Website $website1 */
$website1 = $objectManager->create(Website::class);
$website1->load('klevu_test_website_1', 'code');
$website1Id = (int)$website1->getId();
$store1 = $website1->getDefaultStore();
$store1Id = (int)$store1->getId();

/** @var Website $website2 */
$website2 = $objectManager->create(Website::class);
$website2->load('klevu_test_website_2', 'code');
$website2Id = (int)$website2->getId();
$store2 = $website2->getDefaultStore();
$store2Id = (int)$store2->getId();

$fixtures = [
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_ratingtest_without_rating_without_rating_count',
        'name' => '[Klevu][Rating Test] Simple Product (Without Rating; Without Rating Count)',
        'description' => '[Klevu Test Fixtures][Rating Test] Simple Product (Without Rating; Without Rating Count)',
        'short_description' => '[Klevu Test Fixtures][Rating Test] Simple Product (Without Rating; Without Rating Count)',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu][Rating Test] Simple Product (Without Rating; Without Rating Count)',
        'meta_description' => '[Klevu Test Fixtures][Rating Test] Simple Product (Without Rating; Without Rating Count)',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_ratingtest_without_rating_without_rating_count' . crc32((string)rand()),
        'rating' => null,
        'review_count' => null,
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_ratingtest_without_rating_with_rating_count',
        'name' => '[Klevu][Rating Test] Simple Product (Without Rating; With Rating Count)',
        'description' => '[Klevu Test Fixtures][Rating Test] Simple Product (Without Rating; With Rating Count)',
        'short_description' => '[Klevu Test Fixtures][Rating Test] Simple Product (Without Rating; With Rating Count)',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu][Rating Test] Simple Product (Without Rating; With Rating Count)',
        'meta_description' => '[Klevu Test Fixtures][Rating Test] Simple Product (Without Rating; With Rating Count)',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_ratingtest_without_rating_with_rating_count' . crc32((string)rand()),
        'rating' => null,
        'review_count' => 10,
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_ratingtest_with_rating_without_rating_count',
        'name' => '[Klevu][Rating Test] Simple Product (With Rating; Without Rating Count)',
        'description' => '[Klevu Test Fixtures][Rating Test] Simple Product (With Rating; Without Rating Count)',
        'short_description' => '[Klevu Test Fixtures][Rating Test] Simple Product (With Rating; Without Rating Count)',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu][Rating Test] Simple Product (With Rating; Without Rating Count)',
        'meta_description' => '[Klevu Test Fixtures][Rating Test] Simple Product (With Rating; Without Rating Count)',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_ratingtest_with_rating_without_rating_count' . crc32((string)rand()),
        'rating' => 75,
        'review_count' => null,
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_ratingtest_with_rating_with_rating_count',
        'name' => '[Klevu][Rating Test] Simple Product (With Rating; With Rating Count)',
        'description' => '[Klevu Test Fixtures][Rating Test] Simple Product (With Rating; Wit Rating Count)',
        'short_description' => '[Klevu Test Fixtures][Rating Test] Simple Product (With Rating; With Rating Count)',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu][Rating Test] Simple Product (With Rating; With Rating Count)',
        'meta_description' => '[Klevu Test Fixtures][Rating Test] Simple Product (With Rating; With Rating Count)',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_ratingtest_with_rating_with_rating_count' . crc32((string)rand()),
        'rating' => 42,
        'review_count' => 16,
    ],
];

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

foreach ($fixtures as $fixture) {
    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);

    /** @var Product $product */
    $product = $productRepository->save($product);

    if (!empty($fixture['rating'])) {
        $product->addAttributeUpdate('rating', $fixture['rating'], $store1Id);
        $product->addAttributeUpdate('rating', $fixture['rating'], $store2Id);
    }
    if (!empty($fixture['review_count'])) {
        $product->addAttributeUpdate('review_count', $fixture['review_count'], $store1Id);
        $product->addAttributeUpdate('review_count', $fixture['review_count'], $store2Id);
    }
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

$productRepository->cleanCache();
