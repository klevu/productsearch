<?php

namespace Klevu\Search\Test\Integration\Service\Catalog\Product\Review;

use Klevu\Search\Api\Service\Catalog\Product\Review\RatingDataMapperInterface;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingDataMappingKey;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class RatingDataMapperTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testUpdateRatingImplementsUpdateRatingInterface()
    {
        $this->setUpPhp5();

        $ratingDataMapperService = $this->instantiateRatingDataMapperService();

        $this->assertInstanceOf(RatingDataMapperInterface::class, $ratingDataMapperService);
    }

    public function testDefaultFieldMappingIsReturnedFromDiXml()
    {
        $this->setUpPhp5();

        $ratings = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_COUNT => 3,
                RatingDataMapper::RATING_AVERAGE => 80.0,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10
            ]
        ];

        $ratingDataMapperService = $this->instantiateRatingDataMapperService();
        $mappedRatings = $ratingDataMapperService->execute($ratings);

        if (method_exists($this, 'assertIsArray()')) {
            $this->assertIsArray($mappedRatings);
        } else {
            $this->assertTrue(is_array($mappedRatings), 'Is Array');
        }
        $this->assertArrayHasKey(0, $mappedRatings);
        $mappedRating = $mappedRatings[0];
        if (method_exists($this, 'assertIsArray()')) {
            $this->assertIsArray($mappedRating);
        } else {
            $this->assertTrue(is_array($mappedRating), 'Is Array');
        }

        $this->assertArrayHasKey(RatingDataMapper::RATING_PRODUCT_ID, $mappedRating);
        $this->assertSame(1, $mappedRating[RatingDataMapper::RATING_PRODUCT_ID]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_SUM, $mappedRating);
        $this->assertSame(240, $mappedRating[RatingDataMapper::RATING_SUM]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_COUNT, $mappedRating);
        $this->assertSame(3, $mappedRating[RatingDataMapper::RATING_COUNT]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_AVERAGE, $mappedRating);
        $this->assertSame(80.0, $mappedRating[RatingDataMapper::RATING_AVERAGE]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_STORE, $mappedRating);
        $this->assertSame(1, $mappedRating[RatingDataMapper::RATING_STORE]);

        $this->assertArrayHasKey(RatingDataMapper::REVIEW_COUNT, $mappedRating);
        $this->assertSame(10, $mappedRating[RatingDataMapper::REVIEW_COUNT]);
    }

    public function testCustomFieldMappingReturnsCorrectData()
    {
        $this->setUpPhp5();

        $ratings = [
            [
                'product_id' => 1,
                'total' => 240,
                'number' => 3,
                'mean' => 80.0,
                'store_id' => 1,
                'count' => 10
            ]
        ];

        $fieldMapping = [
            RatingDataMapper::RATING_PRODUCT_ID => 'product_id',
            RatingDataMapper::RATING_SUM => 'total',
            RatingDataMapper::RATING_COUNT => 'number',
            RatingDataMapper::RATING_AVERAGE => 'mean',
            RatingDataMapper::RATING_STORE => 'store_id',
            RatingDataMapper::REVIEW_COUNT => 'count'
        ];

        $ratingDataMapperService = $this->instantiateRatingDataMapperService($fieldMapping);
        $mappedRatings = $ratingDataMapperService->execute($ratings);

        if (method_exists($this, 'assertIsArray()')) {
            $this->assertIsArray($mappedRatings);
        } else {
            $this->assertTrue(is_array($mappedRatings), 'Is Array');
        }
        $this->assertArrayHasKey(0, $mappedRatings);
        $mappedRating = $mappedRatings[0];
        if (method_exists($this, 'assertIsArray()')) {
            $this->assertIsArray($mappedRating);
        } else {
            $this->assertTrue(is_array($mappedRating), 'Is Array');
        }

        $this->assertArrayHasKey(RatingDataMapper::RATING_PRODUCT_ID, $mappedRating);
        $this->assertSame(1, $mappedRating[RatingDataMapper::RATING_PRODUCT_ID]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_SUM, $mappedRating);
        $this->assertSame(240, $mappedRating[RatingDataMapper::RATING_SUM]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_COUNT, $mappedRating);
        $this->assertSame(3, $mappedRating[RatingDataMapper::RATING_COUNT]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_AVERAGE, $mappedRating);
        $this->assertSame(80.0, $mappedRating[RatingDataMapper::RATING_AVERAGE]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_STORE, $mappedRating);
        $this->assertSame(1, $mappedRating[RatingDataMapper::RATING_STORE]);

        $this->assertArrayHasKey(RatingDataMapper::REVIEW_COUNT, $mappedRating);
        $this->assertSame(10, $mappedRating[RatingDataMapper::REVIEW_COUNT]);
    }

    /**
     * @dataProvider missingMappingFieldsDataProvider
     */
    public function testExceptionIsThrownIfMappingIsMissingForField($missingField)
    {
        $this->setUpPhp5();

        $ratings = [];
        $fieldMapping = [
            RatingDataMapper::RATING_PRODUCT_ID => 'entity_pk_value',
            RatingDataMapper::RATING_SUM => 'sum',
            RatingDataMapper::RATING_COUNT => 'count',
            RatingDataMapper::RATING_AVERAGE => 'average',
            RatingDataMapper::RATING_STORE => 'store',
            RatingDataMapper::REVIEW_COUNT => 'review_count'
        ];
        $fieldMapping = array_filter($fieldMapping, function ($field) use ($missingField) {
            return $field !== $missingField;
        });

        $this->expectException(InvalidRatingDataMappingKey::class);
        $this->expectExceptionMessage(
            sprintf('The rating data mapping key %s is missing.', $missingField)
        );

        $ratingDataMapperService = $this->instantiateRatingDataMapperService($fieldMapping);
        $ratingDataMapperService->execute($ratings);
    }


    /**
     * @return array[]
     */
    public function missingMappingFieldsDataProvider()
    {
        return [
            [RatingDataMapper::RATING_PRODUCT_ID],
            [RatingDataMapper::RATING_SUM],
            [RatingDataMapper::RATING_COUNT],
            [RatingDataMapper::RATING_AVERAGE],
            [RatingDataMapper::RATING_STORE],
            [RatingDataMapper::REVIEW_COUNT]
        ];
    }

    /**
     * @return RatingDataMapper
     */
    private function instantiateRatingDataMapperService($fieldMapping = null)
    {
        if (!$fieldMapping) {
            return $this->objectManager->create(RatingDataMapper::class);
        }

        return $this->objectManager->create(RatingDataMapper::class, [
            'fieldMapping' => $fieldMapping
        ]);
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

}
