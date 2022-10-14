<?php

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\GetRatingStarsInterface;
use Klevu\Search\Api\Service\Catalog\Product\Review\ConvertRatingToStarsInterface;
use Klevu\Search\Api\Service\Catalog\Product\Review\GetAverageRatingInterface;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingTypeException;
use Magento\Catalog\Api\Data\ProductInterface;
use Psr\Log\LoggerInterface;

class GetRatingStars implements GetRatingStarsInterface
{
    /**
     * @var ConvertRatingToStarsInterface
     */
    private $convertRatingToStars;
    /**
     * @var GetAverageRatingInterface
     */
    private $getAverageRating;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param GetAverageRatingInterface $getAverageRating
     * @param ConvertRatingToStarsInterface $convertRatingToStars
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetAverageRatingInterface $getAverageRating,
        ConvertRatingToStarsInterface $convertRatingToStars,
        LoggerInterface $logger
    ) {
        $this->getAverageRating = $getAverageRating;
        $this->convertRatingToStars = $convertRatingToStars;
        $this->logger = $logger;
    }

    /**
     * @param ProductInterface[] $products
     *
     * @return float|null
     */
    public function execute(array $products)
    {
        $averageRating = $this->getAverageRating->execute($products);

        try {
            $ratingStars = $this->convertRatingToStars->execute($averageRating);
        } catch (InvalidRatingTypeException $exception) {
            $this->logger->error($exception->getMessage(), ['exception_class' => get_class($exception)]);
            $ratingStars = null;
        }

        return $ratingStars;
    }
}
