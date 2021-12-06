<?php
/** @noinspection PhpDeprecationInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as IndexerProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Registry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/productAttributeFixtures.php';

$objectManager = Bootstrap::getObjectManager();

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

// ------------------------------------------------------------------

$configurableAttribute = $eavConfig->getAttribute('catalog_product', 'klevu_test_configurable');
$configurableAttributeOptions = $configurableAttribute->getOptions();

/** @var array[] $fixtures */
include __DIR__ . '/productFixtures_data.php';

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
$attributeValues = [];
$productSkuToId = [];
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
//    $product = $product->load($product->getSku(), 'sku');
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
