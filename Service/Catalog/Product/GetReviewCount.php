<?php

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\GetReviewCountInterface;
use Klevu\Search\Model\Attribute\ReviewCount as ReviewCountAttribute;
use Magento\Catalog\Api\Data\ProductInterface;

class GetReviewCount implements GetReviewCountInterface
{
    /**
     * Service to calculate the total number of ratings of all products supplied
     * Has no opinion on how product types should be handled or the relationship between supplied products.
     *
     * @param ProductInterface[] $products
     *
     * @return int
     */
    public function execute(array $products)
    {
        $reviewsCount = 0;
        $products = array_filter(
            array_unique($products, SORT_REGULAR),
            static function ($product) {
                return ($product instanceof ProductInterface)
                    && $product->getId()
                    && is_numeric($product->getData(ReviewCountAttribute::ATTRIBUTE_CODE));
            }
        );

        foreach ($products as $product) {
            $reviewsCount += (int)$product->getData(ReviewCountAttribute::ATTRIBUTE_CODE);
        }

        return $reviewsCount;
    }
}
