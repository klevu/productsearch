<?php

namespace Klevu\Search\Test\Unit\Provider\Catalog\Product\Review;

use Klevu\Search\Provider\Catalog\Product\Review\MagentoReviewCountDataProvider;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MagentoReviewCountDataProviderTest extends TestCase
{
    /**
     * @var ReviewFactory|MockObject
     */
    private $mockReviewFactory;
    /**
     * @var Review|MockObject
     */
    private $mockReview;
    /**
     * @var MockObject|LoggerInterface
     */
    private $mockLogger;

    public function testReturnsInt()
    {
        $this->setupPhp5();

        $expectedCount = 10;
        $productId = 1;
        $storeId = 1;

        $this->mockReviewFactory->expects($this->once())->method('create')->willReturn($this->mockReview);

        $this->mockReview->expects($this->once())
            ->method('getTotalReviews')
            ->with(
                $productId,
                MagentoReviewCountDataProvider::REVIEWS_APPROVED_ONLY,
                $storeId
            )
            ->willReturn($expectedCount);

        $dataProvider = $this->instantiateRatingDataProvider();
        $count = $dataProvider->getData($productId, $storeId);

        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($count);
        } else {
            $this->assertTrue(is_int($count), 'Is Int');
        }
        $this->assertSame($expectedCount, $count);
    }

    /**
     * @dataProvider incorrectProductIdDataProvider
     */
    public function testHandlesIncorrectValuesForProductId($productId)
    {
        $this->setupPhp5();
        $storeId = 1;

        $this->mockLogger->expects($this->once())->method('error');

        $this->mockReviewFactory->expects($this->never())->method('create');
        $this->mockReview->expects($this->never())->method('getTotalReviews');

        $dataProvider = $this->instantiateRatingDataProvider();
        $dataProvider->getData($productId, $storeId);
    }

    /**
     * @return array
     */
    public function incorrectProductIdDataProvider()
    {
        return [
            ['product_id'],
            [0],
            [-10],
            [[1]],
            [json_decode(json_encode([1, 2, 3]))],
            [true],
            [false],
            [null]
        ];
    }

    /**
     * @return MagentoReviewCountDataProvider
     */
    private function instantiateRatingDataProvider()
    {
        return new MagentoReviewCountDataProvider(
            $this->mockReviewFactory,
            $this->mockLogger
        );
    }

    /**
     * @return void
     */
    private function setupPhp5()
    {
        $this->mockReviewFactory = $this->getMockBuilder(ReviewFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockReview = $this->getMockBuilder(Review::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
    }
}
