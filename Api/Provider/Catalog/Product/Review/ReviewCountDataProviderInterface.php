<?php

namespace Klevu\Search\Api\Provider\Catalog\Product\Review;

interface ReviewCountDataProviderInterface
{
    /**
     * @param int $productId
     * @param int|null $storeId
     *
     * @return int|null
     */
    public function getData($productId, $storeId = null);
}
