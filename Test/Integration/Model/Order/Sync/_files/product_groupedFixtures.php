<?php

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as IndexerProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Registry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

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

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var IndexerProcessor $indexerProcessor */
$indexerProcessor = $objectManager->get(IndexerProcessor::class);

$fixtures = [
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_grouped_child_1',
        'name' => '[Klevu] Simple Child Product 1',
        'description' => '[Klevu Test Fixtures] Simple product 1',
        'short_description' => '[Klevu Test Fixtures] Simple product 1',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 25.00,
        'special_price' => null,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product 1',
        'meta_description' => '[Klevu Test Fixtures] Simple product 1',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_grouped_child_1_' . crc32(rand()),
    ], [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_grouped_child_2',
        'name' => '[Klevu] Simple Child Product 2',
        'description' => '[Klevu Test Fixtures] Simple product 2',
        'short_description' => '[Klevu Test Fixtures] Simple product 2',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 75.00,
        'special_price' => null,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product 2',
        'meta_description' => '[Klevu Test Fixtures] Simple product 2',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 200,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_grouped_child_2_' . crc32(rand()),
    ], [
        'type_id' => 'grouped',
        'sku' => 'klevu_grouped_1',
        'name' => '[Klevu] Grouped Product 1',
        'description' => '[Klevu Test Fixtures] Grouped Product 1 Description',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'tax_class_id' => 2,
        'url_key' => 'klevu-grouped-product-1-' . crc32(rand()),
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'associated_skus' => [
            'klevu_simple_grouped_child_1',
            'klevu_simple_grouped_child_2'
        ]
    ]
];

// ------------------------------------------------------------------

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$collection = $objectManager->create(ProductResource\Collection::class);
$collection->addAttributeToFilter('sku', ['in' => array_column($fixtures, 'sku')]);
$collection->setFlag('has_stock_status_filter', true);
$collection->load();
foreach ($collection as $product) {
    $productRepository->delete($product);
}

// ------------------------------------------------------------------

// Simple products
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'simple') {
        continue;
    }

    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);
    $product->setPrice($fixture['price']);

    $product = $productRepository->save($product);
    $indexerProcessor->reindexRow($product->getId());
}

//setting up grouped product
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'grouped') {
        continue;
    }

    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);

    $product = $productRepository->save($product);
    $indexerProcessor->reindexRow($product->getId());

    $newLinks = [];
    $productLinkFactory = $objectManager->get(ProductLinkInterfaceFactory::class);

    /** @var ProductRepositoryInterface $productRepositorySimple */
    $productRepositorySimple = $objectManager->get(ProductRepositoryInterface::class);

    foreach ($fixture['associated_skus'] as $associatedSku) {
        /** @var ProductLinkInterface $productLink */
        $productLink = $productLinkFactory->create();
        $linkedProduct = $productRepositorySimple->get($associatedSku);
        $productLink->setSku($product->getSku())
            ->setLinkType('associated')
            ->setLinkedProductSku($linkedProduct->getSku())
            ->setLinkedProductType($linkedProduct->getTypeId())
            ->setPosition(1)
            ->getExtensionAttributes()
            ->setQty(1);
        $newLinks[] = $productLink;
    }
    $product->setProductLinks($newLinks);
    $product->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);
    $productRepositorySimple->save($product);
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

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
