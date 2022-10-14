<?php

namespace Klevu\Search\Test\Unit\Service\Catalog\Product;

use Klevu\Search\Model\Attribute\ReviewCount as ReviewCountAttribute;
use Klevu\Search\Service\Catalog\Product\GetReviewCount;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetReviewCountTest extends TestCase
{
    public function testProductReturnsInt()
    {
        $expectedRatingCount = 10;

        $mockProduct = $this->getProduct();
        $mockProduct->method('getId')->willReturn(1);
        $mockProduct->method('getData')
            ->with(ReviewCountAttribute::ATTRIBUTE_CODE)
            ->willReturn($expectedRatingCount);
        $getRatingsCountService = $this->instantiateGetRatingsCountService();
        $ratingCount = $getRatingsCountService->execute([$mockProduct]);

        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($ratingCount);
        } else {
            $this->assertTrue(is_int($ratingCount), 'Is Int');
        }
        $this->assertSame($expectedRatingCount, $ratingCount);
    }

    public function testMultipleProductsReturnCombinedCount()
    {
        $expectedRatingCount = 15;

        $mockProduct1 = $this->getProduct();
        $mockProduct1->method('getId')->willReturn(1);
        $mockProduct1->method('getData')
            ->with(ReviewCountAttribute::ATTRIBUTE_CODE)
            ->willReturn(10);

        $mockProduct2 = $this->getProduct();
        $mockProduct2->method('getId')->willReturn(2);
        $mockProduct2->method('getData')
            ->with(ReviewCountAttribute::ATTRIBUTE_CODE)
            ->willReturn(5);

        $getRatingsCountService = $this->instantiateGetRatingsCountService();
        $ratingCount = $getRatingsCountService->execute([$mockProduct1, $mockProduct2]);

        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($ratingCount);
        } else {
            $this->assertTrue(is_int($ratingCount), 'Is Int');
        }
        $this->assertSame($expectedRatingCount, $ratingCount);
    }

    public function testIgnoresProductsWithNoRating()
    {
        $expectedRatingCount = 25;

        $mockProduct1 = $this->getProduct();
        $mockProduct1->method('getId')->willReturn(1);
        $mockProduct1->method('getData')
            ->with(ReviewCountAttribute::ATTRIBUTE_CODE)
            ->willReturn($expectedRatingCount);

        $mockProduct2 = $this->getProduct();
        $mockProduct2->method('getId')->willReturn(2);
        $mockProduct2->method('getData')
            ->with(ReviewCountAttribute::ATTRIBUTE_CODE)
            ->willReturn(null);

        $getRatingsCountService = $this->instantiateGetRatingsCountService();
        $ratingCount = $getRatingsCountService->execute([$mockProduct1, $mockProduct2]);

        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($ratingCount);
        } else {
            $this->assertTrue(is_int($ratingCount), 'Is Int');
        }
        $this->assertSame($expectedRatingCount, $ratingCount);
    }

    /**
     * @return ProductInterface|Product|MockObject
     */
    private function getProduct()
    {
        return $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return GetReviewCount
     */
    private function instantiateGetRatingsCountService()
    {
        return new GetReviewCount();
    }
}
