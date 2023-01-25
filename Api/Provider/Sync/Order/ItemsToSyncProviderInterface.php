<?php

namespace Klevu\Search\Api\Provider\Sync\Order;

use Klevu\Search\Model\Order\Sync;
use Magento\Sales\Api\Data\OrderItemInterface;

interface ItemsToSyncProviderInterface
{
    /**
     * @param int|null $storeId
     * @param int|null $orderId
     * @param int|null $status
     *
     * @return OrderItemInterface[]
     */
    public function getItems($storeId = null, $orderId = null, $status = Sync::SYNC_QUEUE_ITEM_WAITING);
}
