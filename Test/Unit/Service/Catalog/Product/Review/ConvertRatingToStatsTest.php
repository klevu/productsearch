<?php

namespace Klevu\Search\Test\Unit\Service\Catalog\Product\Review;

use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingTypeException;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingOutOfTypeException;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidMaxStarsTypeException;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidPrecisionTypeException;
use Klevu\Search\Service\Catalog\Product\Review\ConvertRatingToStars;
use PHPUnit\Framework\TestCase;

class ConvertRatingToStatsTest extends TestCase
{
    public function testReturns5StarsFor100Rating()
    {
        $convertRatingToStars = $this->instantiateConvertRatingToStarsService();
        $stars = $convertRatingToStars->execute(100.00);

        $this->assertSame(5.00, $stars);
    }

    public function testReturns10StarsForRating100WhenMaxStarsIs10()
    {
        $convertRatingToStars = $this->instantiateConvertRatingToStarsService(100, 10);
        $stars = $convertRatingToStars->execute(100.00);

        $this->assertSame(10.00, $stars);
    }

    public function testReturns5StarsForRating75WhenRatingOutOf75()
    {
        $convertRatingToStars = $this->instantiateConvertRatingToStarsService(75);
        $stars = $convertRatingToStars->execute(75.00);

        $this->assertSame(5.00, $stars);
    }

    /**
     * @dataProvider ratingZeroDataProvider
     */
    public function testReturnsZeroWhenRatingIsZero($rating)
    {
        $convertRatingToStars = $this->instantiateConvertRatingToStarsService();
        $stars = $convertRatingToStars->execute($rating);

        $this->assertSame(0.00, $stars);
    }

    /**
     * @dataProvider InvalidRatingDataProvider
     */
    public function testThrowsExceptionWhenInvalidRatingProvided($rating)
    {
        $this->expectException(InvalidRatingTypeException::class);
        $this->expectExceptionMessage('Invalid Rating ($rating) Provided');

        $convertRatingToStars = $this->instantiateConvertRatingToStarsService();
        $convertRatingToStars->execute($rating);
    }

    /**
     * @dataProvider InvalidRatingOutOfDataProvider
     */
    public function testDefaultValueIsUsedWhenInvalidRatingOutOfProvided($ratingOutOf)
    {
        $convertRatingToStars = $this->instantiateConvertRatingToStarsService($ratingOutOf);
        $stars = $convertRatingToStars->execute(50);

        $this->assertSame(2.5, $stars);
    }

    /**
     * @dataProvider InvalidMaxStarsDataProvider
     */
    public function testDefaultValueIsUsedWhenInvalidMaxNumberOfStarsProvided($maxStars)
    {
        $convertRatingToStars = $this->instantiateConvertRatingToStarsService(100, $maxStars);
        $stars = $convertRatingToStars->execute(50);

        $this->assertSame(2.5, $stars);
    }

    /**
     * @dataProvider InvalidPrecisionDataProvider
     */
    public function testDefaultValueIsUsedWhenInvalidPrecisionProvided($precision)
    {
        $convertRatingToStars = $this->instantiateConvertRatingToStarsService(100, 5, $precision);
        $stars = $convertRatingToStars->execute(50);

        $this->assertSame(2.5, $stars);

    }

    /**
     * @return array
     */
    public function ratingZeroDataProvider()
    {
        return [
            [0],
            [0.00],
            [0.0000],
            ['0'],
            ['0.00'],
            ['0.0000'],
        ];
    }

    /**
     * @return array
     */
    public function InvalidRatingDataProvider()
    {
        return [
            [null],
            [false],
            [true],
            [-10],
            [-10.00],
            ['-10'],
            ['-10.00'],
            [[0]],
            [[123, 456]],
            ['string'],
            [['123', 'string']],
            [json_decode(json_encode(['a' => 'b']))],
        ];
    }

    /**
     * @return array
     */
    public function InvalidRatingOutOfDataProvider()
    {
        return [
            ['0.00'],
            ['0.0000'],
            [-10],
            [-10.00],
            [-10.0000],
            ['-10'],
            ['-10.00'],
            ['-10.0000'],
            [true],
            ['string'],
            [[123, 456]],
            ['string'],
            [['123', 'string']],
            [json_decode(json_encode(['a' => 'b']))],
        ];
    }

    /**
     * @return array
     */
    public function InvalidMaxStarsDataProvider()
    {
        return [
            ['0.00'],
            ['0.0000'],
            [-10],
            [-10.00],
            [-10.0000],
            ['-10'],
            ['-10.00'],
            ['-10.0000'],
            [true],
            ['string'],
            [[123, 456]],
            ['string'],
            [['123', 'string']],
            [json_decode(json_encode(['a' => 'b']))],
        ];
    }

    /**
     * @return array
     */
    public function InvalidPrecisionDataProvider()
    {
        return [
            [1.2],
            [123.456],
            [-10],
            [-10.00],
            [-10.0000],
            ['-10'],
            ['-10.00'],
            ['-10.0000'],
            [true],
            [false],
            ['string'],
            [[123, 456]],
            ['string'],
            [['123', 'string']],
            [json_decode(json_encode(['a' => 'b']))],
        ];
    }

    /**
     * @return ConvertRatingToStars
     */
    private function instantiateConvertRatingToStarsService($ratingOutOf = null, $maxStars = null, $precision = null)
    {
        return new ConvertRatingToStars($ratingOutOf, $maxStars, $precision);
    }
}
