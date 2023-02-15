<?php

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

include "productConfigurableFixtures_rollback.php";

$objectManager = Bootstrap::getObjectManager();

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);

/** @var Website $baseWebsite */
$baseWebsite = $objectManager->create(Website::class);
$baseWebsite->load('base', 'code');

/** @var Website $website1 */
$website1 = $objectManager->create(Website::class);
$website1->load('klevu_test_website_1', 'code');

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

/** @var EavConfig $eavConfig */
$eavConfig = $objectManager->get(EavConfig::class);
/** @var ConfigurableOptionsFactory $configurableOptionsFactory */
$configurableOptionsFactory = $objectManager->get(ConfigurableOptionsFactory::class);

$configurableAttribute = $eavConfig->getAttribute('catalog_product', 'klevu_test_configurable_attribute');
$configurableAttributeOptions = $configurableAttribute->getOptions();

$stores = $website1->getStores();
foreach ($stores as $store) {
    $fixtures = [];

    $fixtures[] = [
        'sku' => 'klevu_simple_child_1',
        'name' => 'Simple Child Name ' . $store->getName(),
        'description' => 'Simple Child Description ' . $store->getName(),
        'short_description' => 'Simple Child Short Description ' . $store->getName(),
        'type_id' => Product\Type::TYPE_SIMPLE,
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
        ]),
        'store_id' => $store->getId(),
        'price' => 10.00,
        'special_price' => null,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Child Product 1 ' . $store->getName(),
        'meta_description' => '[Klevu Test Fixtures] Simple Child product 1 ' . $store->getName(),
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu-simple-child-1-' . $store->getCode() . crc32(rand()),
        'klevu_test_configurable_attribute' => $configurableAttributeOptions[8]->getValue(),
    ];
    $fixtures[] = [
        'type_id' => Configurable::TYPE_CODE,
        'sku' => 'klevu_configurable_1',
        'name' => 'Configurable Name ' . $store->getName(),
        'description' => 'Configurable Description ' . $store->getName(),
        'short_description' => 'Configurable Short Description ' . $store->getName(),
        'attribute_set_id' => 4,
        'website_ids' => [
            $baseWebsite->getId(),
            $website1->getId(),
        ],
        'store_id' => $store->getId(),
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Configurable Product' . $store->getName(),
        'meta_description' => '[Klevu Test Fixtures] Configurable product ' . $store->getName(),
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock' => 1,
            'is_in_stock' => 1,
        ],
        'url_key' => 'klevu-configurable-product-' . $store->getCode() . crc32(rand()),
        'child_skus' => [
            'klevu_simple_child_1'
        ],
    ];

    // Simple products
    $attributeValues = [];
    $productSkuToId = [];

    foreach ($fixtures as $fixture) {
        if ($fixture['type_id'] !== Product\Type::TYPE_SIMPLE) {
            continue;
        }
        /** @var $product Product */
        $product = $objectManager->create(Product::class);
        $product->isObjectNew(true);
        $product->addData($fixture);
        if (isset($product['store_id'])) {
            $storeManager->setCurrentStore($product['store_id']);
        }

        $productRepository->save($product);
        $product = $productRepository->get($product['sku']);

        if (0 === strpos($fixture['sku'], 'klevu_simple_child_')) {
            $attributeValues[$fixture['sku']] = [
                'label' => 'test',
                'attribute_id' => $configurableAttribute->getId(),
                'value_index' => $fixture['klevu_test_configurable_attribute'],
            ];
            $productSkuToId[$product->getSku()] = $product->getId();
        }
    }

    // Configurable Setup
    foreach ($fixtures as $fixture) {
        if ($fixture['type_id'] !== Configurable::TYPE_CODE) {
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

        if (isset($product['store_id'])) {
            $storeManager->setCurrentStore($product['store_id']);
        }

        $productRepository->cleanCache();
        $productRepository->save($product);
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
