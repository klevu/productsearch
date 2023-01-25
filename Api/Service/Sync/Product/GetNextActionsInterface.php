<?php

namespace Klevu\Search\Api\Service\Sync\Product;

use Magento\Store\Api\Data\StoreInterface;

interface GetNextActionsInterface
{
    /**
     * @param StoreInterface $store
     * @param array $productIds
     *
     * @return array[]
     */
    public function execute(StoreInterface $store, array $productIds);
}
