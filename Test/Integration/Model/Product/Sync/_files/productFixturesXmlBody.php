<?php

use Magento\Bundle\Api\Data\LinkInterfaceFactory;
use Magento\Bundle\Api\Data\OptionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\ProductTypeListInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as IndexerProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductType;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Registry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\Eav\Model\Config as EavConfig;

include __DIR__ . '/productFixturesXmlBody_rollback.php';
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

$productTypeList = $objectManager->create(ProductTypeListInterface::class);
$availableProductTypes = array_map(function (ProductType $productType) {
    return $productType->getName();
}, $productTypeList->getProductTypes());

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$fixtures = [
    [
        'type_id' => 'simple',
        'sku' => 'klevu_simple_synctest_xmlbody',
        'name' => '[Klevu] Sync Test: Simple Product',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 0.00,
        'special_price' => 0.00,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => 'false',
        'meta_description' => '[Klevu Test Fixtures]',
        'meta_keyword' => '0',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'klevu_test_configurable' => $configurableAttributeOptions[1]->getValue(),
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'klevu_boolean_attribute' => 1,
        'url_key' => 'klevu_simple_synctest_xmlbody_' . crc32(rand()),
    ],
    [
        'type_id' => 'virtual',
        'sku' => 'klevu_virtual_synctest_xmlbody',
        'name' => '[Klevu] Sync Test: Virtual Product',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 1.234567,
        'special_price' => 0.987654,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'meta_keyword' => '     ',
        'visibility' => Visibility::VISIBILITY_IN_SEARCH,
        'status' => Status::STATUS_ENABLED,
        'klevu_test_configurable' => $configurableAttributeOptions[2]->getValue(),
        'stock_data' => [
            'use_config_manage_stock'   => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_virtual_synctest_xmlbody_' . crc32(rand()),
        'klevu_boolean_attribute' => 0,
    ],
    [
        'type_id' => 'downloadable',
        'sku' => 'klevu_downloadable_synctest_xmlbody',
        'name' => '[Klevu] Sync Test: Downloadable Product',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 100,
        'special_price' => 50,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'meta_keyword' => 0,
        'visibility' => Visibility::VISIBILITY_IN_CATALOG,
        'status' => Status::STATUS_ENABLED,
        'klevu_test_configurable' => $configurableAttributeOptions[3]->getValue(),
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_downloadable_synctest_xmlbody_' . crc32(rand()),
        'klevu_boolean_attribute' => 0,
    ],

    [
        'type_id' => 'simple',
        'sku' => 'klevu_configchild_synctest_xmlbody',
        'name' => '[Klevu] Sync Test: Child Product',
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
        'meta_keyword' => '1',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 0,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_configchild_synctest_xmlbody_' . crc32(rand()),
        'klevu_test_configurable' => $configurableAttributeOptions[4]->getValue(),
        'klevu_boolean_attribute' => 1,
    ],
    [
        'type_id' => 'configurable',
        'sku' => 'klevu_config_synctest_xmlbody',
        'name' => '[Klevu] Sync Test: Configurable Product',
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
        'meta_keyword' => '1',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_config_synctest_xmlbody_' . crc32(rand()),
        'child_skus' => [
            'klevu_configchild_synctest_xmlbody',
        ],
        'klevu_boolean_attribute' => 1,
    ],

    [
        'type_id' => 'simple',
        'sku' => 'klevu_groupchild_synctest_xmlbody',
        'name' => '[Klevu] Sync Test: Grouped Child Product',
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
        'meta_keyword' => '0',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_groupchild_synctest_xmlbody_' . crc32(rand()),
        'klevu_boolean_attribute' => 1,
    ],
    [
        'type_id' => 'grouped',
        'sku' => 'klevu_grouped_synctest_xmlbody',
        'name' => '[Klevu] Sync Test: Grouped Product',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 0.01,
        'special_price' => 0.01,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'meta_keyword' => '0',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_grouped_synctest_xmlbody_' . crc32(rand()),
        'child_skus' => [
            'klevu_groupchild_synctest_xmlbody',
        ],
        'klevu_boolean_attribute' => 0,
    ],
    [
        'type_id' => 'simple',
        'sku' => 'klevu_bundlechild_synctest_xmlbody',
        'name' => '[Klevu] Sync Test: Bundle Child Product',
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
        'meta_keyword' => '0',
        'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_bundlechild_synctest_xmlbody_' . crc32(rand()),
        'klevu_boolean_attribute' => 1,
    ],
    [
        'type_id' => 'bundle',
        'sku' => 'klevu_bundle_synctest_xmlbody',
        'name' => '[Klevu] Sync Test: Bundle Product',
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
        'meta_keyword' => '0',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 0,
        ],
        'url_key' => 'klevu_bundle_synctest_instock_childreninstock_' . crc32(rand()),
        'price_type' => \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC,
        'child_skus' => [
            'klevu_bundlechild_synctest_xmlbody',
        ],
        'klevu_boolean_attribute' => 1,
    ],

    [
        'type_id' => 'giftcard',
        'sku' => 'klevu_giftcard_synctest_xmlbody',
        'name' => '[Klevu] Sync Test: Giftcard Product',
        'description' => '[Klevu Test Fixtures]',
        'short_description' => '[Klevu Test Fixtures]',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 0.00,
        'special_price' => 0.00,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu]',
        'meta_description' => '[Klevu Test Fixtures]',
        'meta_keyword' => '0',
        'giftcard_type' => 1,
        'allow_open_amount' => 1,
        'giftcard_amounts' => [
            25,
            50,
        ],
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'klevu_test_configurable' => $configurableAttributeOptions[1]->getValue(),
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_giftcard_synctest_xmlbody_' . crc32(rand()),
        'klevu_boolean_attribute' => 1,
    ]
];
if (isset($CREATE_SKUS) && null !== $CREATE_SKUS) {
    $fixtures = array_filter($fixtures, static function (array $fixture) use ($CREATE_SKUS) {
        return in_array($fixture['sku'], $CREATE_SKUS, true);
    });
}

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
foreach ($fixtures as $fixture) {
    if (!in_array($fixture['type_id'], ['simple', 'virtual', 'downloadable', 'giftcard'], true)
        || ('giftcard' === $fixture['type_id'] && !in_array('giftcard', $availableProductTypes, true))) {
        continue;
    }

    $giftcardAmounts = isset($fixture['giftcard_amounts'])
        ? $fixture['giftcard_amounts']
        : [];
    unset($fixture['giftcard_amounts']);

    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);

    $product = $productRepository->save($product);

    if ('giftcard' === $fixture['type_id'] && !empty($giftcardAmounts)) {
        $giftcardAmountAttribute = $eavConfig->getAttribute('catalog_product', 'giftcard_amounts');
        /** @var \Magento\GiftCard\Model\ResourceModel\Giftcard\Amount $giftcardAmountResource */
        $giftcardAmountResource = $objectManager->get(\Magento\GiftCard\Model\ResourceModel\Giftcard\Amount::class);

        foreach ($giftcardAmounts as $giftcardAmount) {
            $giftcardAmountResource->insert([
                'website_id' => 0,
                'value' => $giftcardAmount,
                'attribute_id' => $giftcardAmountAttribute->getAttributeId(),
                'row_id' => $product->getData('row_id'),
            ]);
        }
        $product->setStoreId(0);
        $product->setData('allow_open_amount', 1);
        $product->setData(
            $giftcardAmountAttribute->getAttributeCode(),
            $giftcardAmountResource->loadProductData($product, $giftcardAmountAttribute)
        );
        $product = $productRepository->save($product);
    }

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
    $product = $productRepository->save($product);
    $productIds[] = $product->getId();
}

