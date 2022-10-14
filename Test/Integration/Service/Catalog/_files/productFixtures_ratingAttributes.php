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
        'sku' => 'klevu_simple_with_rating_with_reviewcount_allstores',
        'name' => '[Klevu] Simple Product: With Rating; With Review Count (All Stores)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1Id,
            $website2Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_with_rating_with_reviewcount_allstores_' . crc32(rand()),
        'store_data' => [
            $store1Id => [
                'rating' => 50,
                'review_count' => 10,
            ],
            $store2Id => [
                'rating' => 90,
                'review_count' => 2,
            ],
        ],
    ],
    [
        'sku' => 'klevu_simple_with_rating_with_reviewcount_store1',
        'name' => '[Klevu] Simple Product: With Rating; With Review Count (Store #1)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_with_rating_with_reviewcount_store1_' . crc32(rand()),
        'store_data' => [
            $store1Id => [
                'rating' => 50,
                'review_count' => 10,
            ],
        ],
    ],
    [
        'sku' => 'klevu_simple_with_rating_with_reviewcount_store2',
        'name' => '[Klevu] Simple Product: With Rating; With Review Count (Store #2)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website2Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_with_rating_with_reviewcount_store2_' . crc32(rand()),
        'store_data' => [
            $store2Id => [
                'rating' => 90,
                'review_count' => 2,
            ],
        ],
    ],
    [
        'sku' => 'klevu_simple_with_rating_without_reviewcount_allstores',
        'name' => '[Klevu] Simple Product: With Rating; Without Review Count (All Stores)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1Id,
            $website2Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_with_rating_without_reviewcount_allstores_' . crc32(rand()),
        'store_data' => [
            $store1Id => [
                'rating' => 50,
            ],
            $store2Id => [
                'rating' => 90,
            ],
        ],
    ],
    [
        'sku' => 'klevu_simple_with_rating_without_reviewcount_store1',
        'name' => '[Klevu] Simple Product: With Rating; Without Review Count (Store #1)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_with_rating_without_reviewcount_store1_' . crc32(rand()),
        'store_data' => [
            $store1Id => [
                'rating' => 50,
            ],
        ],
    ],
    [
        'sku' => 'klevu_simple_with_rating_without_reviewcount_store2',
        'name' => '[Klevu] Simple Product: With Rating; Without Review Count (Store #2)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website2Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_with_rating_without_reviewcount_store2_' . crc32(rand()),
        'store_data' => [
            $store2Id => [
                'rating' => 90,
            ],
        ],
    ],
    [
        'sku' => 'klevu_simple_without_rating_with_reviewcount_allstores',
        'name' => '[Klevu] Simple Product: Without Rating; With Review Count (All Stores)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1Id,
            $website2Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_without_rating_with_reviewcount_allstores_' . crc32(rand()),
        'store_data' => [
            $store1Id => [
                'review_count' => 10,
            ],
            $store2Id => [
                'review_count' => 2,
            ],
        ],
    ],
    [
        'sku' => 'klevu_simple_without_rating_with_reviewcount_store1',
        'name' => '[Klevu] Simple Product: Without Rating; With Review Count (Store #1)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_without_rating_with_reviewcount_store1_' . crc32(rand()),
        'store_data' => [
            $store1Id => [
                'review_count' => 10,
            ],
        ],
    ],
    [
        'sku' => 'klevu_simple_without_rating_with_reviewcount_store2',
        'name' => '[Klevu] Simple Product: Without Rating; With Review Count (Store #2)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website2Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_without_rating_with_reviewcount_store2_' . crc32(rand()),
        'store_data' => [
            $store2Id => [
                'review_count' => 2,
            ],
        ],
    ],
    [
        'sku' => 'klevu_simple_without_rating_without_reviewcount_allstores',
        'name' => '[Klevu] Simple Product: Without Rating; Without Review Count (All Stores)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1Id,
            $website2Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_without_rating_without_reviewcount_allstores_' . crc32(rand()),
        'store_data' => [],
    ],
    [
        'sku' => 'klevu_simple_without_rating_without_reviewcount_store1',
        'name' => '[Klevu] Simple Product: Without Rating; Without Review Count (Store #1)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_without_rating_without_reviewcount_store1_' . crc32(rand()),
        'store_data' => [],
    ],
    [
        'sku' => 'klevu_simple_without_rating_without_reviewcount_store2',
        'name' => '[Klevu] Simple Product: Without Rating; Without Review Count (Store #2)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website2Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_without_rating_without_reviewcount_store2_' . crc32(rand()),
        'store_data' => [],
    ],
    [
        'sku' => 'klevu_simple_with_rating_with_reviewcount_globalscope',
        'name' => '[Klevu] Simple Product: With Rating; With Review Count (Global Scope)',
        'description' => '[Klevu Test Fixtures] Simple product',
        'short_description' => '[Klevu Test Fixtures] Simple product',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1Id,
            $website2Id,
        ]),
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product',
        'meta_description' => '[Klevu Test Fixtures] Simple product',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_with_rating_with_reviewcount_allstores_' . crc32(rand()),
        'store_data' => [
            \Magento\Store\Model\Store::DEFAULT_STORE_ID => [
                'rating' => 11,
                'review_count' => 20,
            ],
        ],
    ],
];

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

foreach ($fixtures as $fixture) {
    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);

    $storeData = isset($fixture['store_data']) ? $fixture['store_data'] : [];
    unset($fixture['store_data']);

    $product->addData($fixture);
    if (!empty($storeData[\Magento\Store\Model\Store::DEFAULT_STORE_ID])) {
        foreach ($storeData[\Magento\Store\Model\Store::DEFAULT_STORE_ID] as $attributeCode => $value) {
            $product->setData($attributeCode, $value);
        }
    }

    /** @var Product $product */
    $product = $productRepository->save($product);

    if (!empty($storeData[$store1Id])) {
        foreach ($storeData[$store1Id] as $attributeCode => $value) {
            $product->addAttributeUpdate($attributeCode, $value, $store1Id);
        }
    }

    if (!empty($storeData[$store2Id])) {
        foreach ($storeData[$store2Id] as $attributeCode => $value) {
            $product->addAttributeUpdate($attributeCode, $value, $store2Id);
        }
    }
}
$productRepository->cleanCache();

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
