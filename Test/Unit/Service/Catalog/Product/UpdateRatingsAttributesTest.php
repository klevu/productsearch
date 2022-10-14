<?php

namespace Klevu\Search\Test\Unit\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\IsRatingAttributeAvailableInterface;
use Klevu\Search\Api\Service\Catalog\Product\IsRatingCountAttributeAvailableInterface;
use Klevu\Search\Api\Service\Catalog\Product\Review\RatingDataMapperInterface;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingDataMappingKey;
use Klevu\Search\Model\Attribute\Rating as RatingAttribute;
use Klevu\Search\Model\Attribute\ReviewCount as RatingCountAttribute;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use Klevu\Search\Service\Catalog\Product\UpdateRatingsAttributes;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\Product\ActionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdateRatingsAttributesTest extends TestCase
{
    /**
     * @var Action|MockObject
     */
    private $mockProductActions;
    /**
     * @var ActionFactory|MockObject
     */
    private $mockActionFactory;
    /**
     * @var MockObject|LoggerInterface
     */
    private $mockLogger;
    /**
     * @var RatingDataMapperInterface|MockObject
     */
    private $mockRatingDataMapper;
    /**
     * @var MockObject&IsRatingAttributeAvailableInterface
     */
    private $mockIsRatingAttributeAvailable;
    /**
     * @var MockObject&IsRatingCountAttributeAvailableInterface
     */
    private $mockIsRatingCountAttributeAvailable;

    public function testUpdateAttributesIsCalledWhenRatingAndReviewCountAreAvailable()
    {
        $this->setupPhp5();

        $mockRatingsData = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_COUNT => 3,
                RatingDataMapper::RATING_AVERAGE => 80.0,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10
            ],
        ];

        $this->mockRatingDataMapper->expects($this->once())
            ->method('execute')
            ->with($mockRatingsData)
            ->willReturn($mockRatingsData);

        $this->mockProductActions->expects($this->once())
            ->method('updateAttributes')
            ->with(
                [$mockRatingsData[0][RatingDataMapper::RATING_PRODUCT_ID]],
                [
                    RatingAttribute::ATTRIBUTE_CODE => $mockRatingsData[0][RatingDataMapper::RATING_AVERAGE],
                    RatingCountAttribute::ATTRIBUTE_CODE => $mockRatingsData[0][RatingDataMapper::REVIEW_COUNT],
                ],
                $mockRatingsData[0][RatingDataMapper::RATING_STORE]
            );

        $this->mockActionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mockProductActions);

        $this->mockIsRatingAttributeAvailable->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(true);
        $this->mockIsRatingCountAttributeAvailable->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(true);

        $updateRatings = $this->instantiateUpdateRatingsAttributes();
        $updateRatings->execute($mockRatingsData);
    }

    public function testUpdateAttributesIsCalledWhenRatingIsNotAvailableAndReviewCountIsAvailable()
    {
        $this->setupPhp5();

        $mockRatingsData = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_COUNT => 3,
                RatingDataMapper::RATING_AVERAGE => 80.0,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10
            ],
        ];

        $this->mockRatingDataMapper->expects($this->once())
            ->method('execute')
            ->with($mockRatingsData)
            ->willReturn($mockRatingsData);

        $this->mockProductActions->expects($this->once())
            ->method('updateAttributes')
            ->with(
                [$mockRatingsData[0][RatingDataMapper::RATING_PRODUCT_ID]],
                [
                    RatingCountAttribute::ATTRIBUTE_CODE => $mockRatingsData[0][RatingDataMapper::REVIEW_COUNT],
                ],
                $mockRatingsData[0][RatingDataMapper::RATING_STORE]
            );

        $this->mockActionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mockProductActions);

        $this->mockIsRatingAttributeAvailable->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(false);
        $this->mockIsRatingCountAttributeAvailable->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(true);

        $updateRatings = $this->instantiateUpdateRatingsAttributes();
        $updateRatings->execute($mockRatingsData);
    }

    public function testUpdateAttributesIsCalledWhenRatingIsAvailableAndReviewCountIsNotAvailable()
    {
        $this->setupPhp5();

        $mockRatingsData = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_COUNT => 3,
                RatingDataMapper::RATING_AVERAGE => 80.0,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10
            ],
        ];

        $this->mockRatingDataMapper->expects($this->once())
            ->method('execute')
            ->with($mockRatingsData)
            ->willReturn($mockRatingsData);

        $this->mockProductActions->expects($this->once())
            ->method('updateAttributes')
            ->with(
                [$mockRatingsData[0][RatingDataMapper::RATING_PRODUCT_ID]],
                [
                    RatingAttribute::ATTRIBUTE_CODE => $mockRatingsData[0][RatingDataMapper::RATING_AVERAGE],
                ],
                $mockRatingsData[0][RatingDataMapper::RATING_STORE]
            );

        $this->mockActionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mockProductActions);

        $this->mockIsRatingAttributeAvailable->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(true);
        $this->mockIsRatingCountAttributeAvailable->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(false);

        $updateRatings = $this->instantiateUpdateRatingsAttributes();
        $updateRatings->execute($mockRatingsData);
    }

    public function testUpdateAttributesIsNotCalledWhenRatingAndReviewCountAreNotAvailable()
    {
        $this->setupPhp5();

        $mockRatingsData = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_COUNT => 3,
                RatingDataMapper::RATING_AVERAGE => 80.0,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10
            ],
        ];

        $this->mockRatingDataMapper->expects($this->once())
            ->method('execute')
            ->with($mockRatingsData)
            ->willReturn($mockRatingsData);

        $this->mockProductActions->expects($this->never())
            ->method('updateAttributes');

        $this->mockActionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mockProductActions);

        $this->mockIsRatingAttributeAvailable->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(false);
        $this->mockIsRatingCountAttributeAvailable->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturn(false);

        $updateRatings = $this->instantiateUpdateRatingsAttributes();
        $updateRatings->execute($mockRatingsData);
    }

    public function testUpdateAttributesIsNotCalledIfDataMappingIsIncomplete()
    {
        $this->setupPhp5();

        $mockRatingsData = [];

        $mockException = new InvalidRatingDataMappingKey(
            __('The rating data mapping key %1 is missing.', RatingDataMapper::RATING_PRODUCT_ID)
        );

        $this->mockRatingDataMapper->expects($this->once())
            ->method('execute')
            ->with($mockRatingsData)
            ->willThrowException($mockException);

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(sprintf('The rating data mapping key %s is missing.', RatingDataMapper::RATING_PRODUCT_ID));

        $this->mockActionFactory->expects($this->never())
            ->method('create');

        $this->mockIsRatingAttributeAvailable
            ->method('execute')
            ->willReturn(true);
        $this->mockIsRatingCountAttributeAvailable
            ->method('execute')
            ->willReturn(true);

        $updateRatings = $this->instantiateUpdateRatingsAttributes();
        $updateRatings->execute($mockRatingsData);
    }

    public function testUpdateAttributesIsNotCalledIfProductIdIsEmpty()
    {
        $this->setupPhp5();

        $mockRatingsData = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 0,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_COUNT => 3,
                RatingDataMapper::RATING_AVERAGE => 80.0,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10
            ],
        ];

        $this->mockRatingDataMapper->expects($this->once())
            ->method('execute')
            ->with($mockRatingsData)
            ->willReturn($mockRatingsData);

        $this->mockActionFactory->expects($this->never())->method('create');

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(sprintf('Rating data missing %s', RatingDataMapper::RATING_PRODUCT_ID));

        $this->mockIsRatingAttributeAvailable
            ->method('execute')
            ->willReturn(true);
        $this->mockIsRatingCountAttributeAvailable
            ->method('execute')
            ->willReturn(true);

        $updateRatings = $this->instantiateUpdateRatingsAttributes();
        $updateRatings->execute($mockRatingsData);
    }

    public function testUpdateAttributesIsNotCalledIfAverageAndSumAreMissing()
    {
        $this->setupPhp5();

        $mockRatingsData = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_COUNT => 3,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10
            ],
        ];

        $this->mockRatingDataMapper->expects($this->once())
            ->method('execute')
            ->with($mockRatingsData)
            ->willReturn($mockRatingsData);

        $this->mockActionFactory->expects($this->never())->method('create');

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with('Rating data missing. Either average or (sum and count) are required');

        $this->mockIsRatingAttributeAvailable
            ->method('execute')
            ->willReturn(true);
        $this->mockIsRatingCountAttributeAvailable
            ->method('execute')
            ->willReturn(true);

        $updateRatings = $this->instantiateUpdateRatingsAttributes();
        $updateRatings->execute($mockRatingsData);
    }

    public function testUpdateAttributesIsNotCalledIfAverageAndCountAreMissing()
    {
        $this->setupPhp5();

        $mockRatingsData = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10
            ],
        ];

        $this->mockRatingDataMapper->expects($this->once())
            ->method('execute')
            ->with($mockRatingsData)
            ->willReturn($mockRatingsData);

        $this->mockActionFactory->expects($this->never())->method('create');

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with('Rating data missing. Either average or (sum and count) are required');

        $this->mockIsRatingAttributeAvailable
            ->method('execute')
            ->willReturn(true);
        $this->mockIsRatingCountAttributeAvailable
            ->method('execute')
            ->willReturn(true);

        $updateRatings = $this->instantiateUpdateRatingsAttributes();
        $updateRatings->execute($mockRatingsData);
    }

    public function testUpdateAttributesIsCalledIfCountIsMissing()
    {
        $this->setupPhp5();

        $mockRatingsData = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_AVERAGE => 80.0,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10
            ],
        ];

        $this->mockRatingDataMapper->expects($this->once())
            ->method('execute')
            ->with($mockRatingsData)
            ->willReturn($mockRatingsData);

        $this->mockProductActions->expects($this->once())->method('updateAttributes');

        $this->mockActionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mockProductActions);

        $this->mockLogger->expects($this->never())->method('error');

        $this->mockIsRatingAttributeAvailable
            ->method('execute')
            ->willReturn(true);
        $this->mockIsRatingCountAttributeAvailable
            ->method('execute')
            ->willReturn(true);

        $updateRatings = $this->instantiateUpdateRatingsAttributes();
        $updateRatings->execute($mockRatingsData);
    }

    public function testUpdateAttributesWhenAverageIsMissingButSumAndCountArePresent()
    {
        $this->setupPhp5();

        $mockRatingsData = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_COUNT => 3,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10
            ],
        ];

        $this->mockRatingDataMapper->expects($this->once())
            ->method('execute')
            ->with($mockRatingsData)
            ->willReturn($mockRatingsData);

        $this->mockProductActions->expects($this->once())
            ->method('updateAttributes')
            ->with(
                [$mockRatingsData[0][RatingDataMapper::RATING_PRODUCT_ID]],
                [
                    RatingAttribute::ATTRIBUTE_CODE => $mockRatingsData[0][RatingDataMapper::RATING_SUM] / $mockRatingsData[0][RatingDataMapper::RATING_COUNT],
                    RatingCountAttribute::ATTRIBUTE_CODE => $mockRatingsData[0][RatingDataMapper::REVIEW_COUNT],
                ],
                $mockRatingsData[0][RatingDataMapper::RATING_STORE]
            );

        $this->mockActionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mockProductActions);

        $this->mockIsRatingAttributeAvailable
            ->method('execute')
            ->willReturn(true);
        $this->mockIsRatingCountAttributeAvailable
            ->method('execute')
            ->willReturn(true);

        $updateRatings = $this->instantiateUpdateRatingsAttributes();
        $updateRatings->execute($mockRatingsData);
    }

    public function testHandlesCountOf0()
    {
        $this->setupPhp5();

        $mockRatingsData = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_COUNT => 0,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10
            ],
        ];

        $this->mockRatingDataMapper->expects($this->once())
            ->method('execute')
            ->with($mockRatingsData)
            ->willReturn($mockRatingsData);

        $this->mockProductActions->expects($this->once())
            ->method('updateAttributes')
            ->with(
                [$mockRatingsData[0][RatingDataMapper::RATING_PRODUCT_ID]],
                [
                    RatingAttribute::ATTRIBUTE_CODE => 0,
                    RatingCountAttribute::ATTRIBUTE_CODE => $mockRatingsData[0][RatingDataMapper::REVIEW_COUNT],
                ],
                $mockRatingsData[0][RatingDataMapper::RATING_STORE]
            );

        $this->mockActionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mockProductActions);

        $this->mockIsRatingAttributeAvailable
            ->method('execute')
            ->willReturn(true);
        $this->mockIsRatingCountAttributeAvailable
            ->method('execute')
            ->willReturn(true);

        $updateRatings = $this->instantiateUpdateRatingsAttributes();
        $updateRatings->execute($mockRatingsData);
    }

    /**
     * @return void
     * @TODO when support for php5.6 is dropped replace with protected function setUp() and remove call from each test
     */
    private function setupPhp5()
    {
        $this->mockActionFactory = $this->getMockBuilder(ActionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockProductActions = $this->getMockBuilder(Action::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRatingDataMapper = $this->getMockBuilder(RatingDataMapperInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockIsRatingAttributeAvailable = $this->getMockBuilder(IsRatingAttributeAvailableInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockIsRatingCountAttributeAvailable = $this->getMockBuilder(IsRatingCountAttributeAvailableInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return UpdateRatingsAttributes
     */
    private function instantiateUpdateRatingsAttributes()
    {
        return new UpdateRatingsAttributes(
            $this->mockActionFactory,
            $this->mockLogger,
            $this->mockRatingDataMapper,
            $this->mockIsRatingAttributeAvailable,
            $this->mockIsRatingCountAttributeAvailable
        );
    }
}
