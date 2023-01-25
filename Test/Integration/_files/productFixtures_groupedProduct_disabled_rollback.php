<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\ResourceConnection;

$skusToDelete = [
    'klevu_grouped_product_test',
    'klevu_grouped_product_test_simple',
    'klevu_bundle_product_test',
    'klevu_bundle_product_test_simple',
];

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

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

//Clean up klevu_product_sync
/** @var ResourceConnection $resource */
$resource = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();
$write = $resource->getConnection('core_write');
$connection->truncateTable(
    $resource->getTableName('klevu_product_sync')
);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
