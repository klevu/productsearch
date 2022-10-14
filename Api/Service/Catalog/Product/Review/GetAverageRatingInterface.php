<?php

namespace Klevu\Search\Api\Service\Catalog\Product\Review;

use Magento\Catalog\Api\Data\ProductInterface;

interface GetAverageRatingInterface
{
    /**
     * @param ProductInterface[] $products
     *
     * @return float
     */
    public function execute(array $products);
}
