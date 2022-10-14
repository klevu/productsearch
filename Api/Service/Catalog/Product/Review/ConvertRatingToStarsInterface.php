<?php

namespace Klevu\Search\Api\Service\Catalog\Product\Review;

use Klevu\Search\Exception\Catalog\Product\Review\InvalidMaxStarsTypeException;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingOutOfTypeException;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingTypeException;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidPrecisionTypeException;

interface ConvertRatingToStarsInterface
{
    /**
     * @param float|int $rating
     *
     * @return float
     * @throws InvalidRatingTypeException
     * @throws InvalidRatingOutOfTypeException
     * @throws InvalidMaxStarsTypeException
     * @throws InvalidPrecisionTypeException
     */
    public function execute($rating);
}
