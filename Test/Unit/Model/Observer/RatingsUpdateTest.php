<?php

namespace Klevu\Search\Test\Unit\Model\Observer;

use Klevu\Search\Exception\Catalog\Product\Review\KlevuProductAttributeMissingException;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Observer\RatingsUpdate;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Klevu\Search\Service\Catalog\Product\Review\UpdateRating;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem;
use Magento\Review\Model\Rating;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RatingsUpdateTest extends TestCase
{
    const PRODUCT_ID_FIXTURE = '1';

    /**
     * @var MagentoProductActionsInterface|MockObject
     */
    private $mockProductSync;
    /**
     * @var Filesystem|MockObject
     */
    private $mockFileSystem;
    /**
     * @var SearchHelper|MockObject
     */
    private $mockSearchHelper;
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $mockStoreManager;
    /**
     * @var Rating|MockObject
     */
    private $mockRating;
    /**
     * @var EntityType|MockObject
     */
    private $mockEntityType;
    /**
     * @var Attribute|MockObject
     */
    private $mockAttribute;
    /**
     * @var ProductAction|MockObject
     */
    private $mockProductAction;
    /**
     * @var UpdateRating|MockObject
     */
    private $mockUpdateRating;
    /**
     * @var ProductInterface|MockObject
     */
    private $mockProduct;
    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $mockProductRepository;

    public function testItImplementsObserverInterface()
    {
        $this->setupPhp5();

        $observer = $this->instantiateRatingsUpdateObserver();
        $this->assertInstanceOf(ObserverInterface::class, $observer);
    }

    public function testUpdateStoreRatingIsCalledIfStatusIdNotApproved()
    {
        $this->setupPhp5();
        $statusId = 2;
        $productId = self::PRODUCT_ID_FIXTURE;

        $this->mockUpdateRating->expects($this->once())->method('execute')->with($this->mockProduct);

        $mockReview = $this->getMockReview();
        $mockReview->method('getStatusId')->willReturn($statusId);
        $mockReview->expects($this->atLeastOnce())->method('getEntityPkValue')->willReturn($productId);

        $mockEvent = $this->getMockEvent();
        $mockEvent->method('getDataUsingMethod')->willReturn($mockReview);

        $mockObserver = $this->getMockObserver();
        $mockObserver->expects($this->once())->method('getEvent')->willReturn($mockEvent);

        $ratingsUpdate = $this->instantiateRatingsUpdateObserver();
        $ratingsUpdate->execute($mockObserver);
    }

    public function testUpdateStoreRatingIsNotCalledIfObjectIsNotReviewInstance()
    {
        $this->setupPhp5();

        $this->mockUpdateRating->expects($this->never())->method('execute');

        $mockDataObject = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockEvent = $this->getMockEvent();
        $mockEvent->expects($this->once())->method('getDataUsingMethod')->wilLReturn($mockDataObject);

        $mockObserver = $this->getMockObserver();
        $mockObserver->expects($this->once())->method('getEvent')->willReturn($mockEvent);

        $ratingsUpdate = $this->instantiateRatingsUpdateObserver();
        $ratingsUpdate->execute($mockObserver);
    }

    public function testUpdateAttributesCalled()
    {
        $this->setupPhp5();
        $statusId = 1;
        $productId = self::PRODUCT_ID_FIXTURE;

        $mockReview = $this->getMockReview();
        $mockReview->method('getStatusId')->willReturn($statusId);
        $mockReview->expects($this->atLeastOnce())->method('getEntityPkValue')->willReturn($productId);

        $this->mockUpdateRating->expects($this->once())->method('execute')->with($this->mockProduct);

        $mockEvent = $this->getMockEvent();
        $mockEvent->method('getDataUsingMethod')->willReturn($mockReview);

        $mockObserver = $this->getMockObserver();
        $mockObserver->expects($this->once())->method('getEvent')->willReturn($mockEvent);

        $ratingsUpdate = $this->instantiateRatingsUpdateObserver();
        $ratingsUpdate->execute($mockObserver);
    }

    public function testErrorIsLoggedWhenExceptionThrownInUpdateRating()
    {
        $this->setupPhp5();
        $statusId = 1;
        $productId = self::PRODUCT_ID_FIXTURE;

        $mockReview = $this->getMockReview();
        $mockReview->method('getStatusId')->willReturn($statusId);
        $mockReview->expects($this->atLeastOnce())->method('getEntityPkValue')->willReturn($productId);

        $exception = new KlevuProductAttributeMissingException(__('Exception Thrown'));
        $this->mockUpdateRating->expects($this->once())->method('execute')->willThrowException($exception);

        $this->mockSearchHelper->expects($this->once())->method('log');

        $this->mockProductSync->expects($this->never())->method('updateSpecificProductIds');

        $mockEvent = $this->getMockEvent();
        $mockEvent->method('getDataUsingMethod')->willReturn($mockReview);

        $mockObserver = $this->getMockObserver();
        $mockObserver->expects($this->once())->method('getEvent')->willReturn($mockEvent);

        $ratingsUpdate = $this->instantiateRatingsUpdateObserver();
        $ratingsUpdate->execute($mockObserver);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->mockProductSync = $this->getMockBuilder(MagentoProductActionsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockFileSystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockSearchHelper = $this->getMockBuilder(SearchHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStoreManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRating = $this->getMockBuilder(Rating::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockEntityType = $this->getMockBuilder(EntityType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockAttribute = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockProductAction = $this->getMockBuilder(ProductAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockUpdateRating = $this->getMockBuilder(UpdateRating::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockProduct = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockProduct->method('getId')->willReturn(self::PRODUCT_ID_FIXTURE);
        $this->mockProductRepository = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockProductRepository->method('getById')
            ->with(self::PRODUCT_ID_FIXTURE)
            ->willReturn($this->mockProduct);
    }

    /**
     * @return RatingsUpdate
     */
    private function instantiateRatingsUpdateObserver()
    {
        return new RatingsUpdate(
            $this->mockProductSync,
            $this->mockFileSystem,
            $this->mockSearchHelper,
            $this->mockStoreManager,
            $this->mockRating,
            $this->mockEntityType,
            $this->mockAttribute,
            $this->mockProductAction,
            $this->mockUpdateRating,
            $this->mockProductRepository
        );
    }

    /**
     * @return Review|MockObject
     */
    private function getMockReview()
    {
        $mockReviewBuilder = $this->getMockBuilder(Review::class);
        if (method_exists($mockReviewBuilder, 'addMethods')) {
            $mockReviewBuilder->addMethods(['getStatusId', 'getEntityPkValue']);
        } else {
            $mockReviewBuilder->setMethods(['getStatusId', 'getData', 'getEntityPkValue']);
        }
        if (method_exists($mockReviewBuilder, 'onlyMethods')) {
            $mockReviewBuilder->onlyMethods(['getData']);
        } else {
            $mockReviewBuilder->setMethods(['getStatusId', 'getData', 'getEntityPkValue']);
        }

        return $mockReviewBuilder->disableOriginalConstructor()->getMock();
    }

    /**
     * @return Event|MockObject
     */
    private function getMockEvent()
    {
        $mockEventBuilder = $this->getMockBuilder(Event::class);

        return $mockEventBuilder->disableOriginalConstructor()->getMock();
    }

    /**
     * @return Observer|MockObject
     */
    private function getMockObserver()
    {
        return $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
    }
}
