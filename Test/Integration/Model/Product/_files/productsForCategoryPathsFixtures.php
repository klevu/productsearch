<?php

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexerProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Registry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\Website;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;

require __DIR__ . '/productAttributeFixtures.php';

$objectManager = Bootstrap::getObjectManager();

/** @var ProductResource $productResource */
$productResource = $objectManager->get(ProductResource::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var IndexerFactory $indexerFactory */
$indexerFactory = $objectManager->get(IndexerFactory::class);
/** @var PriceIndexerProcessor $priceIndexerProcessor */
$priceIndexerProcessor = $objectManager->get(PriceIndexerProcessor::class);
/** @var UrlRewriteCollectionFactory $urlRewriteCollectionFactory */
$urlRewriteCollectionFactory = $objectManager->get(UrlRewriteCollectionFactory::class);

/** @var Website $baseWebsite */
$baseWebsite = $objectManager->create(Website::class);
$baseWebsite->load('base', 'code');

/** @var EavConfig $eavConfig */
$eavConfig = $objectManager->get(EavConfig::class);
/** @var ConfigurableOptionsFactory $configurableOptionsFactory */
$configurableOptionsFactory = $objectManager->get(ConfigurableOptionsFactory::class);

// ------------------------------------------------------------------

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var UrlRewriteCollection $urlRewriteCollection */
$urlRewriteCollection = $urlRewriteCollectionFactory->create();
$urlRewriteCollection->addFieldToFilter('entity_type', 'product');
$urlRewriteCollection->addFieldToFilter('request_path', ['like' => 'klevu-product-test-category-paths-test-%']);
foreach ($urlRewriteCollection as $urlRewrite) {
    /** @var UrlRewrite $urlRewrite */
    $urlRewrite->delete();
}

// ------------------------------------------------------------------

$configurableAttribute = $eavConfig->getAttribute('catalog_product', 'klevu_test_configurable');
$configurableAttributeOptions = $configurableAttribute->getOptions();

$fixtures = [
    [
        'sku' => 'klevu-category-paths-test-child-no-categories',
        'name' => '[Klevu][Product Test] Category Paths: Child No Categories',
        'description' => '',
        'short_description' => '',
        'attribute_set_id' => 4,
        'website_ids' => [
            $baseWebsite->getId(),
        ],
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '',
        'meta_description' => '',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu-product-test-category-paths-test-child-no-categories',
        'klevu_test_configurable' => $configurableAttributeOptions[1]->getValue(),
    ], [
        'sku' => 'klevu-category-paths-test-child-with-categories',
        'name' => '[Klevu][Product Test] Category Paths: Child With Categories',
        'description' => '',
        'short_description' => '',
        'attribute_set_id' => 4,
        'website_ids' => [
            $baseWebsite->getId(),
        ],
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '',
        'meta_description' => '',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu-product-test-category-paths-test-child-with-categories',
        'klevu_test_configurable' => $configurableAttributeOptions[2]->getValue(),
    ], [
        'sku' => 'klevu-category-paths-test-standalone',
        'name' => '[Klevu][Product Test] Category Paths: Standalone',
        'description' => '',
        'short_description' => '',
        'attribute_set_id' => 4,
        'website_ids' => [
            $baseWebsite->getId(),
        ],
        'price' => 10,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '',
        'meta_description' => '',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu-product-test-category-paths-test-standalone',
    ],[
        'sku' => 'klevu-category-paths-test-standalone-is-exclude-cat',
        'name' => '[Klevu][Product Test] Category Paths: Is Exclude Cat',
        'description' => '',
        'short_description' => '',
        'attribute_set_id' => 4,
        'website_ids' => [
            $baseWebsite->getId(),
        ],
        'price' => 20,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '',
        'meta_description' => '',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu-product-test-category-paths-is-exclude-cat',
    ],
];

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

$collection = $objectManager->create(ProductResource\Collection::class);
$collection->addAttributeToFilter('sku', ['in' => array_column($fixtures, 'sku')]);
$collection->setFlag('has_stock_status_filter', true);
$collection->load();
foreach ($collection as $product) {
    $productRepository->delete($product);
}

foreach ($fixtures as $fixture) {
    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);
    $product->setPrice($fixture['price']);

    $product = $productRepository->save($product);
    $product->unsetData('category_ids');
    $priceIndexerProcessor->reindexRow($product->getId());
}

