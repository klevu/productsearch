<?php

use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\ProductRepositoryInterface;

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var ResourceConnection $resource */
$resource = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();
$write = $resource->getConnection('core_write');
$connection->truncateTable(
    $resource->getTableName('klevu_product_sync')
);

//Insert
$groupProductSku = 'klevu_bundle_product_test';
$groupProduct = $productRepository->get($groupProductSku);
$data[] = [
        'product_id' => $groupProduct->getId(),
        'parent_id' => 0,
        'store_id' => 1,
        'last_synced_at' => '2021-09-07 17:05:17'

    ];

$simpleProductSku = 'klevu_bundle_product_test_simple';
$simpleProduct = $productRepository->get($simpleProductSku);
$data[] =
    [
        'product_id' => $simpleProduct->getId(),
        'parent_id' => $groupProduct->getId(),
        'store_id' => 1,
        'last_synced_at' => '2021-09-07 17:05:17'

    ];
foreach ($data as $row) {
    $query = "REPLACE into " . $resource->getTableName('klevu_product_sync')
        . "(product_id, parent_id, store_id, last_synced_at, type) values "
        . "(:product_id, :parent_id, :store_id, :last_synced_at, :type)";

    $binds = [
        'product_id' => $row['product_id'],
        'parent_id' => $row['parent_id'],
        'store_id' => $row['store_id'],
        'last_synced_at' => $row['last_synced_at'],
        'type' => 'product'
    ];
    $write->query($query, $binds);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
