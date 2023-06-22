<?php

use Magento\Bundle\Model\Product\Price;
use Magento\Bundle\Api\Data\LinkInterfaceFactory;
use Magento\Bundle\Api\Data\OptionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as IndexerProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Registry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

include __DIR__ . '/bundleProduct_DynamicPrice_Fixtures_rollback.php';

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

// -------------------------------------------------------------------------------------

$simpleProduct1 = $objectManager->create(Product::class);
$simpleProduct1->isObjectNew(true);
$simpleProduct1->addData([
    'sku' => 'klevu_bundle_product_test_simple_1',
    'type_id' => 'simple',
    'name' => '[Klevu] Product Test (Simple) 1',
    'description' => '[Klevu Test Fixtures] Simple child for assigned bundle product 1',
    'short_description' => '[Klevu Test Fixtures] Simple child for assigned bundle product 1',
    'attribute_set_id' => 4,
    'website_ids' => array_filter([
        $baseWebsite->getId(),
        $website1->getId(),
        $website2->getId(),
    ]),
    'price' => 20.00,
    'special_price' => 5.99,
    'weight' => 1,
    'tax_class_id' => 0,
    'meta_title' => '[Klevu] bundle Product Test (Simple) 1',
    'meta_description' => '[Klevu Test Fixtures] Simple child for assigned bundle product 1',
    'visibility' => Visibility::VISIBILITY_BOTH,
    'status' => Status::STATUS_ENABLED,
    'stock_data' => [
        'use_config_manage_stock'   => 1,
        'qty'                       => 100,
        'is_qty_decimal'            => 0,
        'is_in_stock'               => 1,
    ],
    'url_key' => 'klevu-bundle-product-test-simple-1-'. crc32(rand()),
]);
$simpleProduct1 = $productRepository->save($simpleProduct1);

$simpleProduct2 = $objectManager->create(Product::class);
$simpleProduct2->isObjectNew(true);
$simpleProduct2->addData([
    'sku' => 'klevu_bundle_product_test_simple_2',
    'type_id' => 'simple',
    'name' => '[Klevu] Product Test (Simple) 2',
    'description' => '[Klevu Test Fixtures] Simple child for assigned bundle product 2',
    'short_description' => '[Klevu Test Fixtures] Simple child for assigned bundle product 2',
    'attribute_set_id' => 4,
    'website_ids' => array_filter([
        $baseWebsite->getId(),
        $website1->getId(),
        $website2->getId(),
    ]),
    'price' => 30.00,
    'special_price' => 20.00,
    'weight' => 1,
    'tax_class_id' => 0,
    'meta_title' => '[Klevu] bundle Product Test (Simple) 2',
    'meta_description' => '[Klevu Test Fixtures] Simple child for assigned bundle product 2',
    'visibility' => Visibility::VISIBILITY_BOTH,
    'status' => Status::STATUS_ENABLED,
    'stock_data' => [
        'use_config_manage_stock'   => 1,
        'qty'                       => 100,
        'is_qty_decimal'            => 0,
        'is_in_stock'               => 1,
    ],
    'url_key' => 'klevu-bundle-product-test-simple-2-'. crc32(rand()),
]);
$simpleProduct2 = $productRepository->save($simpleProduct2);

$bundleProduct = $objectManager->create(Product::class);
$bundleProduct->isObjectNew(true);
$bundleProduct->addData([
    'sku' => 'klevu_bundle_product_test',
    'type_id' => 'bundle',
    'name' => '[Klevu] Bundle Product Test',
    'description' => '[Klevu Test Fixtures] assigned bundle product',
    'short_description' => '[Klevu Test Fixtures] assigned bundle product',
    'attribute_set_id' => 4,
    'website_ids' => array_filter([
        $baseWebsite->getId(),
        $website1->getId(),
        $website2->getId(),
    ]),
    'price' => null, // dynamically priced bundles don't have prices set
    'special_price' => null, // dynamically priced bundles don't have prices set
    'weight' => 1,
    'tax_class_id' => 0,
    'meta_title' => '[Klevu] Bundle Product Test',
    'meta_description' => '[Klevu Test Fixtures] assigned bundle product',
    'visibility' => Visibility::VISIBILITY_BOTH,
    'status' => Status::STATUS_ENABLED,
    'stock_data' => [
        'use_config_manage_stock'   => 1,
        'qty'                       => 100,
        'is_qty_decimal'            => 0,
        'is_in_stock'               => 1,
    ],
    'url_key' => 'klevu-bundle-product-test-' . crc32(rand()),
]);
$bundleProduct->setPriceView(1);
$bundleProduct->setSkuType(1);
$bundleProduct->setWeightType(1);
$bundleProduct->setPriceType(Price::PRICE_TYPE_DYNAMIC);
$bundleProduct->setShipmentType(0);
$bundleProduct->setBundleOptionsData(
    [
        // Required "Drop-down" option
        [
            'title' => 'Option 1',
            'default_title' => 'Option 1',
            'type' => 'select',
            'required' => 1,
            'position' => 1,
            'delete' => '',
        ]
    ]
);
$bundleProduct->setBundleSelectionsData(
    [
        [
            [
                'product_id' => $simpleProduct1->getId(),
                'selection_qty' => 1,
                'selection_can_change_qty' => 1,
                'delete' => '',
            ],
            [
                'product_id' => $simpleProduct2->getId(),
                'selection_qty' => 1,
                'selection_can_change_qty' => 1,
                'delete' => '',
            ],
        ],
    ]
);


if ($bundleProduct->getBundleOptionsData()) {
    $options = [];
    foreach ($bundleProduct->getBundleOptionsData() as $key => $optionData) {
        if (!(bool)$optionData['delete']) {
            $option = $objectManager->create(OptionInterfaceFactory::class)
                ->create(['data' => $optionData]);
            $option->setSku($bundleProduct->getSku());
            $option->setOptionId(null);

            $links = [];
            $bundleLinks = $bundleProduct->getBundleSelectionsData();
            if (!empty($bundleLinks[$key])) {
                foreach ($bundleLinks[$key] as $linkData) {
                    if (!(bool)$linkData['delete']) {
                        /** @var \Magento\Bundle\Api\Data\LinkInterface $link */
                        $link = $objectManager->create(LinkInterfaceFactory::class)
                            ->create(['data' => $linkData]);
                        $linkProduct = $productRepository->getById($linkData['product_id']);
                        $link->setSku($linkProduct->getSku());
                        $link->setQty($linkData['selection_qty']);
                        if (isset($linkData['selection_can_change_qty'])) {
                            $link->setCanChangeQuantity($linkData['selection_can_change_qty']);
                        }
                        $links[] = $link;
                    }
                }
                $option->setProductLinks($links);
                $options[] = $option;
            }
        }
    }
    $extension = $bundleProduct->getExtensionAttributes();
    $extension->setBundleProductOptions($options);
    $bundleProduct->setExtensionAttributes($extension);
}

$productRepository->save($bundleProduct, true);
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
