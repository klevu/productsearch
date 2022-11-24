<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$skusToDelete = [
    'klevu_grouped_recursive_test',
    'klevu_grouped_recursive_test_simple',
];

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$storeManager->setCurrentStore(null);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

foreach ($skusToDelete as $skuToDelete) {
    try {
        $product = $productRepository->get($skuToDelete);
        $productRepository->delete($product);
    } catch (NoSuchEntityException $e) {
        // This is fine
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
