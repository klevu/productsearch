<?php
/** @noinspection PhpDeprecationInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\ProductTypeListInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as IndexerProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductType;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Registry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Bundle\Api\Data\OptionInterfaceFactory;
use Magento\Bundle\Api\Data\LinkInterfaceFactory;

require __DIR__ . '/productFixtures_rollback.php';
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

$configurableAttribute = $eavConfig->getAttribute('catalog_product', 'klevu_synctest_configurable');
$configurableAttributeOptions = $configurableAttribute->getOptions();

/** @var ProductLinkInterfaceFactory $productLinkFactory */
$productLinkFactory = $objectManager->get(ProductLinkInterfaceFactory::class);

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
$availableProductTypes = array_map(function (ProductType $productType) {
    return $productType->getName();
}, $productTypeList->getProductTypes());

// Simple products
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

foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'simple' || !in_array('simple', $availableProductTypes, true)) {
        continue;
    }

    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);
    $product->setPrice($fixture['price']);

    $product = $productRepository->save($product);
    $indexerProcessor->reindexRow($product->getId());

    if (in_array($fixture['sku'], $configurableChildSkus, true)) {
        if (!isset($fixture['klevu_synctest_configurable'])) {
            throw new \LogicException(sprintf(
                'Fixture "%s" used as a configurable variant does not contain "klevu_synctest_configurable"',
                $fixture['sku']
            ));
        }

        $attributeValues[$fixture['sku']] = [
            'label' => 'test',
            'attribute_id' => $configurableAttribute->getId(),
            'value_index' => $fixture['klevu_synctest_configurable'],
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

    if (in_array($fixture['sku'], $configurableChildSkus, true)) {
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

    $childSkus = $fixture['associated_skus'];

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

    $productRepository->save($product);
    $productRepository->cleanCache();
}

//setting up bundle product
foreach ($fixtures as $fixture) {
    if ($fixture['type_id'] !== 'bundle' || !in_array('bundle', $availableProductTypes, true)) {
        continue;
    }

    $childSkus = $fixture['associated_skus'];

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