$configurableFixtures = [
    [
        'type_id' => 'configurable',
        'sku' => 'klevu-category-paths-test-parent-no-categories',
        'name' => '[Klevu][Product Test] Category Paths: Parent No Categories',
        'description' => '',
        'short_description' => '',
        'attribute_set_id' => 4,
        'website_ids' => [
            $baseWebsite->getId(),
        ],
        'tax_class_id' => 2,
        'meta_title' => '',
        'meta_description' => '',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu-product-test-category-paths-test-parent-no-categories',
        'child_skus' => [
            'klevu-category-paths-test-child-with-categories',
        ],
    ], [
        'type_id' => 'configurable',
        'sku' => 'klevu-category-paths-test-parent-with-categories',
        'name' => '[Klevu][Product Test] Category Paths: Parent With Categories',
        'description' => '',
        'short_description' => '',
        'attribute_set_id' => 4,
        'website_ids' => [
            $baseWebsite->getId(),
        ],
        'tax_class_id' => 2,
        'meta_title' => '',
        'meta_description' => '',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu-product-test-category-paths-test-parent-with-categories',
        'child_skus' => [
            'klevu-category-paths-test-child-no-categories',
        ],
    ],
];

$collection = $objectManager->create(ProductResource\Collection::class);
$collection->addAttributeToFilter('sku', ['in' => array_column($configurableFixtures, 'sku')]);
$collection->setFlag('has_stock_status_filter', true);
$collection->load();
foreach ($collection as $product) {
    $productRepository->delete($product);
}

foreach ($configurableFixtures as $configurableFixture) {
    $childSkus = $configurableFixture['child_skus'];
    unset($configurableFixture['price'], $configurableFixture['special_price'], $configurableFixture['child_skus']);

    /** @var $product Product */
    $product = $objectManager->create(Product::class);

    $attributeValues = [];
    $associatedProductIds = [];
    foreach ($childSkus as $childSku) {
        foreach ($fixtures as $childFixture) {
            if ($childFixture['sku'] !== $childSku) {
                continue;
            }

            $attributeValues[] = [
                'label' => 'test',
                'attribute_id' => $configurableAttribute->getId(),
                'value_index' => $childFixture['klevu_test_configurable'],
            ];

            $associatedProductIds[$childSku] = (int)$productRepository->get($childSku)->getId();
        }
    }

    if ($attributeValues) {
        $configurableAttributesData = [
            [
                'attribute_id' => $configurableAttribute->getId(),
                'code' => $configurableAttribute->getAttributeCode(),
                'label' => $configurableAttribute->getDataUsingMethod('store_label'),
                'position' => 0,
                'values' => $attributeValues,
            ],
        ];
        $configurableOptions = $configurableOptionsFactory->create($configurableAttributesData);
        /** @var ProductExtensionInterface $extensionConfigurableAttributes */
        $extensionConfigurableAttributes = $product->getExtensionAttributes();
        $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
    }

    $product->isObjectNew(true);
    $product->addData($configurableFixture);

    $productRepository->cleanCache();
    $productRepository->save($product);
    $product->unsetData('category_ids');
}

$categoryCollection = $objectManager->create(CategoryCollection::class);
$categoryCollection->addAttributeToFilter('name', ['like' => '%[Klevu][Product Test %']);
$categoryCollection->load();

/** @var CategoryLinkManagementInterface $categoryLinkManagement */
$categoryLinkManagement = $objectManager->get(CategoryLinkManagementInterface::class);
$categoryLinkManagement->assignProductToCategories(
    'klevu-category-paths-test-child-with-categories',
    $categoryCollection->getColumnValues('entity_id')
);
$categoryLinkManagement->assignProductToCategories(
    'klevu-category-paths-test-parent-with-categories',
    $categoryCollection->getColumnValues('entity_id')
);
$categoryLinkManagement->assignProductToCategories(
    'klevu-category-paths-test-standalone',
    $categoryCollection->getColumnValues('entity_id')
);
$categoryLinkManagement->assignProductToCategories(
    'klevu-category-paths-test-standalone-is-exclude-cat',
    $categoryCollection->getColumnValues('entity_id')
);

$indexes = [
    'catalog_product_attribute',
    'catalog_product_price',
    'inventory',
    'cataloginventory_stock',
    'catalog_category_product',
    'catalog_product_category',
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
