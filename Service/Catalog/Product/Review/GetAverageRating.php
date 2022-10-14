<?php

namespace Klevu\Search\Service\Catalog\Product\Review;

use Klevu\Search\Api\Service\Catalog\Product\Review\GetAverageRatingInterface;
use Klevu\Search\Model\Attribute\Rating as RatingAttribute;
use Klevu\Search\Model\Attribute\ReviewCount as ReviewCountAttribute;
use Magento\Catalog\Api\Data\ProductInterface;
use Psr\Log\LoggerInterface;

class GetAverageRating implements GetAverageRatingInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Service to calculate the average rating of all products supplied
     * Has no opinion on how product types should be handled or the relationship between supplied products.
     *
     * @param ProductInterface[] $products
     *
     * @return float|null
     */
    public function execute(array $products)
    {
        $count = 0;
        $ratings = [];
        $products = $this->filterInvalidProducts($products);
        $isSingleProduct = (1 === count($products));
        foreach ($products as $product) {
            list($productRating, $productReviewCount) = $this->getRating($product);
            if (null === $productRating) {
                continue;
            }

            if (null === $productReviewCount && $isSingleProduct) {
                $productReviewCount = 1;
            }

            $ratings[] = $productRating;
            $count += $productReviewCount;
        }

        return $this->getAverageRating($ratings, $count);
    }

    /**
     * @param array $products
     *
     * @return array
     */
    private function filterInvalidProducts(array $products)
    {
        return array_filter(
            array_unique($products, SORT_REGULAR),
            function ($product) {
                return ($product instanceof ProductInterface)
                    && $product->getId()
                    && $this->isRatingValid($product)
                    && $this->isRatingCountValid($product);
            }
        );
    }

    /**
     * @param ProductInterface $product
     *
     * @return array
     */
    private function getRating(ProductInterface $product)
    {
        $rating = $product->getData(RatingAttribute::ATTRIBUTE_CODE);
        if (null === $rating) {
            return [null, null];
        }

        $reviewCount = $product->getData(ReviewCountAttribute::ATTRIBUTE_CODE);
        if (null === $reviewCount) {
            return [(float)$rating, null];
        }

        $productRating = $rating * $reviewCount;

        return [(float)$productRating, (int)$reviewCount];
    }

    /**
     * @param array $ratings
     * @param int $count
     *
     * @return float|null
     */
    private function getAverageRating(array $ratings, $count)
    {
        $ratings = array_filter($ratings, static function ($rating) {
            return is_numeric($rating);
        });
        if (!$count || !is_numeric($count) || empty($ratings)) {
            return null;
        }
        $totalRatings = array_sum($ratings);

        return (float)$totalRatings / (int)$count;
    }

    /**
     * @param ProductInterface $product
     *
     * @return bool
     */
    private function isRatingValid(ProductInterface $product)
    {
        $return = true;
        if (!$product->hasData(RatingAttribute::ATTRIBUTE_CODE)) {
            return $return;
        }

        $rating = $product->getData(RatingAttribute::ATTRIBUTE_CODE);
        if (!is_numeric($rating)) {
            $this->logger->error(
                sprintf(
                    'Product Rating data is not numeric for product ID %s',
                    $product->getId()
                )
            );

            $return = false;
        } elseif ($rating < 0) {
            $this->logger->error(
                sprintf(
                    'Product Rating value (%s) is less than 0 for product ID %s',
                    $rating,
                    $product->getId()
                )
            );

            $return = false;
        }

        return $return;
    }

    /**
     * @param ProductInterface $product
     *
     * @return bool
     */
    private function isRatingCountValid(ProductInterface $product)
    {
        $return = true;
        if (!$product->hasData(ReviewCountAttribute::ATTRIBUTE_CODE)) {
            return $return;
        }

        $reviewCount = $product->getData(ReviewCountAttribute::ATTRIBUTE_CODE);
        if (!is_numeric($reviewCount)) {
            $this->logger->error(
                sprintf(
                    'Product Review Count data is not numeric for product ID %s',
                    $product->getId()
                )
            );

            $return = false;
        } elseif ($reviewCount < 0) {
            $this->logger->error(
                sprintf(
                    'Product Review Count value (%s) is less than 0 for product ID %s',
                    $reviewCount,
                    $product->getId()
                )
            );

            $return = false;
        }

        return $return;
    }
}
