<?php

namespace Klevu\Search\Api\Provider\Sync\Order\Item;

use Magento\Sales\Api\Data\OrderItemInterface;

interface DataProviderInterface
{
    /**
     * @param OrderItemInterface $orderItem
     *
     * @return array
     */
    public function getData(OrderItemInterface $orderItem);
}
