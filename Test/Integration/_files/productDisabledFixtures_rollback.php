<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$skusToDelete = [
    'klevu_simple_1'
];

$objectManager = Bootstrap::getObjectManager();
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

foreach ($skusToDelete as $sku) {
    $product = $productRepository->get($sku);
    if ($product->getId()) {
        $productRepository->delete($product);
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
