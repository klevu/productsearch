<?php

use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as IndexerProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\StoreManagerInterface;
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
/** @var ProductLinkInterfaceFactory $productLinkFactory */
$productLinkFactory = $objectManager->get(ProductLinkInterfaceFactory::class);

// -------------------------------------------------------------------------------------

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$skusToDelete = [
    'klevu_grouped_product_test',
    'klevu_grouped_product_test_simple_1',
    'klevu_grouped_product_test_simple_2',
    'klevu_grouped_product_test_simple_3',
    'klevu_grouped_product_test_simple_4',
];
foreach ($skusToDelete as $skuToDelete) {
    try {
        $groupedProduct = $productRepository->get($skuToDelete);
        $productRepository->delete($groupedProduct);
    } catch (NoSuchEntityException $e) {
        // This is fine
    }
}

// -------------------------------------------------------------------------------------

$simpleProduct1 = $objectManager->create(Product::class);
$simpleProduct1->isObjectNew(true);
$simpleProduct1->addData([
    'sku' => 'klevu_grouped_product_test_simple_1',
    'type_id' => 'simple',
    'name' => '[Klevu] Grouped Product Test 1 (Simple)',
    'description' => '[Klevu Test Fixtures] Simple child for assigned grouped product 1',
    'short_description' => '[Klevu Test Fixtures] Simple child for assigned grouped product 1',
    'attribute_set_id' => 4,
    'website_ids' => array_filter([
        $baseWebsite->getId(),
        $website1->getId(),
        $website2->getId(),
    ]),
    'price' => 500.00,
    'special_price' => 249.99,
    'weight' => 1,
    'tax_class_id' => 2,
    'meta_title' => '[Klevu] Grouped Product Test 1 (Simple)',
    'meta_description' => '[Klevu Test Fixtures] Simple child for assigned grouped product 1',
    'visibility' => Visibility::VISIBILITY_BOTH,
    'status' => Status::STATUS_ENABLED,
    'stock_data' => [
        'use_config_manage_stock'   => 1,
        'qty'                       => 100,
        'is_qty_decimal'            => 0,
        'is_in_stock'               => 1,
    ],
    'url_key' => 'klevu-grouped-product-test-simple-1-'. crc32(rand()),
]);
$simpleProduct1 = $productRepository->save($simpleProduct1);

$simpleProduct2 = $objectManager->create(Product::class);
$simpleProduct2->isObjectNew(true);
$simpleProduct2->addData([
    'sku' => 'klevu_grouped_product_test_simple_2',
    'type_id' => 'simple',
    'name' => '[Klevu] Grouped Product Test 2 (Simple)',
    'description' => '[Klevu Test Fixtures] Simple child for assigned grouped product 2',
    'short_description' => '[Klevu Test Fixtures] Simple child for assigned grouped product 2',
    'attribute_set_id' => 4,
    'website_ids' => array_filter([
        $baseWebsite->getId(),
        $website1->getId(),
        $website2->getId(),
    ]),
    'price' => 10.00,
    'special_price' => 4.99,
    'weight' => 1,
    'tax_class_id' => 2,
    'meta_title' => '[Klevu] Grouped Product Test 2 (Simple)',
    'meta_description' => '[Klevu Test Fixtures] Simple child for assigned grouped product 2',
    'visibility' => Visibility::VISIBILITY_BOTH,
    'status' => Status::STATUS_ENABLED,
    'stock_data' => [
        'use_config_manage_stock'   => 1,
        'qty'                       => 100,
        'is_qty_decimal'            => 0,
        'is_in_stock'               => 1,
    ],
    'url_key' => 'klevu-grouped-product-test-simple-2-'. crc32(rand()),
]);
$simpleProduct2 = $productRepository->save($simpleProduct2);

$simpleProduct3 = $objectManager->create(Product::class);
$simpleProduct3->isObjectNew(true);
$simpleProduct3->addData([
    'sku' => 'klevu_grouped_product_test_simple_3',
    'type_id' => 'simple',
    'name' => '[Klevu] Grouped Product Test 3 (Simple)',
    'description' => '[Klevu Test Fixtures] Simple child for assigned grouped product 3',
    'short_description' => '[Klevu Test Fixtures] Simple child for assigned grouped product 3',
    'attribute_set_id' => 4,
    'website_ids' => array_filter([
        $baseWebsite->getId(),
        $website1->getId(),
        $website2->getId(),
    ]),
    'price' => 1000.00,
    'special_price' => 499.99,
    'weight' => 1,
    'tax_class_id' => 2,
    'meta_title' => '[Klevu] Grouped Product Test 3 (Simple)',
    'meta_description' => '[Klevu Test Fixtures] Simple child for assigned grouped product 3',
    'visibility' => Visibility::VISIBILITY_BOTH,
    'status' => Status::STATUS_ENABLED,
    'stock_data' => [
        'use_config_manage_stock'   => 1,
        'qty'                       => 100,
        'is_qty_decimal'            => 0,
        'is_in_stock'               => 1,
    ],
    'url_key' => 'klevu-grouped-product-test-simple-3-'. crc32(rand()),
]);
$simpleProduct3 = $productRepository->save($simpleProduct3);

