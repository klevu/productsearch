<?php

use Klevu\Search\Model\Order\Sync;
use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$incrementIdsToSync = [
    'KLEVU100000001',
    'KLEVU100000002',
    'KLEVU100000003',
    'KLEVU100000004',
    'KLEVU100000005',
    'KLEVU100000006',
    'KLEVU100000007',
    'KLEVU100000008',
    'KLEVU100000009',
    'KLEVU100000010',
    'KLEVU100000011',
    'KLEVU100000012',
    'KLEVU100000013',
    'KLEVU100000014',
    'KLEVU100000015',
    'KLEVU100000016',
    'KLEVU100000017',
    'KLEVU100000018',
    'KLEVU100000019',
    'KLEVU100000020',
    'KLEVU100000021',
    'KLEVU200000001',
    'KLEVU200000002',
    'KLEVU200000003',
    'KLEVU200000004',
    'KLEVU200000005',
];

/** @var Sync $syncModel */
$syncModel = $objectManager->get(Sync::class);


/** @var ResourceConnection $resource */
$resource = $objectManager->get(ResourceConnection::class);
$connection = $resource->getConnection();
$connection->truncateTable(
    $resource->getTableName('klevu_order_sync')
);

foreach ($incrementIdsToSync as $incrementId) {
    /** @var Order $order */
    $order = $objectManager->get(Order::class);
    $order->load($incrementId, 'increment_id');
    if ($order->getId()) {
        $syncModel->addOrderToQueue($order);
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
