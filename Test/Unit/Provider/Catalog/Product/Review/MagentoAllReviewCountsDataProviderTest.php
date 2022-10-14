<?php

namespace Klevu\Search\Test\Unit\Provider\Catalog\Product\Review;

use Klevu\Search\Provider\Catalog\Product\Review\MagentoAllReviewCountsDataProvider;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Reports\Model\ResourceModel\Review\Collection as ReviewCollection;
use Magento\Reports\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MagentoAllReviewCountsDataProviderTest Extends TestCase
{
    /**
     * @var ReviewCollectionFactory|MockObject
     */
    private $mockReviewCollectionFactory;
    /**
     * @var ReviewCollection|MockObject
     */
    private $mockReviewCollection;
    /**
     * @var AdapterInterface|MockObject
     */
    private $mockConnection;
    /**
     * @var Select|MockObject
     */
    private $mockSelect;

    public function testReturnsArray()
    {
        $this->setupPhp5();

        $storeId = '1';

        $this->mockSelect->expects($this->once())
            ->method('reset')
            ->with(Select::COLUMNS);
        $this->mockSelect->expects($this->once())
            ->method('from')
            ->with(
                [],
                [RatingDataMapper::RATING_PRODUCT_ID => 'main_table.entity_pk_value',
                RatingDataMapper::REVIEW_COUNT => new \Zend_Db_Expr('COUNT(*)'),]
            );
        $this->mockSelect->expects($this->once())
            ->method('where')
            ->with('main_table.status_id IN (:status_id)');
        $this->mockSelect->expects($this->once())
            ->method('group')
            ->with('main_table.entity_pk_value');

        $this->mockConnection->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [RatingDataMapper::RATING_PRODUCT_ID => 1, RatingDataMapper::REVIEW_COUNT => 10],
                [RatingDataMapper::RATING_PRODUCT_ID => 2, RatingDataMapper::REVIEW_COUNT => 20],
                [RatingDataMapper::RATING_PRODUCT_ID => 3, RatingDataMapper::REVIEW_COUNT => 30],
            ]);

        $this->mockReviewCollection->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->mockConnection);
        $this->mockReviewCollection->expects($this->once())
            ->method('addStoreFilter')
            ->with($storeId);
        $this->mockReviewCollection->expects($this->once())
            ->method('getSelect')
            ->willReturn($this->mockSelect);

        $this->mockReviewCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mockReviewCollection);

        $dataProvider = $this->instantiateAllReviewCountsDataProvider();
        $reviewCounts = $dataProvider->getData((int)$storeId);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($reviewCounts);
        } else {
            $this->assertTrue(is_array($reviewCounts), 'Is Array');
        }
        $this->assertArrayHasKey(1, $reviewCounts);
        $this->assertSame(10, $reviewCounts[1]);

        $this->assertArrayHasKey(2, $reviewCounts);
        $this->assertSame(20, $reviewCounts[2]);

        $this->assertArrayHasKey(3, $reviewCounts);
        $this->assertSame(30, $reviewCounts[3]);
    }

    /**
     * @return void
     */
    private function setupPhp5()
    {
        $this->mockReviewCollectionFactory = $this->getMockBuilder(ReviewCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockReviewCollection = $this->getMockBuilder(ReviewCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockConnection = $this->getMockBuilder(AdapterInterface::class)->getMock();
        $this->mockSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MagentoAllReviewCountsDataProvider
     */
    private function instantiateAllReviewCountsDataProvider()
    {
        return new MagentoAllReviewCountsDataProvider(
            $this->mockReviewCollectionFactory
        );
    }
}