$simpleProduct4 = $objectManager->create(Product::class);
$simpleProduct4->isObjectNew(true);
$simpleProduct4->addData([
    'sku' => 'klevu_grouped_product_test_simple_4',
    'type_id' => 'simple',
    'name' => '[Klevu] Grouped Product Test 4 (Simple)',
    'description' => '[Klevu Test Fixtures] Simple child for assigned grouped product 4',
    'short_description' => '[Klevu Test Fixtures] Simple child for assigned grouped product 4',
    'attribute_set_id' => 4,
    'website_ids' => array_filter([
        $baseWebsite->getId(),
        $website1->getId(),
        $website2->getId(),
    ]),
    'price' => 1.00,
    'special_price' => 0.49,
    'weight' => 1,
    'tax_class_id' => 2,
    'meta_title' => '[Klevu] Grouped Product Test 4 (Simple)',
    'meta_description' => '[Klevu Test Fixtures] Simple child for assigned grouped product 4',
    'visibility' => Visibility::VISIBILITY_BOTH,
    'status' => Status::STATUS_ENABLED,
    'stock_data' => [
        'use_config_manage_stock'   => 1,
        'qty'                       => 0,
        'is_qty_decimal'            => 0,
        'is_in_stock'               => 0,
    ],
    'url_key' => 'klevu-grouped-product-test-simple-4-'. crc32(rand()),
]);
$simpleProduct4 = $productRepository->save($simpleProduct4);

$groupedProduct = $objectManager->create(Product::class);
$groupedProduct->isObjectNew(true);
$groupedProduct->addData([
    'sku' => 'klevu_grouped_product_test',
    'type_id' => 'grouped',
    'name' => '[Klevu] Grouped Product Test',
    'description' => '[Klevu Test Fixtures] assigned grouped product',
    'short_description' => '[Klevu Test Fixtures] assigned grouped product',
    'attribute_set_id' => 4,
    'website_ids' => array_filter([
        $baseWebsite->getId(),
        $website1->getId(),
        $website2->getId(),
    ]),
    'price' => 100.00,
    'special_price' => 49.99,
    'weight' => 1,
    'tax_class_id' => 2,
    'meta_title' => '[Klevu] Grouped Product Test',
    'meta_description' => '[Klevu Test Fixtures] assigned grouped product',
    'visibility' => Visibility::VISIBILITY_BOTH,
    'status' => Status::STATUS_ENABLED,
    'stock_data' => [
        'use_config_manage_stock'   => 1,
        'qty'                       => 100,
        'is_qty_decimal'            => 0,
        'is_in_stock'               => 1,
    ],
    'url_key' => 'klevu-grouped-product-test-'. crc32(rand()),
]);

$groupedProduct = $productRepository->save($groupedProduct);
$productRepository->cleanCache();

$simpleProductLink1 = $productLinkFactory->create();
$simpleProductLink1->setSku($groupedProduct->getSku())
    ->setLinkType('associated')
    ->setLinkedProductSku($simpleProduct1->getSku())
    ->setLinkedProductType($simpleProduct1->getTypeId())
    ->setPosition(1)
    ->getExtensionAttributes()
    ->setQty(1);

$simpleProductLink2 = $productLinkFactory->create();
$simpleProductLink2->setSku($groupedProduct->getSku())
    ->setLinkType('associated')
    ->setLinkedProductSku($simpleProduct2->getSku())
    ->setLinkedProductType($simpleProduct2->getTypeId())
    ->setPosition(1)
    ->getExtensionAttributes()
    ->setQty(1);

$simpleProductLink3 = $productLinkFactory->create();
$simpleProductLink3->setSku($groupedProduct->getSku())
    ->setLinkType('associated')
    ->setLinkedProductSku($simpleProduct3->getSku())
    ->setLinkedProductType($simpleProduct3->getTypeId())
    ->setPosition(1)
    ->getExtensionAttributes()
    ->setQty(1);

$simpleProductLink4 = $productLinkFactory->create();
$simpleProductLink4->setSku($groupedProduct->getSku())
    ->setLinkType('associated')
    ->setLinkedProductSku($simpleProduct4->getSku())
    ->setLinkedProductType($simpleProduct4->getTypeId())
    ->setPosition(1)
    ->getExtensionAttributes()
    ->setQty(1);

$groupedProduct->setProductLinks([
    $simpleProductLink1,
    $simpleProductLink2,
    $simpleProductLink3,
    $simpleProductLink4,
]);

$indexerProcessor->reindexRow($groupedProduct->getId());
$groupedProduct = $productRepository->save($groupedProduct);
$productRepository->cleanCache();

// -------------------------------------------------------------------------------------

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

// -------------------------------------------------------------------------------------

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
