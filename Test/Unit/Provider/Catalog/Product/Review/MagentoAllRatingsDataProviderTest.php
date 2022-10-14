<?php

namespace Klevu\Search\Test\Unit\Provider\Catalog\Product\Review;

use Klevu\Search\Api\Provider\Catalog\Product\Review\AllReviewCountsDataProviderInterface;
use Klevu\Search\Api\Provider\Catalog\Product\Review\ProductsWithRatingAttributeDataProviderInterface;
use Klevu\Search\Provider\Catalog\Product\Review\MagentoAllRatingsDataProvider;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Review\Model\ResourceModel\Rating\Collection as RatingCollection;
use Magento\Review\Model\ResourceModel\Rating\CollectionFactory as RatingCollectionFactory;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MagentoAllRatingsDataProviderTest extends TestCase
{
    /**
     * @var Store|MockObject
     */
    private $mockStore;
    /**
     * @var RatingCollectionFactory|MockObject
     */
    private $mockRatingCollectionFactory;
    /**
     * @var RatingCollection|MockObject
     */
    private $mockRatingCollection;
    /**
     * @var AdapterInterface|MockObject
     */
    private $mockConnection;
    /**
     * @var AllReviewCountsDataProviderInterface|MockObject
     */
    private $mockAllReviewCountsDataProvider;
    /**
     * @var ProductsWithRatingAttributeDataProviderInterface|MockObject
     */
    private $mockProductWithRatingAttributeDataProviderInterface;
    /**
     * @var Select|MockObject
     */
    private $mockSelect;

    public function testReturnsArray()
    {
        $this->setupPhp5();

        $storeId = 1;
        $this->mockStore->expects($this->once())->method('getId')->willReturn($storeId);

        $this->mockRatingCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mockRatingCollection);

        $this->mockConnection->method('fetchAll')->willReturn([
            [RatingDataMapper::RATING_PRODUCT_ID => '1', RatingDataMapper::RATING_SUM => 240, RatingDataMapper::RATING_COUNT => 3, RatingDataMapper::RATING_STORE => $storeId],
            [RatingDataMapper::RATING_PRODUCT_ID => '2', RatingDataMapper::RATING_SUM => 200, RatingDataMapper::RATING_COUNT => 3, RatingDataMapper::RATING_STORE => $storeId]
        ]);

        $this->mockRatingCollection->method('getSelect')->willReturn($this->mockSelect);
        $this->mockRatingCollection->expects($this->once())->method('setStoreFilter')->with($storeId);
        $this->mockRatingCollection->expects($this->once())->method('setActiveFilter')->with(true);
        $this->mockRatingCollection->expects($this->once())->method('getConnection')->willReturn($this->mockConnection);

        $this->mockAllReviewCountsDataProvider->expects($this->once())
            ->method('getData')
            ->willReturn([
                1 => 1,
                2 => 1
            ]);

        $this->mockProductWithRatingAttributeDataProviderInterface->expects($this->once())
            ->method('getProductIdsForStore')
            ->with($storeId)
            ->willReturn([99999]);

        $allRatingsProvider = $this->instantiateAllRatingsDataProvider();
        $ratingsData = $allRatingsProvider->getData($this->mockStore);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($ratingsData);
        } else {
            $this->assertTrue(is_array($ratingsData), 'Is Array');
        }
        $this->assertCount(3, $ratingsData);

        foreach ($ratingsData as $k => $ratingsDatum) {
            if (method_exists($this, 'assertIsArray')) {
                $this->assertIsArray($ratingsDatum, 'Ratings Datum #' . $k . ': Is Array');
            } else {
                $this->assertTrue(is_array($ratingsDatum), 'Ratings Datum #' . $k . ': Is Array');
            }

            $this->assertArrayHasKey(RatingDataMapper::RATING_PRODUCT_ID, $ratingsDatum);
            $this->assertArrayHasKey(RatingDataMapper::RATING_STORE, $ratingsDatum);
            $this->assertArrayHasKey(RatingDataMapper::RATING_COUNT, $ratingsDatum);
            $this->assertArrayHasKey(RatingDataMapper::RATING_SUM, $ratingsDatum);
            $this->assertArrayHasKey(RatingDataMapper::REVIEW_COUNT, $ratingsDatum);

            $this->assertEquals(1,  $ratingsDatum[RatingDataMapper::RATING_STORE], 'Ratings Datum #' . $k . ': Store');

            switch ($ratingsDatum[RatingDataMapper::RATING_PRODUCT_ID]) {
                case 1:
                    $this->assertEquals(240,  $ratingsDatum[RatingDataMapper::RATING_SUM], 'Ratings Datum #' . $k . ': Sum');
                    $this->assertEquals(3,  $ratingsDatum[RatingDataMapper::RATING_COUNT], 'Ratings Datum #' . $k . ': Sum');
                    $this->assertEquals(1,  $ratingsDatum[RatingDataMapper::REVIEW_COUNT], 'Ratings Datum #' . $k . ': Sum');
                    break;

                case 2:
                    $this->assertEquals(200,  $ratingsDatum[RatingDataMapper::RATING_SUM], 'Ratings Datum #' . $k . ': Sum');
                    $this->assertEquals(3,  $ratingsDatum[RatingDataMapper::RATING_COUNT], 'Ratings Datum #' . $k . ': Sum');
                    $this->assertEquals(1,  $ratingsDatum[RatingDataMapper::REVIEW_COUNT], 'Ratings Datum #' . $k . ': Sum');
                    break;

                case 99999:
                    $this->assertNull($ratingsDatum[RatingDataMapper::RATING_SUM], 'Ratings Datum #' . $k . ': Sum');
                    $this->assertEquals(0,  $ratingsDatum[RatingDataMapper::RATING_COUNT], 'Ratings Datum #' . $k . ': Sum');
                    $this->assertEquals(0,  $ratingsDatum[RatingDataMapper::REVIEW_COUNT], 'Ratings Datum #' . $k . ': Sum');
                    break;

                default:
                    $this->fail('Ratings Datum #' . $k . ': Unexpected product id');
                    break;
            }
        }
    }

    /**
     * @return MagentoAllRatingsDataProvider
     */
    private function instantiateAllRatingsDataProvider()
    {
        return new MagentoAllRatingsDataProvider(
            $this->mockRatingCollectionFactory,
            $this->mockAllReviewCountsDataProvider,
            $this->mockProductWithRatingAttributeDataProviderInterface
        );
    }

    /**
     * @return void
     */
    private function setupPhp5()
    {
        $this->mockStore = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRatingCollectionFactory = $this->getMockBuilder(RatingCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRatingCollection = $this->getMockBuilder(RatingCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockConnection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockAllReviewCountsDataProvider = $this->getMockBuilder(AllReviewCountsDataProviderInterface::class)
            ->getMock();
        $this->mockProductWithRatingAttributeDataProviderInterface = $this->getMockBuilder(ProductsWithRatingAttributeDataProviderInterface::class)
            ->getMock();
        $this->mockSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
