<?php

namespace Klevu\Search\Api\Service\Sync;

use Magento\Store\Api\Data\StoreInterface;

interface GetOrderSelectMaxLimitInterface
{
    /**
     * @param StoreInterface $store
     *
     * @return int
     */
    public function execute(StoreInterface $store);
}