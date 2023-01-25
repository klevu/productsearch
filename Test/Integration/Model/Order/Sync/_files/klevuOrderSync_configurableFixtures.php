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
    'KLEVU200000001'
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
