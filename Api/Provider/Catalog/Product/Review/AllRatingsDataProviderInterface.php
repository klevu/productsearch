<?php

namespace Klevu\Search\Api\Provider\Catalog\Product\Review;

use Magento\Store\Api\Data\StoreInterface;

interface AllRatingsDataProviderInterface
{
    /**
     * @param StoreInterface|int $store
     *
     * @return array
     */
    public function getData($store);
}
