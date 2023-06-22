<?php

use Magento\Bundle\Api\Data\LinkInterfaceFactory;
use Magento\Bundle\Api\Data\OptionInterfaceFactory;
use Magento\Bundle\Model\Product\Price as BundlePrice;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as IndexerProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Registry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

include __DIR__ . '/productFixtures_rollback.php';
require __DIR__ . '/productAttributeFixtures.php';

$objectManager = Bootstrap::getObjectManager();

/** @var IndexerProcessor $indexerProcessor */
$indexerProcessor = $objectManager->get(IndexerProcessor::class);

/** @var Website $baseWebsite */
$baseWebsite = $objectManager->create(Website::class);
$baseWebsite->load('base', 'code');

/** @var Website $website1 */
$website1 = $objectManager->create(Website::class);
$website1->load('klevu_test_website_1', 'code');

/** @var Website $website2 */
$website2 = $objectManager->create(Website::class);
$website2->load('klevu_test_website_2', 'code');

/** @var EavConfig $eavConfig */
$eavConfig = $objectManager->get(EavConfig::class);
/** @var ConfigurableOptionsFactory $configurableOptionsFactory */
$configurableOptionsFactory = $objectManager->get(ConfigurableOptionsFactory::class);

$configurableAttribute = $eavConfig->getAttribute('catalog_product', 'klevu_test_configurable');
$configurableAttributeOptions = $configurableAttribute->getOptions();

