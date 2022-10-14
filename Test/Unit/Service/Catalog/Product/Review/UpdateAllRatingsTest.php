<?php

namespace Klevu\Search\Test\Unit\Service\Catalog\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Catalog\Product\IsRatingAttributeAvailableInterface;
use Klevu\Search\Api\Service\Catalog\Product\IsRatingCountAttributeAvailableInterface;
use Klevu\Search\Api\Service\Catalog\Product\UpdateRatingsAttributesInterface;
use Klevu\Search\Exception\Catalog\Product\Review\KlevuProductAttributeMissingException;
use Klevu\Search\Provider\Catalog\Product\Review\MagentoAllRatingsDataProvider;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use Klevu\Search\Service\Catalog\Product\Review\UpdateAllRatings;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateAllRatingsTest extends TestCase
{
    /**
     * @var IsRatingAttributeAvailableInterface|MockObject
     */
    private $mockIsRatingAvailable;
    /**
     * @var IsRatingCountAttributeAvailableInterface|MockObject
     */
    private $mockIsReviewCountAvailable;
    /**
     * @var MagentoAllRatingsDataProvider|MockObject
     */
    private $mockDataProvider;
    /**
     * @var UpdateRatingsAttributesInterface|MockObject
     */
    private $mockUpdateAttributes;

    public function testThrowsExceptionIfRatingAttributeDoesNotExist()
    {
        $this->setUpPhp5();

        $store = 1;
        $this->expectException(KlevuProductAttributeMissingException::class);

        $this->mockIsRatingAvailable->expects($this->once())->method('execute')->willReturn(false);
        $this->mockIsReviewCountAvailable->expects($this->never())->method('execute');

        $updateAllRatings = $this->instantiateUpdateAllRatings();
        $updateAllRatings->execute($store);
    }

    public function testThrowsExceptionIfReviewCountAttributeDoesNotExist()
    {
        $this->setUpPhp5();

        $store = 1;
        $this->expectException(KlevuProductAttributeMissingException::class);

        $this->mockIsRatingAvailable->expects($this->once())->method('execute')->willReturn(true);
        $this->mockIsReviewCountAvailable->expects($this->once())->method('execute')->willReturn(false);

        $updateAllRatings = $this->instantiateUpdateAllRatings();
        $updateAllRatings->execute($store);
    }

    public function testDoesNotCallUpdateIfNoRatingsAreProvided()
    {
        $this->setUpPhp5();

        $store = 1;

        $this->mockIsRatingAvailable->expects($this->once())->method('execute')->willReturn(true);
        $this->mockIsReviewCountAvailable->expects($this->once())->method('execute')->willReturn(true);

        $this->mockDataProvider->expects($this->once())->method('getData')->willReturn([]);

        $this->mockUpdateAttributes->expects($this->never())->method('execute');

        $updateAllRatings = $this->instantiateUpdateAllRatings();
        $updateAllRatings->execute($store);
    }

    public function testCallsUpdateIfRatingsAreProvided()
    {
        $this->setUpPhp5();

        $store = 1;
        $ratingData = [
            RatingDataMapper::RATING_AVERAGE => 80.0,
            RatingDataMapper::RATING_COUNT => 3,
            RatingDataMapper::RATING_PRODUCT_ID => 1,
            RatingDataMapper::RATING_STORE => $store,
            RatingDataMapper::RATING_SUM => 240,
            RatingDataMapper::REVIEW_COUNT => 1
        ];

        $this->mockIsRatingAvailable->expects($this->once())->method('execute')->willReturn(true);
        $this->mockIsReviewCountAvailable->expects($this->once())->method('execute')->willReturn(true);

        $this->mockDataProvider->expects($this->once())->method('getData')->willReturn($ratingData);

        $this->mockUpdateAttributes->expects($this->once())->method('execute')->with($ratingData);

        $updateAllRatings = $this->instantiateUpdateAllRatings();
        $updateAllRatings->execute($store);
    }

    /**
     * @dataProvider invalidStoreDataProvider
     */
    public function testThrowsExceptionIfStoreParamInvalid($store)
    {
        $this->setUpPhp5();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Store param must be an integer');

        $updateAllRatings = $this->instantiateUpdateAllRatings();
        $updateAllRatings->execute($store);
    }

    /**
     * @dataProvider invalidStoreDataProvider_Negative
     */
    public function testThrowsExceptionIfStoreParamInvalid_Negative($store)
    {
        $this->setUpPhp5();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Store param must be a non-negative integer');

        $updateAllRatings = $this->instantiateUpdateAllRatings();
        $updateAllRatings->execute($store);
    }

    /**
     * @return array
     */
    public function invalidStoreDataProvider()
    {
        return [
            ['string'],
            [true],
            [false],
            [null],
            [[1, 2, 3]],
            [json_decode(json_encode([1, 2, 3]))],
            [-1.23],
            [$this->getMockBuilder(StoreInterface::class)->getMock()]
        ];
    }

    /**
     * @return array
     */
    public function invalidStoreDataProvider_Negative()
    {
        return [
            [-1],
        ];
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->mockIsRatingAvailable = $this->getMockBuilder(IsRatingAttributeAvailableInterface::class)
            ->getMock();
        $this->mockIsReviewCountAvailable = $this->getMockBuilder(IsRatingCountAttributeAvailableInterface::class)
            ->getMock();
        $this->mockDataProvider = $this->getMockBuilder(MagentoAllRatingsDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockUpdateAttributes = $this->getMockBuilder(UpdateRatingsAttributesInterface::class)
            ->getMock();
    }

    /**
     * @return UpdateAllRatings
     */
    private function instantiateUpdateAllRatings()
    {
        return new UpdateAllRatings(
            $this->mockIsRatingAvailable,
            $this->mockIsReviewCountAvailable,
            $this->mockDataProvider,
            $this->mockUpdateAttributes
        );
    }
}
