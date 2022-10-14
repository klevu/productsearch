<?php

namespace Klevu\Search\Test\Unit\Service\Catalog\Product;

use Klevu\Search\Service\Catalog\Product\GetRatingStars;
use Klevu\Search\Service\Catalog\Product\Review\ConvertRatingToStars;
use Klevu\Search\Service\Catalog\Product\Review\GetAverageRating;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingTypeException;

class GetRatingStarsTest extends TestCase
{
    /**
     * @var GetAverageRating|MockObject
     */
    private $mockGetAverageRating;
    /**
     * @var ConvertRatingToStars|MockObject
     */
    private $mockConvertRatingToStars;
    /**
     * @var LoggerInterface|MockObject
     */
    private $mockLogger;

    public function testReturnsFloat()
    {
        $this->setupPhp5();

        $mockProduct = $this->getMockProduct();

        $this->mockGetAverageRating->expects($this->once())->method('execute')->willReturn(50.0);
        $this->mockConvertRatingToStars->expects($this->once())->method('execute')->willReturn(2.5);

        $getRatingService = $this->instantiateGetRatingsService();
        $rating = $getRatingService->execute([$mockProduct]);

        if (method_exists($this, 'assertIsFloat')) {
            $this->assertIsFloat($rating);
        } else {
            $this->assertTrue(is_float($rating), 'Is Float');
        }
        $expectedRating = 2.5;
        $this->assertSame($expectedRating, $rating);
    }

    public function testReturnsNullIfExceptionIsThrown()
    {
        $this->setupPhp5();

        $mockProduct = $this->getMockProduct();

        $this->mockGetAverageRating->expects($this->once())
            ->method('execute')
            ->willReturn(50.0);

        $mockInvalidRatingTypeException = $this->getMockBuilder(InvalidRatingTypeException::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockConvertRatingToStars->expects($this->once())
            ->method('execute')
            ->willThrowException($mockInvalidRatingTypeException);

        $getRatingService = $this->instantiateGetRatingsService();
        $rating = $getRatingService->execute([$mockProduct]);

        if (method_exists($this, 'assertIsNull')) {
            $this->assertIsNull($rating);
        } else {
            $this->assertTrue(is_null($rating), 'Is NUll');
        }
        $expectedRating = null;
        $this->assertSame($expectedRating, $rating);
    }

    /**
     * @return ProductInterface|Product|MockObject
     */
    private function getMockProduct()
    {
        return $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function setupPhp5()
    {
        $this->mockGetAverageRating = $this->getMockBuilder(GetAverageRating::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockConvertRatingToStars = $this->getMockBuilder(ConvertRatingToStars::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return GetRatingStars
     */
    private function instantiateGetRatingsService()
    {
        return new GetRatingStars(
            $this->mockGetAverageRating,
            $this->mockConvertRatingToStars,
            $this->mockLogger
        );
    }
}
