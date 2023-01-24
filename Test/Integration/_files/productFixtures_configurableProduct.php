<?php

/** @noinspection PhpDeprecationInspection */

/** @noinspection PhpUnhandledExceptionInspection */

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as IndexerProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Registry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/../Model/Product/_files/productAttributeFixtures.php';

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

/** @var ProductResource $productResource */
$productResource = $objectManager->get(ProductResource::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var IndexerProcessor $indexerProcessor */
$indexerProcessor = $objectManager->get(IndexerProcessor::class);

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$defaultStoreView = $storeManager->getDefaultStoreView();

/** @var EavConfig $eavConfig */
$eavConfig = $objectManager->get(EavConfig::class);
/** @var ConfigurableOptionsFactory $configurableOptionsFactory */
$configurableOptionsFactory = $objectManager->get(ConfigurableOptionsFactory::class);

$configurableAttribute = $eavConfig->getAttribute('catalog_product', 'klevu_test_configurable');
$configurableAttributeOptions = $configurableAttribute->getOptions();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$attributeValues = [];
$productSkuToId = [];

$fixtures = [
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_child_1',
        'name' => '[Klevu] Simple Child Product 1',
        'description' => '[Klevu Test Fixtures] Simple product 1 (Enabled; Visibility None)',
        'short_description' => '[Klevu Test Fixtures] Simple product 1',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 99.99,
        'special_price' => 49.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Child Product 1',
        'meta_description' => '[Klevu Test Fixtures] Simple child product 1',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu-simple-child-product-1-' . crc32(rand()),
        'klevu_test_configurable' => $configurableAttributeOptions[1]->getValue(),
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_child_2',
        'name' => '[Klevu] Simple Child Product 2 [OOS]',
        'description' => '[Klevu Test Fixtures] Simple product 2 (Enabled; Visibility None; OOS)',
        'short_description' => '[Klevu Test Fixtures] Simple child product 2',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 89.99,
        'special_price' => 39.99,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Child Product 2',
        'meta_description' => '[Klevu Test Fixtures] Simple child product 2',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 0,
        ],
        'url_key' => 'klevu-simple-child-product-2-' . crc32(rand()),
        'klevu_test_configurable' => $configurableAttributeOptions[2]->getValue(),
    ],
    [
        'type_id' => 'configurable',
        'sku' => 'klevu_configurable_1',
        'name' => '[Klevu] Configurable Product 1',
        'description' => '[Klevu Test Fixtures] Configurable product 1',
        'short_description' => 'No children',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Configurable Product 1',
        'meta_description' => '[Klevu Test Fixtures] Configurable product 1',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu-configurable-product-1-' . crc32(rand()),
        'child_skus' => [
            'klevu_simple_child_1',
            'klevu_simple_child_2',
        ],
    ]
];

$collection = $objectManager->create(ProductResource\Collection::class);
$collection->addAttributeToFilter('sku', ['in' => array_column($fixtures, 'sku')]);
$collection->setFlag('has_stock_status_filter', true);
$collection->load();
foreach ($collection as $product) {
    $productRepository->delete($product);
}

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

    if (0 === strpos($fixture['sku'], 'klevu_simple_child_')) {
        $attributeValues[$fixture['sku']] = [
            'label' => 'test',
            'attribute_id' => $configurableAttribute->getId(),
            'value_index' => $fixture['klevu_test_configurable'],
        ];
        $productSkuToId[$product->getSku()] = $product->getId();
    }
}

// Configurable Setup
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

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
