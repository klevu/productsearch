<?php

namespace Klevu\Search\Api\Service\Catalog\Product;

use Magento\Catalog\Api\Data\ProductInterface;

interface GetRatingStarsInterface
{
    /**
     * @param ProductInterface[] $products
     *
     * @return float
     */
    public function execute(array $products);
}
