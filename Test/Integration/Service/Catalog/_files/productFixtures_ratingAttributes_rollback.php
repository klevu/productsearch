<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$skusToDelete = [
    'klevu_simple_with_rating_with_reviewcount_allstores',
    'klevu_simple_with_rating_with_reviewcount_store1',
    'klevu_simple_with_rating_with_reviewcount_store2',
    'klevu_simple_with_rating_without_reviewcount_allstores',
    'klevu_simple_with_rating_without_reviewcount_store1',
    'klevu_simple_with_rating_without_reviewcount_store2',
    'klevu_simple_without_rating_with_reviewcount_allstores',
    'klevu_simple_without_rating_with_reviewcount_store1',
    'klevu_simple_without_rating_with_reviewcount_store2',
    'klevu_simple_without_rating_without_reviewcount_allstores',
    'klevu_simple_without_rating_without_reviewcount_store1',
    'klevu_simple_without_rating_without_reviewcount_store2',
    'klevu_simple_with_rating_with_reviewcount_globalscope',
];

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

foreach ($skusToDelete as $sku) {
    try {
        $productRepository->deleteById($sku);
    } catch (NoSuchEntityException $e) {
        // This is fine
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
