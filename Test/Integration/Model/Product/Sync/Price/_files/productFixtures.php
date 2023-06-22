<?php

use Magento\Catalog\Api\Data\ProductTierPriceInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Catalog\Api\ScopedProductTierPriceManagementInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Registry;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\Website;

include __DIR__ . '/productFixtures_rollback.php';

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

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$fixtures = [
    [
        'sku' => 'klevu_simple_1',
        'name' => '[Klevu] Simple Product 1',
        'type_id' => Product\Type::TYPE_SIMPLE,
        'description' => '[Klevu Test Fixtures] Simple product 1',
        'short_description' => '[Klevu Test Fixtures] Simple product 1',
        'attribute_set_id' => 4,
        'website_ids' => array_filter([
            $baseWebsite->getId(),
            $website1->getId(),
            $website2->getId(),
        ]),
        'price' => 10.00,
        'special_price' => null,
        'weight' => 1,
        'tax_class_id' => 2,
        'meta_title' => '[Klevu] Simple Product 1',
        'meta_description' => '[Klevu Test Fixtures] Simple product 1',
        'visibility' => Visibility::VISIBILITY_BOTH,
        'status' => Status::STATUS_ENABLED,
        'stock_data' => [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ],
        'url_key' => 'klevu_simple_1_' . crc32(rand()),
    ]
];

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

foreach ($fixtures as $fixture) {
    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->isObjectNew(true);
    $product->addData($fixture);

    $productRepository->save($product);
}

$tirePrices = [
    $product->getSku() => [
        'website_id' => $website1->getId(),
        'customer_group_id' => \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,
        'qty' => 1.0, // must be a float
        'value' => 11.00,
        'percentage_value' => false,
    ],
];

foreach ($tirePrices as $sku => $tirePrice) {
    /** @var ProductTierPriceInterface $productTierPrice */
    $productTierPriceFactory = $objectManager->create(ProductTierPriceInterfaceFactory::class);
    $productTierPrice = $productTierPriceFactory->create();
    $productTierPrice->setCustomerGroupId($tirePrice['customer_group_id']);
    $productTierPrice->setQty($tirePrice['qty']);
    $productTierPrice->setValue($tirePrice['value']);

    $extensionAttributes = $productTierPrice->getExtensionAttributes();
    if ($productTierPrice['percentage_value']) {
        $extensionAttributes->setPercentageValue($productTierPrice['percentage_value']);
    }
    $productTierPrice->setExtensionAttributes($extensionAttributes);
    $tierPriceManagement = $objectManager->create(ScopedProductTierPriceManagementInterface::class);
    $tierPriceManagement->add($sku, $productTierPrice);
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

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
