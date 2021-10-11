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
