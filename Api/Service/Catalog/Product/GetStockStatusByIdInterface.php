<?php

namespace Klevu\Search\Api\Service\Catalog\Product;

interface GetStockStatusByIdInterface
{
    /**
     * @param array $productIds
     * @param int|null $websiteId
     *
     * @return array
     */
    public function execute(array $productIds, $websiteId = null);
}
