<?php

namespace Klevu\Search\Api\Provider\Catalog\Product\Review;

interface AllReviewCountsDataProviderInterface
{
    /**
     * @param int $storeId
     *
     * @return array
     */
    public function getData($storeId);
}
