<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$skusToDelete = [
    'klevu_configurable_1',
    'klevu_simple_child_1',
    'klevu_simple_child_2'
];

$objectManager = Bootstrap::getObjectManager();
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

foreach ($skusToDelete as $sku) {
    try {
        $product = $productRepository->get($sku);
        if ($product->getId()) {
            $productRepository->delete($product);
        }
    } catch (NoSuchEntityException $e) {
        // this is fine
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
