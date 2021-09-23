<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\Data\ProductTierPriceExtensionFactory;
use Magento\Catalog\Api\Data\ProductExtensionInterfaceFactory;

\Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize();

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Framework\App\ProductMetadataInterface $productMetadata */
$productMetadata = $objectManager->get(\Magento\Framework\App\ProductMetadataInterface::class);

/** @var \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement */
$categoryLinkManagement = $objectManager->get(\Magento\Catalog\Api\CategoryLinkManagementInterface::class);

$tierPrices = [];
/** @var \Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory $tierPriceFactory */
$tierPriceFactory = $objectManager->get(\Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory::class);
/** @var  $tpExtensionAttributes */
$tpExtensionAttributesFactory = $objectManager->get(ProductTierPriceExtensionFactory::class);
/** @var  $productExtensionAttributes */
$productExtensionAttributesFactory = $objectManager->get(ProductExtensionInterfaceFactory::class);

$adminWebsite = $objectManager->get(\Magento\Store\Api\WebsiteRepositoryInterface::class)->get('admin');
$tierPriceExtensionAttributes1 = $tpExtensionAttributesFactory->create();
if (method_exists($tierPriceExtensionAttributes1, 'setWebsiteId')) {
    $tierPriceExtensionAttributes1->setWebsiteId($adminWebsite->getId());
}
$productExtensionAttributesWebsiteIds = null;
if (version_compare($productMetadata->getVersion(), '2.2.0', '>=')) {
    $productExtensionAttributesWebsiteIds = $productExtensionAttributesFactory->create(
        ['website_ids' => $adminWebsite->getId()]
    );
}

$tierPrices[] = $tierPriceFactory->create(
    [
        'data' => [
            'customer_group_id' => \Magento\Customer\Model\Group::CUST_GROUP_ALL,
            'qty' => 2,
            'value' => 8
        ]
    ]
)->setExtensionAttributes($tierPriceExtensionAttributes1);

$tierPrices[] = $tierPriceFactory->create(
    [
        'data' => [
            'customer_group_id' => \Magento\Customer\Model\Group::CUST_GROUP_ALL,
            'qty' => 5,
            'value' => 5
        ]
    ]
)->setExtensionAttributes($tierPriceExtensionAttributes1);

$tierPrices[] = $tierPriceFactory->create(
    [
        'data' => [
            'customer_group_id' => \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,
            'qty' => 3,
            'value' => 5
        ]
    ]
)->setExtensionAttributes($tierPriceExtensionAttributes1);

$tierPrices[] = $tierPriceFactory->create(
    [
        'data' => [
            'customer_group_id' => \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,
            'qty' => 3.2,
            'value' => 6,
        ]
    ]
)->setExtensionAttributes($tierPriceExtensionAttributes1);

$tierPriceExtensionAttributes2 = $tpExtensionAttributesFactory->create();
if (method_exists($tierPriceExtensionAttributes2, 'setPercentageValue')) {
    $tierPriceExtensionAttributes2->setPercentageValue(50);
}
if (method_exists($tierPriceExtensionAttributes2, 'setWebsiteId')) {
    $tierPriceExtensionAttributes2->setWebsiteId($adminWebsite->getId());
}

$tierPrices[] = $tierPriceFactory->create(
    [
        'data' => [
            'customer_group_id' => \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,
            'qty' => 10
        ]
    ]
)->setExtensionAttributes($tierPriceExtensionAttributes2);

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product->isObjectNew(true);
$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(1)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('100mm Rich Mill RM4 90° Plunge Face Mill for LNEX/LNMX10 Inserts - RM4ZC (Korloy)')
    ->setSku('424756')
    ->setPrice(396.99)
    ->setWeight(112)
    ->setShortDescription("<p>The Korloy Rich Mill RM4Z plunge face mill 90 Degree Milling System offers the ultimate combination of performance and economic milling. It is the 1st choice for 90 degree milling operations including; plunging, slotting, facing, square shoulder milling, ramping & circular interpolation. Suitable for machining steel, stainless steel, cast iron, aluminium. All holders are also through coolant.</p>")
    ->setTaxClassId(2)
    //->setTierPrices($tierPrices)
    ->setDescription('<p>Double sided insert gives 4 usable cutting edges, Ideal replacement for APKT style systems
High rake angle chip breaker combined with strong cutting edge of negative geometry insert
Available in M tolerance or E tolerance (ground) inserts
Optional chip breakers for lightweight machine tools
Dedicated range for plunge milling
Double sided insert gives 4 usable cutting edges
Negative insert with the strongest cutting edge</p>');
if ($productExtensionAttributesWebsiteIds) {
    $product->setExtensionAttributes($productExtensionAttributesWebsiteIds);
}
$product->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(
        [
            'use_config_manage_stock'   => 1,
            'qty'                       => 10000,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ]
    )->setCanSaveCustomOptions(true)
    ->setHasOptions(true);

$oldOptions = [
    [
        'previous_group' => 'text',
        'title'     => 'Test Field',
        'type'      => 'field',
        'is_require' => 1,
        'sort_order' => 0,
        'price'     => 1,
        'price_type' => 'fixed',
        'sku'       => '1-text',
        'max_characters' => 100,
    ],
    [
        'previous_group' => 'date',
        'title'     => 'Test Date and Time',
        'type'      => 'date_time',
        'is_require' => 1,
        'sort_order' => 0,
        'price'     => 2,
        'price_type' => 'fixed',
        'sku'       => '2-date',
    ],
    [
        'previous_group' => 'select',
        'title'     => 'Test Select',
        'type'      => 'drop_down',
        'is_require' => 1,
        'sort_order' => 0,
        'values'    => [
            [
                'option_type_id' => null,
                'title'         => 'Option 1',
                'price'         => 3,
                'price_type'    => 'fixed',
                'sku'           => '3-1-select',
            ],
            [
                'option_type_id' => null,
                'title'         => 'Option 2',
                'price'         => 3,
                'price_type'    => 'fixed',
                'sku'           => '3-2-select',
            ],
        ]
    ],
    [
        'previous_group' => 'select',
        'title'     => 'Test Radio',
        'type'      => 'radio',
        'is_require' => 1,
        'sort_order' => 0,
        'values'    => [
            [
                'option_type_id' => null,
                'title'         => 'Option 1',
                'price'         => 3,
                'price_type'    => 'fixed',
                'sku'           => '4-1-radio',
            ],
            [
                'option_type_id' => null,
                'title'         => 'Option 2',
                'price'         => 3,
                'price_type'    => 'fixed',
                'sku'           => '4-2-radio',
            ],
        ]
    ]
];

$options = [];

/** @var \Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory $customOptionFactory */
$customOptionFactory = $objectManager->create(\Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory::class);

foreach ($oldOptions as $option) {
    /** @var \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option */
    $option = $customOptionFactory->create(['data' => $option]);
    $option->setProductSku($product->getSku());

    $options[] = $option;
}

$product->setOptions($options);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$productRepository->save($product);

$categoryLinkManagement->assignProductToCategories(
    $product->getSku(),
    [2]
);
