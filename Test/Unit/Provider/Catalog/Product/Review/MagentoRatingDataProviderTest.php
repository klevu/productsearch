<?php

namespace Klevu\Search\Test\Unit\Provider\Catalog\Product\Review;

use Klevu\Search\Api\Provider\Catalog\Product\Review\ReviewCountDataProviderInterface;
use Klevu\Search\Provider\Catalog\Product\Review\MagentoRatingDataProvider;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use Magento\Review\Model\Rating;
use Magento\Review\Model\RatingFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MagentoRatingDataProviderTest extends TestCase
{
    /**
     * @var Rating|MockObject
     */
    private $mockRating;
    /**
     * @var Rating|MockObject
     */
    private $mockRatingModel;
    /**
     * @var RatingFactory|MockObject
     */
    private $mockRatingFactory;
    /**
     * @var StoreManager|MockObject
     */
    private $mockStoreManager;
    /**
     * @var ReviewCountDataProviderInterface|MockObject
     */
    private $mockReviewCountDataProvider;
    /**
     * @var LoggerInterface|MockObject
     */
    private $mockLogger;

    public function testReturnsSumCountAndAverage()
    {
        $this->setupPhp5();

        $productId = 1;
        $storeId = 1;

        $this->mockStoreManager->expects($this->never())
            ->method('getStore');
        $this->mockStoreManager->expects($this->once())
            ->method('setCurrentStore')
            ->with($storeId);

        $this->mockRating->expects($this->exactly(2))
            ->method('getData')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'sum':
                        return '100';
                    case 'count':
                        return '25';
                    default:
                        return '';
                }
            });

        $this->mockRatingModel->expects($this->once())->method('getEntitySummary')->willReturn($this->mockRating);

        $this->mockRatingFactory->expects($this->once())->method('create')->willReturn($this->mockRatingModel);

        $expectsReviewCount = 10;
        $this->mockReviewCountDataProvider->expects($this->once())
            ->method('getData')
            ->with($productId, $storeId)
            ->willReturn($expectsReviewCount);

        $ratingDataProvider = $this->instantiateRatingDataProvider();
        $ratingData = $ratingDataProvider->getData($productId, $storeId);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($ratingData);
        } else {
            $this->assertTrue(is_array($ratingData), 'Is Array');
        }
        $this->assertArrayHasKey(RatingDataMapper::RATING_SUM, $ratingData);
        $this->assertSame(100.0, $ratingData[RatingDataMapper::RATING_SUM]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_COUNT, $ratingData);
        $this->assertSame(25, $ratingData[RatingDataMapper::RATING_COUNT]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_AVERAGE, $ratingData);
        $this->assertSame(4.0, $ratingData[RatingDataMapper::RATING_AVERAGE]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_STORE, $ratingData);
        $this->assertSame((int)$storeId, $ratingData[RatingDataMapper::RATING_STORE]);

        $this->assertArrayHasKey(RatingDataMapper::REVIEW_COUNT, $ratingData);
        $this->assertSame($expectsReviewCount, $ratingData[RatingDataMapper::REVIEW_COUNT]);
    }

    /**
     * @dataProvider InvalidSumAndCountValuesDataProvider
     */
    public function testReturnsNullValuesIfCountOrSumAreMissing($sum, $count)
    {
        $this->setupPhp5();

        $productId = 1;
        $storeId = 1;

        $this->mockStoreManager->expects($this->never())
            ->method('getStore');
        $this->mockStoreManager->expects($this->once())
            ->method('setCurrentStore')
            ->with($storeId);

        $this->mockRating->expects($this->exactly(2))
            ->method('getData')
            ->willReturnCallback(function ($key) use ($sum, $count) {
                switch ($key) {
                    case 'sum':
                        return $sum;
                    case 'count':
                        return $count;
                    default:
                        return '';
                }
            });

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->once())->method('debug')->with(
            sprintf("Rating Data invalid for StoreId: %s, productId: %s", $storeId, $productId)
        );

        $this->mockRatingModel->expects($this->once())->method('getEntitySummary')->willReturn($this->mockRating);

        $this->mockRatingFactory->expects($this->once())->method('create')->willReturn($this->mockRatingModel);

        $ratingDataProvider = $this->instantiateRatingDataProvider();
        $ratingData = $ratingDataProvider->getData($productId, $storeId);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($ratingData);
        } else {
            $this->assertTrue(is_array($ratingData), 'Is Array');
        }
        $this->assertArrayHasKey(RatingDataMapper::RATING_SUM, $ratingData);
        $this->assertNull($ratingData[RatingDataMapper::RATING_SUM], 'Sum');

        $this->assertArrayHasKey(RatingDataMapper::RATING_COUNT, $ratingData);
        $this->assertSame(0, $ratingData[RatingDataMapper::RATING_COUNT], 'Rating Count');

        $this->assertArrayHasKey(RatingDataMapper::RATING_AVERAGE, $ratingData);
        $this->assertNull($ratingData[RatingDataMapper::RATING_AVERAGE], 'Average');

        $this->assertArrayHasKey(RatingDataMapper::RATING_STORE, $ratingData);
        $this->assertSame((int)$storeId, $ratingData[RatingDataMapper::RATING_STORE], 'Store');

        $this->assertArrayHasKey(RatingDataMapper::REVIEW_COUNT, $ratingData);
        $this->assertSame(0, $ratingData[RatingDataMapper::REVIEW_COUNT], 'Review Count');
    }

    /**
     * @dataProvider ValidSumAndCountValuesDataProvider
     */
    public function testReturnsValuesIfCountOrSumAreValidButUnexpected($sum, $count)
    {
        $this->setupPhp5();

        $productId = 1;
        $storeId = 1;

        $this->mockStoreManager->expects($this->never())
            ->method('getStore');
        $this->mockStoreManager->expects($this->once())
            ->method('setCurrentStore')
            ->with($storeId);

        $this->mockRating->expects($this->exactly(2))
            ->method('getData')
            ->willReturnCallback(function ($key) use ($sum, $count) {
                switch ($key) {
                    case 'sum':
                        return $sum;
                    case 'count':
                        return $count;
                    default:
                        return '';
                }
            });

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('debug');

        $this->mockRatingModel->expects($this->once())->method('getEntitySummary')->willReturn($this->mockRating);

        $this->mockRatingFactory->expects($this->once())->method('create')->willReturn($this->mockRatingModel);

        $ratingDataProvider = $this->instantiateRatingDataProvider();
        $ratingData = $ratingDataProvider->getData($productId, $storeId);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($ratingData);
        } else {
            $this->assertTrue(is_array($ratingData), 'Is Array');
        }
        $this->assertArrayHasKey(RatingDataMapper::RATING_SUM, $ratingData);
        $this->assertSame((float)$sum, $ratingData[RatingDataMapper::RATING_SUM], 'Sum');

        $this->assertArrayHasKey(RatingDataMapper::RATING_COUNT, $ratingData);
        if (is_numeric($count)) {
            $this->assertSame((int)$count, $ratingData[RatingDataMapper::RATING_COUNT], 'Rating Count');
        } else {
            $this->assertNull($ratingData[RatingDataMapper::RATING_COUNT], 'Rating Count');
        }

        $this->assertArrayHasKey(RatingDataMapper::RATING_AVERAGE, $ratingData);
        $this->assertSame((float)$sum, $ratingData[RatingDataMapper::RATING_AVERAGE], 'Average');

        $this->assertArrayHasKey(RatingDataMapper::RATING_STORE, $ratingData);
        $this->assertSame((int)$storeId, $ratingData[RatingDataMapper::RATING_STORE], 'Store');

        $this->assertArrayHasKey(RatingDataMapper::REVIEW_COUNT, $ratingData);
        $this->assertSame(0, $ratingData[RatingDataMapper::REVIEW_COUNT], 'Review Count');
    }

    public function testReturnsSumCountAndAverageWhenStoreIdNotPassedIn()
    {
        $this->setupPhp5();

        $productId = 1;
        $storeId = 1;

        $mockStore = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $mockStore->expects($this->once())->method('getId')->willReturn($storeId);

        $this->mockStoreManager->expects($this->once())
            ->method('getStore')
            ->willReturn($mockStore);
        $this->mockStoreManager->expects($this->once())
            ->method('setCurrentStore')
            ->with($storeId);

        $this->mockRating->expects($this->exactly(2))
            ->method('getData')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'sum':
                        return '100';
                    case 'count':
                        return '25';
                    default:
                        return '';
                }
            });

        $this->mockRatingModel->expects($this->once())->method('getEntitySummary')->willReturn($this->mockRating);

        $this->mockRatingFactory->expects($this->once())->method('create')->willReturn($this->mockRatingModel);

        $expectsReviewCount = 10;
        $this->mockReviewCountDataProvider->expects($this->once())
            ->method('getData')
            ->with($productId, $storeId)
            ->willReturn($expectsReviewCount);

        $ratingDataProvider = $this->instantiateRatingDataProvider();
        $ratingData = $ratingDataProvider->getData($productId, null);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($ratingData);
        } else {
            $this->assertTrue(is_array($ratingData), 'Is Array');
        }
        $this->assertArrayHasKey(RatingDataMapper::RATING_SUM, $ratingData);
        $this->assertSame(100.0, $ratingData[RatingDataMapper::RATING_SUM]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_COUNT, $ratingData);
        $this->assertSame(25, $ratingData[RatingDataMapper::RATING_COUNT]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_AVERAGE, $ratingData);
        $this->assertSame(4.0, $ratingData[RatingDataMapper::RATING_AVERAGE]);

        $this->assertArrayHasKey(RatingDataMapper::RATING_STORE, $ratingData);
        $this->assertSame((int)$storeId, $ratingData[RatingDataMapper::RATING_STORE]);

        $this->assertArrayHasKey(RatingDataMapper::REVIEW_COUNT, $ratingData);
        $this->assertSame($expectsReviewCount, $ratingData[RatingDataMapper::REVIEW_COUNT]);
    }

    /**
     * @return array[]
     */
    public function InvalidSumAndCountValuesDataProvider()
    {
        return [
            ['100', '0'],
            ['100', 0],
            ['-1', 100],
            ['100', -1],
            ['string', '23'],
            [[0], 123],
            [3812, [1]],
            [['string'], 123],
            [13, null],
            [true, 123],
            [false, 846],
            [274, true],
            [24, false],
            [13, null],
        ];
    }

    /**
     * @return array[]
     */
    public function ValidSumAndCountValuesDataProvider()
    {
        return [
            ['0', '100'],
            [0, '100'],
            [null, 123],
        ];
    }

    /**
     * @return MagentoRatingDataProvider
     */
    private function instantiateRatingDataProvider()
    {
        return new MagentoRatingDataProvider(
            $this->mockRatingFactory,
            $this->mockStoreManager,
            $this->mockReviewCountDataProvider,
            $this->mockLogger
        );
    }

    /**
     * @return void
     */
    private function setupPhp5()
    {
        $this->mockRating = $this->getMockBuilder(Rating::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRatingModel = $this->getMockBuilder(Rating::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRatingFactory = $this->getMockBuilder(RatingFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStoreManager = $this->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockReviewCountDataProvider = $this->getMockBuilder(ReviewCountDataProviderInterface::class)
            ->getMock();
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
    }
}
