<?php
/** @noinspection PhpDeprecationInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\ProductTypeListInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as IndexerProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Registry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Bundle\Api\Data\OptionInterfaceFactory;
use Magento\Bundle\Api\Data\LinkInterfaceFactory;

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

$productTypeList = $objectManager->create(ProductTypeListInterface::class);
$availableProductTypes = array_map(function(\Magento\Catalog\Model\ProductType $productType) {
    return $productType->getName();
}, $productTypeList->getProductTypes());

// Simple products
$attributeValues = [];
$productSkuToId = [];
foreach ($fixtures as $fixture) {
    if ($fixture['type_id']!== 'simple' || !in_array('simple', $availableProductTypes, true)) {
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

// Virtual Products
foreach ($fixtures as $fixture) {
    if ($fixture['type_id']!== 'virtual' || !in_array('virtual', $availableProductTypes, true)) {
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

// Downloadable products
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'downloadable' || !in_array('downloadable', $availableProductTypes, true)) {
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

// Giftcard products
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'giftcard' || !in_array('giftcard', $availableProductTypes, true)) {
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

// Configurable Setup
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'configurable' || !in_array('configurable', $availableProductTypes, true)) {
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

//setting up grouped product
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'grouped' || !in_array('grouped', $availableProductTypes, true)) {
        continue;
    }

    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);

    $product = $productRepository->save($product);
    $indexerProcessor->reindexRow($product->getId());

    $newLinks = [];
    $productLinkFactory = $objectManager->get(\Magento\Catalog\Api\Data\ProductLinkInterfaceFactory::class);

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepositorySimple */
    $productRepositorySimple = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);

    /** @var \Magento\Catalog\Api\Data\ProductLinkInterface $productLink */
    $productLink = $productLinkFactory->create();
    $linkedProduct = $productRepositorySimple->get($fixture['associated_skus'][0]);
    $productLink->setSku($product->getSku())
        ->setLinkType('associated')
        ->setLinkedProductSku($linkedProduct->getSku())
        ->setLinkedProductType($linkedProduct->getTypeId())
        ->setPosition(1)
        ->getExtensionAttributes()
        ->setQty(1);
    $newLinks[] = $productLink;

    $product->setProductLinks($newLinks);
    $product->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);
    $productRepositorySimple->save($product);
}

//setting up bundle product
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'bundle' || !in_array('bundle', $availableProductTypes, true)) {
        continue;
    }

    /** @var $bundleProduct Product */
    $bundleProduct = $objectManager->create(Product::class);
    $bundleProduct->isObjectNew(true);
    $bundleProduct->addData($fixture);

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepositorySimple */
    $productRepositorySimple = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    $linkedProduct = $productRepositorySimple->get($fixture['associated_skus'][0]);

    $bundleProduct->setPriceView(1)
        ->setSkuType(1)
        ->setWeightType(1)
        ->setPriceType(1)
        ->setShipmentType(0)
        ->setPrice(10.0)
        ->setBundleOptionsData(
            [
                [
                    'title' => 'Bundle Product Items',
                    'default_title' => 'Bundle Product Items',
                    'type' => 'select', 'required' => 1,
                    'delete' => '',
                ],
            ]
        )
        ->setBundleSelectionsData(
            [
                [
                    [
                        'product_id' => $linkedProduct->getId(),
                        'selection_price_value' => 1.99,
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
                            /** @var \Magento\Bundle\Api\Data\LinkInterface$link */
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

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
