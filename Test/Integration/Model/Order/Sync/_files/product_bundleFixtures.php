<?php

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\Data\LinkInterfaceFactory;
use Magento\Bundle\Api\Data\OptionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
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
        'sku' => 'klevu_simple_bundle_child_1',
        'name' => '[Klevu] Simple Child Product 1',
        'description' => '[Klevu Test Fixtures] Simple product 1',
        'short_description' => '[Klevu Test Fixtures] Simple product 1',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 15.00,
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
        'url_key' => 'klevu_simple_bundle_child_1_' . crc32(rand()),
    ], [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_bundle_child_2',
        'name' => '[Klevu] Simple Child Product 2',
        'description' => '[Klevu Test Fixtures] Simple product 2',
        'short_description' => '[Klevu Test Fixtures] Simple product 2',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 30.00,
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
        'url_key' => 'klevu_simple_bundle_child_2_' . crc32(rand()),
    ], [
        'type_id' => 'bundle',
        'sku' => 'klevu_bundle_1',
        'name' => '[Klevu] Bundle Product 1',
        'description' => '[Klevu Test Fixtures] Bundle Product 1 Description',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 60.00,
        'special_price' => null,
        'weight' => 12,
        'meta_title' => '[Klevu] Bundle Product Test',
        'meta_description' => '[Klevu Test Fixtures] assigned bundle product',
        'tax_class_id' => 2,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu-bundle-product-test-' . crc32(rand()),
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'associated_skus' => [
            'klevu_simple_bundle_child_1',
            'klevu_simple_bundle_child_2'
        ],
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

//setting up bundle product
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'bundle') {
        continue;
    }

    /** @var $bundleProduct Product */
    $bundleProduct = $objectManager->create(Product::class);
    $bundleProduct->isObjectNew(true);
    $bundleProduct->addData($fixture);

    /** @var ProductRepositoryInterface $productRepositorySimple */
    $productRepositorySimple = $objectManager->get(ProductRepositoryInterface::class);
    $linkedProduct1 = $productRepositorySimple->get($fixture['associated_skus'][0]);
    $linkedProduct2 = $productRepositorySimple->get($fixture['associated_skus'][1]);

    $bundleProduct->setPriceView(0);
    $bundleProduct->setSkuType(1);
    $bundleProduct->setWeightType(1);
    $bundleProduct->setPriceType(\Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC);
    $bundleProduct->setShipmentType(1);
    $bundleProduct->setBundleOptionsData(
            [
                [
                    'title' => 'Bundle Product Items',
                    'default_title' => 'Bundle Product Items',
                    'type' => 'select',
                    'required' => 1,
                    'delete' => '',
                ],
                [
                    'title' => 'Bundle Product Items 2',
                    'default_title' => 'Bundle Product Items 2',
                    'type' => 'select',
                    'required' => 1,
                    'delete' => '',
                ],
            ]
        );
    $bundleProduct->setBundleSelectionsData(
            [
                [
                    [
                        'product_id' => $linkedProduct1->getId(),
                        'selection_price_value' => 10.00,
                        'selection_qty' => 1,
                        'selection_can_change_qty' => 1,
                        'delete' => '',
                    ],
                ],
                [
                    [
                        'product_id' => $linkedProduct2->getId(),
                        'selection_price_value' => 20.00,
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
                            /** @var LinkInterface$link */
                            $link = $objectManager->create(LinkInterfaceFactory::class)
                                ->create(['data' => $linkData]);
                            $linkProduct = $productRepository->getById($linkData['product_id']);
                            $link->setSku($linkProduct->getSku());
                            $link->setQty($linkData['selection_qty']);
                            $link->setPrice($linkData['selection_price_value']);
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
}//end bundle product

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