/** @var ProductLinkInterfaceFactory $productLinkFactory */
$productLinkFactory = $objectManager->get(ProductLinkInterfaceFactory::class);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$fixtures = [
    // Config Children
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_child_instock_1',
        'name' => '[Klevu] Sync Test: Child Product: In Stock 1',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 20.00,
        'special_price' => null,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_synctest_child_instock_1_' . crc32(rand()),
        'klevu_test_configurable' => $configurableAttributeOptions[1]->getValue(),
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_child_instock_2',
        'name' => '[Klevu] Sync Test: Child Product: In Stock 2',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 30.00,
        'special_price' => 7.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_synctest_child_instock_2_' . crc32(rand()),
        'klevu_test_configurable' => $configurableAttributeOptions[2]->getValue(),
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_child_oos',
        'name' => '[Klevu] Sync Test: Child Product: Out of Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 0.00,
        'special_price' => 2.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 0,
            'is_qty_decimal' => 0,
            'is_in_stock' => 0,
        ],
        'url_key' => 'klevu_simple_synctest_child_oos_' . crc32(rand()),
        'klevu_test_configurable' => $configurableAttributeOptions[3]->getValue(),
    ],

    // Configurable
    [
        'type_id' => 'configurable',
        'sku' => 'klevu_configurable_synctest_instock_childreninstock',
        'name' => '[Klevu] Sync Test: Configurable: In Stock; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => [
            $website1->getId(),
            $website2->getId(),
        ],
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_configurable_synctest_instock_childreninstock_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_child_instock_1',
            'klevu_simple_synctest_child_oos',
            'klevu_simple_synctest_child_instock_2',
        ],
    ],
    [
        'type_id' => 'configurable',
        'sku' => 'klevu_configurable_synctest_instock_childrenoos',
        'name' => '[Klevu] Sync Test: Configurable: In Stock; Children Out of Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => [
            $website1->getId(),
            $website2->getId(),
        ],
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_configurable_synctest_instock_childrenoos_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_child_oos',
        ],
    ],
    [
        'type_id' => 'configurable',
        'sku' => 'klevu_configurable_synctest_oos_childreninstock',
        'name' => '[Klevu] Sync Test: Configurable: Out of Stock; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => [
            $website1->getId(),
            $website2->getId(),
        ],
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'is_in_stock' => 0,
        ],
        'url_key' => 'klevu_configurable_synctest_oos_childreninstock_' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_synctest_child_instock_1',
            'klevu_simple_synctest_child_instock_2',
        ],
    ],

    // Bundle Children
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_bundlechild_instock_1',
        'name' => '[Klevu] Sync Test: Bundle Child Product: In Stock 1',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 50.00,
        'special_price' => null,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_synctest_bundlechild_instock_1_' . crc32(rand()),
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_bundlechild_instock_2',
        'name' => '[Klevu] Sync Test: Bundle Child Product: In Stock 2',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 100.00,
        'special_price' => 15.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_simple_synctest_bundlechild_instock_2_' . crc32(rand()),
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_bundlechild_oos',
        'name' => '[Klevu] Sync Test: Bundle Child Product: Out of Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 150.00,
        'special_price' => 1.50,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 0,
            'is_qty_decimal' => 0,
            'is_in_stock' => 0,
        ],
        'url_key' => 'klevu_simple_synctest_bundlechild_oos_' . crc32(rand()),
    ],

    // Bundle
    [
        'type_id' => 'bundle',
        'sku' => 'klevu_bundle_synctest_instock_childreninstock',
        'name' => '[Klevu] Sync Test: Bundle Product: In Stock; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_bundle_synctest_instock_childreninstock_' . crc32(rand()),
        'price_type' => BundlePrice::PRICE_TYPE_DYNAMIC,
        'child_skus' => [
            'klevu_simple_synctest_bundlechild_instock_1',
            'klevu_simple_synctest_bundlechild_instock_2',
            'klevu_simple_synctest_bundlechild_oos',
        ],
    ],
    [
        'type_id' => 'bundle',
        'sku' => 'klevu_bundle_synctest_instock_childrenoos',
        'name' => '[Klevu] Sync Test: Bundle Product: In Stock; Children Out Of Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu_bundle_synctest_instock_childrenoos_' . crc32(rand()),
        'price_type' => BundlePrice::PRICE_TYPE_DYNAMIC,
        'child_skus' => [
            'klevu_simple_synctest_bundlechild_oos',
        ],
    ],
    [
        'type_id' => 'bundle',
        'sku' => 'klevu_bundle_synctest_oos_childreninstock',
        'name' => '[Klevu] Sync Test: Bundle Product: Out Of Stock; Children In Stock',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 0,
            'is_qty_decimal' => 0,
            'is_in_stock' => 0,
        ],
        'url_key' => 'klevu_bundle_synctest_oos_childreninstock_' . crc32(rand()),
        'price_type' => BundlePrice::PRICE_TYPE_DYNAMIC,
        'child_skus' => [
            'klevu_simple_synctest_bundlechild_instock_1',
            'klevu_simple_synctest_bundlechild_instock_2',
            'klevu_simple_synctest_bundlechild_oos',
        ],
    ],
];

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

$attributeValues = [];
$productSkuToId = [];
$configurableChildSkus = [];
foreach ($fixtures as $fixture) {
    if (!isset($fixture['child_skus']) || $fixture['type_id'] !== 'configurable') {
        continue;
    }

    $configurableChildSkus[] = $fixture['child_skus'];
}
$configurableChildSkus = array_filter(array_unique(array_merge([], ...$configurableChildSkus)));

$productIds = [];

// Simple  Products
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'simple') {
        continue;
    }

    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);

    $product = $productRepository->save($product);
    $productIds[] = $product->getId();

    $indexerProcessor->reindexRow($product->getId());

    if (in_array($fixture['sku'], $configurableChildSkus, true)) {
        if (!isset($fixture['klevu_test_configurable'])) {
            throw new \LogicException(sprintf(
                'Fixture "%s" used as a configurable variant does not contain "klevu_test_configurable"',
                $fixture['sku']
            ));
        }

        $attributeValues[$fixture['sku']] = [
            'label' => 'test',
            'attribute_id' => $configurableAttribute->getId(),
            'value_index' => $fixture['klevu_test_configurable'],
        ];
        $productSkuToId[$product->getSku()] = $product->getId();
    }
}

