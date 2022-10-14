<?php

namespace Klevu\Search\Test\Unit\Service\Catalog\Product\Review;

use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingDataMappingKey;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use PHPUnit\Framework\TestCase;

class RatingDataMapperTest extends TestCase
{
    public function testReturnsOriginalRatingIfFieldMappingDefault()
    {
        $fieldMapping = [
            RatingDataMapper::RATING_PRODUCT_ID => RatingDataMapper::RATING_PRODUCT_ID,
            RatingDataMapper::RATING_SUM => RatingDataMapper::RATING_SUM,
            RatingDataMapper::RATING_COUNT => RatingDataMapper::RATING_COUNT,
            RatingDataMapper::RATING_AVERAGE => RatingDataMapper::RATING_AVERAGE,
            RatingDataMapper::RATING_STORE => RatingDataMapper::RATING_STORE,
            RatingDataMapper::REVIEW_COUNT => RatingDataMapper::REVIEW_COUNT,
        ];
        $ratings = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_COUNT => 3,
                RatingDataMapper::RATING_AVERAGE => 80.0,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 3,
            ],
            [
                RatingDataMapper::RATING_PRODUCT_ID => 3,
                RatingDataMapper::RATING_SUM => 200,
                RatingDataMapper::RATING_COUNT => 4,
                RatingDataMapper::RATING_AVERAGE => 50.0,
                RatingDataMapper::RATING_STORE => 2,
                RatingDataMapper::REVIEW_COUNT => 9,
            ],
        ];
        $dataMapping = $this->instantiateRatingDataMapping($fieldMapping);
        $mappedRatings = $dataMapping->execute($ratings);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($mappedRatings);
        } else {
            $this->assertTrue(is_array($mappedRatings), 'Is Array');
        }

        $this->assertArrayHasKey(0, $mappedRatings);
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($mappedRatings[0]);
        } else {
            $this->assertTrue(is_array($mappedRatings[0]), 'Is Array');
        }
        $this->assertArrayHasKey(RatingDataMapper::RATING_PRODUCT_ID, $mappedRatings[0]);
        $this->assertSame(1, $mappedRatings[0][RatingDataMapper::RATING_PRODUCT_ID]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_SUM, $mappedRatings[0]);
        $this->assertSame(240, $mappedRatings[0][RatingDataMapper::RATING_SUM]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_COUNT, $mappedRatings[0]);
        $this->assertSame(3, $mappedRatings[0][RatingDataMapper::RATING_COUNT]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_AVERAGE, $mappedRatings[0]);
        $this->assertSame(80.0, $mappedRatings[0][RatingDataMapper::RATING_AVERAGE]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_STORE, $mappedRatings[0]);
        $this->assertSame(1, $mappedRatings[0][RatingDataMapper::RATING_STORE]);

        $this->assertArrayHasKey(RatingDataMapper::REVIEW_COUNT, $mappedRatings[0]);
        $this->assertSame(3, $mappedRatings[0][RatingDataMapper::REVIEW_COUNT]);
    }

    public function testReturnsMappedRatingIfFieldMappingIsCustomised()
    {
        $fieldMapping = [
            RatingDataMapper::RATING_PRODUCT_ID => 'product_id',
            RatingDataMapper::RATING_SUM => 'total',
            RatingDataMapper::RATING_COUNT => 'number',
            RatingDataMapper::RATING_AVERAGE => 'mean',
            RatingDataMapper::RATING_STORE => 'site',
            RatingDataMapper::REVIEW_COUNT => 'count',
        ];
        $ratings = [
            [
                'product_id' => 1,
                'total' => 240,
                'number' => 3,
                'mean' => 80.0,
                'site' => 1,
                'count' => 10,
            ],
            [
                'product_id' => 3,
                'total' => 200,
                'number' => 4,
                'mean' => 50.0,
                'site' => 2,
                'count' => 20,
            ],
        ];
        $dataMapping = $this->instantiateRatingDataMapping($fieldMapping);
        $mappedRatings = $dataMapping->execute($ratings);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($mappedRatings);
        } else {
            $this->assertTrue(is_array($mappedRatings), 'Is Array');
        }

        $this->assertArrayHasKey(0, $mappedRatings);
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($mappedRatings[0]);
        } else {
            $this->assertTrue(is_array($mappedRatings[0]), 'Is Array');
        }
        $this->assertArrayHasKey(RatingDataMapper::RATING_PRODUCT_ID, $mappedRatings[0]);
        $this->assertSame(1, $mappedRatings[0][RatingDataMapper::RATING_PRODUCT_ID]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_SUM, $mappedRatings[0]);
        $this->assertSame(240, $mappedRatings[0][RatingDataMapper::RATING_SUM]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_COUNT, $mappedRatings[0]);
        $this->assertSame(3, $mappedRatings[0][RatingDataMapper::RATING_COUNT]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_AVERAGE, $mappedRatings[0]);
        $this->assertSame(80.0, $mappedRatings[0][RatingDataMapper::RATING_AVERAGE]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_STORE, $mappedRatings[0]);
        $this->assertSame(1, $mappedRatings[0][RatingDataMapper::RATING_STORE]);

        $this->assertArrayHasKey(RatingDataMapper::REVIEW_COUNT, $mappedRatings[0]);
        $this->assertSame(10, $mappedRatings[0][RatingDataMapper::REVIEW_COUNT]);
    }

    public function testExceptionIsThrownIfMappingIsNotValid()
    {
        $fieldMapping = [
            'some_incorrect_field' => 'product_id',
            RatingDataMapper::RATING_SUM => 'total',
            RatingDataMapper::RATING_COUNT => 'number',
            RatingDataMapper::RATING_AVERAGE => 'mean',
            RatingDataMapper::RATING_STORE => 'site',
            RatingDataMapper::REVIEW_COUNT => 'count'
        ];
        $ratings = [
            [
                'product_id' => 1,
                'total' => 240,
                'number' => 3,
                'mean' => 80.0,
                'site' => 1,
                'count' => 5
            ],
            [
                'product_id' => 3,
                'total' => 200,
                'number' => 4,
                'mean' => 50.0,
                'site' => 2,
                'count' => 4
            ],
        ];

        $this->expectException(InvalidRatingDataMappingKey::class);
        $this->expectExceptionMessage(
            sprintf('The rating data mapping key %s is missing', RatingDataMapper::RATING_PRODUCT_ID)
        );

        $dataMapping = $this->instantiateRatingDataMapping($fieldMapping);
        $dataMapping->execute($ratings);
    }

    /**
     * @param array $fieldMapping
     *
     * @return RatingDataMapper
     */
    private function instantiateRatingDataMapping(array $fieldMapping = [])
    {
        return new RatingDataMapper(
            $fieldMapping
        );
    }
}
