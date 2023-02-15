<?php

namespace Klevu\Search\Test\Integration\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\Review\RatingDataMapperInterface;
use Klevu\Search\Api\Service\Catalog\Product\UpdateRatingsAttributesInterface;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingDataMappingKey;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use Klevu\Search\Service\Catalog\Product\UpdateRatingsAttributes;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\Product\ActionFactory;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdateRatingsAttributesTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var RatingDataMapperInterface|MockObject
     */
    private $mockDataMapper;
    /**
     * @var MockObject|LoggerInterface
     */
    private $mockLogger;

    public function testUpdateRatingsAttributesImplementsUpdateRatingsAttributesInterface()
    {
        $this->setUpPhp5();

        $updateRatingsAttributesService = $this->instantiateUpdateRatingsAttributesService();

        $this->assertInstanceOf(UpdateRatingsAttributesInterface::class, $updateRatingsAttributesService);
    }

    public function testErrorIsLoggedIfRatingMappingFails()
    {
        $this->setUpPhp5();

        $exception = $this->objectManager->create(InvalidRatingDataMappingKey::class, [
            'phrase' => __('The rating data mapping key %1 is missing.', RatingDataMapper::RATING_PRODUCT_ID)
        ]);

        $this->mockDataMapper->expects($this->once())->method('execute')->willThrowException($exception);

        $this->mockLogger->expects($this->once())->method('error');

        $updateRatingsAttributesService = $this->objectManager->create(UpdateRatingsAttributes::class, [
            'ratingDataMapper' => $this->mockDataMapper,
            'logger' => $this->mockLogger
        ]);
        $updateRatingsAttributesService->execute([]);
    }

    /**
     * @dataProvider missingMappingFieldsDataProvider
     */
    public function testRatingUpdateIsSkippedIfDataIsMissing(array $missingFields)
    {
        $this->setUpPhp5();
        $ratings = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_COUNT => 3,
                RatingDataMapper::RATING_AVERAGE => 80.0,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10,
            ]
        ];
        $ratings[0] = array_filter($ratings[0], function ($index) use ($missingFields) {
            return !in_array($index, $missingFields, true);
        }, ARRAY_FILTER_USE_KEY);

        if (count($missingFields) === 1) {
            $this->mockLogger->expects($this->once())
                ->method('error')
                ->with(
                    __(
                        'Rating data missing %1',
                        $missingFields[0]
                    )
                );
        } else {
            $this->mockLogger->expects($this->once())
                ->method('error')
                ->with(
                    __(
                        'Rating data missing. Either %1 or (%2 and %3) are required',
                        RatingDataMapper::RATING_AVERAGE,
                        RatingDataMapper::RATING_SUM,
                        RatingDataMapper::RATING_COUNT
                    )
                );
        }

        $updateRatingsAttributesService = $this->objectManager->create(UpdateRatingsAttributes::class, [
            'logger' => $this->mockLogger
        ]);
        $updateRatingsAttributesService->execute($ratings);
    }

    public function testErrorIsLoggedIfUpdateRatingThrowsException()
    {
        $this->setUpPhp5();
        $ratings = [
            [
                RatingDataMapper::RATING_PRODUCT_ID => 1,
                RatingDataMapper::RATING_SUM => 240,
                RatingDataMapper::RATING_COUNT => 3,
                RatingDataMapper::RATING_AVERAGE => 80.0,
                RatingDataMapper::RATING_STORE => 1,
                RatingDataMapper::REVIEW_COUNT => 10,
            ]
        ];

        $exception = $this->objectManager->create(\Exception::class, [
            'phrase' => __('Nope.')
        ]);

        $mockAction = $this->getMockBuilder(ProductAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAction->expects($this->once())->method('updateAttributes')->willThrowException($exception);

        $mockActionFactory = $this->getMockBuilder(ActionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockActionFactory->expects($this->once())->method('create')->willReturn($mockAction);

        $this->mockLogger->expects($this->once())->method('error');

        $updateRatingsAttributesService = $this->objectManager->create(UpdateRatingsAttributes::class, [
            'actionFactory' => $mockActionFactory,
            'logger' => $this->mockLogger
        ]);
        $updateRatingsAttributesService->execute($ratings);
    }

    /**
     * @return array[]
     */
    public function missingMappingFieldsDataProvider()
    {
        return [
            [[RatingDataMapper::RATING_PRODUCT_ID]],
            [[RatingDataMapper::RATING_SUM, RatingDataMapper::RATING_AVERAGE]],
            [[RatingDataMapper::RATING_COUNT, RatingDataMapper::RATING_AVERAGE]],
            [[RatingDataMapper::RATING_STORE]],
            [[RatingDataMapper::REVIEW_COUNT]]
        ];
    }

    /**
     * @return UpdateRatingsAttributes
     */
    private function instantiateUpdateRatingsAttributesService()
    {
        return $this->objectManager->create(UpdateRatingsAttributes::class);
    }

    /**
     * @return void
     * @TODO when support for php5.6 is dropped replace with protected function setUp() and remove call from each test
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->mockDataMapper = $this->getMockBuilder(RatingDataMapperInterface::class)->getMock();
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
    }
}