// Configurable Products
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'configurable') {
        continue;
    }

    $childSkus = $fixture['child_skus'];
    unset($fixture['price'], $fixture['special_price'], $fixture['child_skus']);

    /** @var $product Product */
    $product = $objectManager->create(Product::class);

    $values = array_values(array_intersect_key(
        $attributeValues,
        array_fill_keys($childSkus, '')
    ));
    $associatedProductIds = array_values(array_intersect_key(
        $productSkuToId,
        array_fill_keys($childSkus, '')
    ));

    if ($values) {
        $configurableAttributesData = [
            [
                'attribute_id' => $configurableAttribute->getId(),
                'code' => $configurableAttribute->getAttributeCode(),
                'label' => $configurableAttribute->getDataUsingMethod('store_label'),
                'position' => 0,
                'values' => $values,
            ],
        ];
        $configurableOptions = $configurableOptionsFactory->create($configurableAttributesData);
        /** @var ProductExtensionInterface $extensionConfigurableAttributes */
        $extensionConfigurableAttributes = $product->getExtensionAttributes();
        $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);

        $product->setExtensionAttributes($extensionConfigurableAttributes);
    }

    $product->isObjectNew(true);
    $product->addData($fixture);

    $productRepository->cleanCache();
    $product = $productRepository->save($product);
    $productIds[] = $product->getId();
}

// Bundle Products
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'bundle') {
        continue;
    }

    $childSkus = $fixture['child_skus'];

    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);

    $product->setPriceView(1);
    $product->setSkuType(1);
    $product->setWeightType(1);
    $product->setPriceType($fixture['price_type']);
    $product->setShipmentType(0);
    if (isset($fixture['price'])) {
        $product->setPrice($fixture['price']);
    }
    $product->setBundleOptionsData(
        [
            [
                'title' => 'Bundle Product Items',
                'default_title' => 'Bundle Product Items',
                'type' => 'select',
                'required' => 1,
                'delete' => '',
            ],
        ]
    );

    $bundleSelectionsData = [];
    foreach ($childSkus as $childSku) {
        $childProduct = $productRepository->get($childSku);
        $bundleSelectionsData[] = [
            'product_id' => $childProduct->getId(),
            'selection_price_value' => $childProduct->getPrice(),
            'selection_qty' => 1,
            'selection_can_change_qty' => 1,
            'delete' => '',
        ];
    }
    $product->setBundleSelectionsData([$bundleSelectionsData]);

    if ($product->getBundleOptionsData()) {
        $options = [];
        $optionFactory = $objectManager->create(OptionInterfaceFactory::class);
        $linkFactory = $objectManager->create(LinkInterfaceFactory::class);
        foreach ($product->getBundleOptionsData() as $key => $optionData) {
            if ((bool)$optionData['delete']) {
                continue;
            }

            $option = $optionFactory->create(['data' => $optionData]);
            $option->setSku($product->getSku());
            $option->setOptionId(null);

            $links = [];
            $bundleLinks = $product->getBundleSelectionsData();
            if (empty($bundleLinks[$key])) {
                continue;
            }

            foreach ($bundleLinks[$key] as $linkData) {
                if ((bool)$linkData['delete']) {
                    continue;
                }

                $linkProduct = $productRepository->getById($linkData['product_id']);

                $link = $linkFactory->create(['data' => $linkData]);
                $link->setSku($linkProduct->getSku());
                $link->setQty($linkData['selection_qty']);
                $link->setPrice($linkData['selection_price_value']);
                if (isset($linkData['selection_can_change_qty'])) {
                    $link->setCanChangeQuantity($linkData['selection_can_change_qty']);
                }
                $links[] = $link;
            }

            $option->setProductLinks($links);
            $options[] = $option;
        }

        $extensionAttributes = $product->getExtensionAttributes();
        $extensionAttributes->setBundleProductOptions($options);
        $product->setExtensionAttributes($extensionAttributes);
    }

    $product = $productRepository->save($product);
    $productIds[] = $product->getId();
    $productRepository->cleanCache();
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

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
