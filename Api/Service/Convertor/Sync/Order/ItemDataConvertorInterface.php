<?php

namespace Klevu\Search\Api\Service\Convertor\Sync\Order;

interface ItemDataConvertorInterface
{
    /**
     * @param array $orderItem
     *
     * @return array
     */
    public function convert(array $orderItem);
}
