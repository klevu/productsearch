<?php

namespace Klevu\Search\Api\Service\Sync\Product;

use Magento\Store\Api\Data\StoreInterface;

interface GetRecordsPerPageInterface
{
    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function execute($store = null);
}
