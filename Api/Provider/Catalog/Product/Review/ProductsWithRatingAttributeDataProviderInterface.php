<?php

namespace Klevu\Search\Api\Provider\Catalog\Product\Review;

interface ProductsWithRatingAttributeDataProviderInterface
{
    /**
     * @api
     * @return int[]
     */
    public function getProductIdsForAllStores();

    /**
     * @param int $storeId
     * @return int[]
     */
    public function getProductIdsForStore($storeId);
}
