<?php

namespace Klevu\Search\Api\Service\Catalog\Product;

use Magento\Catalog\Api\Data\ProductInterface;

interface GetReviewCountInterface
{
    /**
     * @param ProductInterface[] $products
     *
     * @return int
     */
    public function execute(array $products);
}
