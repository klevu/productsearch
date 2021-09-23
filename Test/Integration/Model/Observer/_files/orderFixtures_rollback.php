<?php

use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$incrementIdsToDelete = [
    'KLEVUOBS100000001',
    'KLEVUOBS100000002',
    'KLEVUOBS200000001',
    'KLEVUOBS200000002'
];
foreach ($incrementIdsToDelete as $incrementId) {
    /** @var Order $order */
    $order = $objectManager->get(Order::class);
    $order->load($incrementId, 'increment_id');
    if ($order->getId()) {

        /** @var ResourceConnection $resource */
        $resource = $objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $select = $connection->select();
        $select->from(
            $resource->getTableName('sales_order_item'),
            ['item_id']
        );
        $select->where('order_id = :orderId');
        $orderItemIds = $connection->fetchCol($select, ['orderId' => $order->getId()]);

        if ($orderItemIds) {
            $connection->delete(
                $resource->getTableName('klevu_order_sync'),
                $connection->quoteInto('order_item_id IN (?)', $orderItemIds)
            );
        }
        $order->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
