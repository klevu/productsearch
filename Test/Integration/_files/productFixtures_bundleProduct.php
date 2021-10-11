<?php

use Magento\Bundle\Api\Data\LinkInterfaceFactory;
use Magento\Bundle\Api\Data\OptionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as IndexerProcessor;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$defaultStoreView = $storeManager->getDefaultStoreView();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var IndexerProcessor $indexerProcessor */
$indexerProcessor = $objectManager->get(IndexerProcessor::class);
/** @var ProductLinkInterfaceFactory $productLinkFactory */
$productLinkFactory = $objectManager->get(ProductLinkInterfaceFactory::class);

// -------------------------------------------------------------------------------------

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$skusToDelete = [
    'klevu_bundle_product_test',
    'klevu_bundle_product_test_simple',
];
foreach ($skusToDelete as $skuToDelete) {
    try {
        $groupedProduct = $productRepository->get($skuToDelete);
        $productRepository->delete($groupedProduct);
    } catch (NoSuchEntityException $e) {
        // This is fine
    }
}

// -------------------------------------------------------------------------------------

$simpleProduct = $objectManager->create(Product::class);
$simpleProduct->isObjectNew(true);
$simpleProduct->addData([
    'sku' => 'klevu_bundle_product_test_simple',
    'type_id' => 'simple',
    'name' => '[Klevu] Product Test (Simple)',
    'description' => '[Klevu Test Fixtures] Simple child for assigned bundle product',
    'short_description' => '[Klevu Test Fixtures] Simple child for assigned bundle product',
    'attribute_set_id' => 4,
    'website_ids' => [
        $defaultStoreView->getWebsiteId(),
    ],
    'price' => 20.00,
    'special_price' => 5.99,
    'weight' => 1,
    'tax_class_id' => 0,
    'meta_title' => '[Klevu] bundle Product Test (Simple)',
    'meta_description' => '[Klevu Test Fixtures] Simple child for assigned bundle product',
    'visibility' => Visibility::VISIBILITY_BOTH,
    'status' => Status::STATUS_ENABLED,
    'stock_data' => [
        'use_config_manage_stock'   => 1,
        'qty'                       => 100,
        'is_qty_decimal'            => 0,
        'is_in_stock'               => 1,
    ],
    'url_key' => 'klevu-bundle-product-test-simple-'. md5(rand()),
]);
$simpleProduct = $productRepository->save($simpleProduct);

$bundleProduct = $objectManager->create(Product::class);
$bundleProduct->isObjectNew(true);
$bundleProduct->addData([
    'sku' => 'klevu_bundle_product_test',
    'type_id' => 'bundle',
    'name' => '[Klevu] Bundle Product Test',
    'description' => '[Klevu Test Fixtures] assigned bundle product',
    'short_description' => '[Klevu Test Fixtures] assigned bundle product',
    'attribute_set_id' => 4,
    'website_ids' => [
        $defaultStoreView->getWebsiteId(),
    ],
    'price' => 100.00,
    'special_price' => 49.99,
    'weight' => 1,
    'tax_class_id' => 0,
    'meta_title' => '[Klevu] Bundle Product Test',
    'meta_description' => '[Klevu Test Fixtures] assigned bundle product',
    'visibility' => Visibility::VISIBILITY_BOTH,
    'status' => Status::STATUS_ENABLED,
    'stock_data' => [
        'use_config_manage_stock'   => 1,
        'qty'                       => 100,
        'is_qty_decimal'            => 0,
        'is_in_stock'               => 1,
    ],
    'url_key' => 'klevu-bundle-product-test-'. md5(rand()),
]);
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
                    'product_id' => $simpleProduct->getId(),
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


// -------------------------------------------------------------------------------------

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
