<?php

namespace Klevu\Search\Api\Service\Sync;

use Magento\Store\Api\Data\StoreInterface;

interface GetBatchSizeInterface
{
    /**
     * @param StoreInterface $store
     *
     * @return int
     */
    public function execute(StoreInterface $store);
}
