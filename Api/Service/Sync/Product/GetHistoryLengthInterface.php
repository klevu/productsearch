<?php

namespace Klevu\Search\Api\Service\Sync\Product;

interface GetHistoryLengthInterface
{
    /**
     * @param int $storeId
     *
     * @return int
     */
    public function execute($storeId = null);
}
