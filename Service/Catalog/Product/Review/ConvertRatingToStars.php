<?php

namespace Klevu\Search\Service\Catalog\Product\Review;

use Klevu\Search\Api\Service\Catalog\Product\Review\ConvertRatingToStarsInterface;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingTypeException;

class ConvertRatingToStars implements ConvertRatingToStarsInterface
{
    const RATING_OUT_OF = 100;
    const RATING_MAX_NUMBER_OF_STARS = 5;
    const RATING_STAR_PRECISION = 2;

    /**
     * @var int
     */
    private $ratingOutOf;
    /**
     * @var int
     */
    private $maxStars;
    /**
     * @var int
     */
    private $precision;

    /**
     * @param string|int|null $ratingOutOf
     * @param string|int|null $maxStars
     * @param string|int|null $precision
     */
    public function __construct(
        $ratingOutOf,
        $maxStars,
        $precision
    ) {
        $this->ratingOutOf = $this->getValidRatingOutOf($ratingOutOf);
        $this->maxStars = $this->getValidMaxStars($maxStars);
        $this->precision = $this->getValidPrecision($precision);
    }

    /**
     * @param float|int $rating
     *
     * @return float
     * @throws InvalidRatingTypeException
     */
    public function execute($rating)
    {
        $this->validate($rating);
        if ((float)$rating === 0.0) {
            return 0.00;
        }
        $starRating = ((float)$rating / $this->ratingOutOf) * $this->maxStars;

        return round($starRating, $this->precision);
    }

    /**
     * @param float|int $rating
     *
     * @return void
     * @throws InvalidRatingTypeException
     */
    private function validate($rating)
    {
        if (!is_numeric($rating) || $rating < 0) {
            throw new InvalidRatingTypeException(__('Invalid Rating ($rating) Provided'));
        }
    }

    /**
     * @param string|int|null $ratingOutOf
     *
     * @return int
     */
    private function getValidRatingOutOf($ratingOutOf)
    {
        return (is_numeric($ratingOutOf) && $ratingOutOf > 0) ?
            (int)$ratingOutOf :
            self::RATING_OUT_OF;
    }

    /**
     * @param string|int|null $maxStars
     *
     * @return int
     */
    private function getValidMaxStars($maxStars)
    {
        return (is_numeric($maxStars) && $maxStars > 0) ?
            (int)$maxStars :
            self::RATING_MAX_NUMBER_OF_STARS;
    }

    /**
     * @param string|int|null $precision
     *
     * @return int
     */
    private function getValidPrecision($precision)
    {
        return (is_numeric($precision) && $precision >= 0) ?
            (int)$precision :
            self::RATING_STAR_PRECISION;
    }
}
