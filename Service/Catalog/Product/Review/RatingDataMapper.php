<?php

namespace Klevu\Search\Service\Catalog\Product\Review;

use Klevu\Search\Api\Service\Catalog\Product\Review\RatingDataMapperInterface;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingDataMappingKey;

class RatingDataMapper implements RatingDataMapperInterface
{
    const RATING_AVERAGE = 'average';
    const RATING_COUNT = 'count';
    const RATING_PRODUCT_ID = 'entity_pk_value';
    const RATING_STORE = 'store';
    const RATING_SUM = 'sum';
    const REVIEW_COUNT = 'review_count';

    /**
     * @var array
     * use di.xml to inject fields to map your data collection
     * e.g
     * <type name="Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper">
     *  <arguments>
     *   <argument name="fieldMapping" xsi:type="array">
     *    <item name="average" xsi:type="string">mean</item>
     *    <item name="count" xsi:type="string">number</item>
     *    <item name="entity_pk_value" xsi:type="string">product_id</item>
     *    <item name="review_count" xsi:type="string">review_count</item>
     *    <item name="store" xsi:type="string">store</item>
     *    <item name="sum" xsi:type="string">total</item>
     *   </argument>
     *  </arguments>
     * </type>
     */
    private $fieldMapping;

    /**
     * @param array $fieldMapping
     */
    public function __construct(array $fieldMapping)
    {
        $this->fieldMapping = $fieldMapping;
    }

    /**
     * @param array $ratings
     *
     * @return array[]
     * @throws InvalidRatingDataMappingKey
     */
    public function execute(array $ratings)
    {
        if (!$this->fieldMapping) {
            return $ratings;
        }
        $this->validateFieldMapping();

        return $this->mapRatings($ratings);
    }

    /**
     * @param array $ratings
     *
     * @return array[]
     */
    private function mapRatings(array $ratings)
    {
        return array_map(function ($rating) {
            $mappedRating = [];
            foreach ($this->fieldMapping as $key => $fieldMap) {
                if (!array_key_exists($fieldMap, $rating)) {
                    continue;
                }
                $mappedRating[$key] = $rating[$fieldMap];
            }

            return $mappedRating;
        }, $ratings);
    }

    /**
     * @return void
     * @throws InvalidRatingDataMappingKey
     */
    private function validateFieldMapping()
    {
        $allowedKeys = [
            self::RATING_AVERAGE,
            self::RATING_COUNT,
            self::RATING_PRODUCT_ID,
            self::RATING_STORE,
            self::RATING_SUM,
            self::REVIEW_COUNT,
        ];

        foreach ($allowedKeys as $key) {
            if (!array_key_exists($key, $this->fieldMapping)) {
                throw new InvalidRatingDataMappingKey(
                    __('The rating data mapping key %1 is missing.', $key)
                );
            }
        }
    }
}
