<?php

namespace Klevu\Search\Api\Service\Catalog\Product\Review;

use Klevu\Search\Exception\Catalog\Product\Review\KlevuProductAttributeMissingException;
use Magento\Catalog\Api\Data\ProductInterface;

interface UpdateRatingInterface
{
    /**
     * @param ProductInterface $product
     *
     * @return void
     * @throws KlevuProductAttributeMissingException
     */
    public function execute(ProductInterface $product);
}
