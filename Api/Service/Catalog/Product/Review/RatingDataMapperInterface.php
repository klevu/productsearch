<?php

namespace Klevu\Search\Api\Service\Catalog\Product\Review;

use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingDataMappingKey;

interface RatingDataMapperInterface
{
    /**
     * @param array $ratings
     *
     * @return array
     * @throws InvalidRatingDataMappingKey
     */
    public function execute(array $ratings);
}