// Grouped Setup
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'grouped') {
        continue;
    }

    $childSkus = $fixture['child_skus'];

    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);

    $product = $productRepository->save($product);
    $productRepository->cleanCache();

    $simpleProductLinks = [];
    foreach ($childSkus as $i => $childSku) {
        $simpleProductLink = $productLinkFactory->create();
        $simpleProductLink->setSku($fixture['sku']);
        $simpleProductLink->setLinkType('associated');
        $simpleProductLink->setLinkedProductSku($childSku);
        $simpleProductLink->setLinkedProductType('simple');
        $simpleProductLink->setPosition($i + 1);
        $extensionAttributes = $simpleProductLink->getExtensionAttributes();
        $extensionAttributes->setQty(1);

        $simpleProductLinks[] = $simpleProductLink;
    }

    $product->setProductLinks($simpleProductLinks);
    $indexerProcessor->reindexRow($product->getId());

    $product = $productRepository->save($product);
    $productRepository->cleanCache();
    $productIds[] = $product->getId();
}

// Bundle Setup
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

$productRepository->cleanCache();

/** @var \Magento\CatalogInventory\Model\StockRegistryStorage $stockRegistryStorage */
$stockRegistryStorage = $objectManager->get(\Magento\CatalogInventory\Model\StockRegistryStorage::class);
foreach ($productIds as $productId) {
    $stockRegistryStorage->removeStockStatus($productId);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
