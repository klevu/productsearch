<?php

namespace Klevu\Search\Test\Unit\Service\Catalog\Product\Review;

use Klevu\Logger\Api\StoreScopeResolverInterface;
use Klevu\Search\Exception\Catalog\Product\Review\KlevuProductAttributeMissingException;
use Klevu\Search\Provider\Catalog\Product\Review\MagentoRatingDataProvider;
use Klevu\Search\Service\Catalog\Product\IsRatingAttributeAvailable;
use Klevu\Search\Service\Catalog\Product\IsRatingCountAttributeAvailable;
use Klevu\Search\Service\Catalog\Product\Review\UpdateRating;
use Klevu\Search\Service\Catalog\Product\UpdateRatingsAttributes;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\Review;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdateRatingTest extends TestCase
{
    /**
     * @var MockObject&LoggerInterface
     */
    private $mockLogger;
    /**
     * @var MockObject&StoreScopeResolverInterface
     */
    private $mockStoreScopeResolver;
    /**
     * @var IsRatingAttributeAvailable|MockObject
     */
    private $mockIsRatingAttrAvailable;
    /**
     * @var IsRatingCountAttributeAvailable|MockObject
     */
    private $mockIsRatingCountAttrAvailable;
    /**
     * @var StoreManagerInterface|StoreManager|MockObject
     */
    private $mockStoreManager;
    /**
     * @var UpdateRatingsAttributes|MockObject
     */
    private $mockUpdateRatings;
    /**
     * @var MagentoRatingDataProvider|MockObject
     */
    private $mockDataProvider;

    public function testExecute_RatingNotAvailable_RatingCountNotAvailable()
    {
        $this->setupPhp5();

        $this->mockIsRatingAttrAvailable->method('execute')->willReturn(false);
        $this->mockIsRatingCountAttrAvailable->method('execute')->willReturn(false);

        $this->mockStoreManager->method('isSingleStoreMode')
            ->willReturn(false);

        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStore');
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreByCode');
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreById');

        $productFixture = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectException(KlevuProductAttributeMissingException::class);
        $this->expectExceptionMessage('Klevu product attributes for rating and review count do not exist');

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('debug');

        $updateRating = $this->instantiateUpdateRating();
        $updateRating->execute($productFixture);
    }

    public function testExecute_RatingNotAvailable_RatingCountAvailable()
    {
        $this->setupPhp5();

        $this->mockIsRatingAttrAvailable->method('execute')->willReturn(false);
        $this->mockIsRatingCountAttrAvailable->method('execute')->willReturn(true);

        $this->mockStoreManager->method('isSingleStoreMode')
            ->willReturn(false);

        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStore');
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreByCode');
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreById');

        $productFixture = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with('Klevu product attribute for rating does not exist');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('debug');

        $updateRating = $this->instantiateUpdateRating();
        $updateRating->execute($productFixture);
    }

    public function testExecute_RatingAvailable_RatingCountNotAvailable()
    {
        $this->setupPhp5();

        $this->mockIsRatingAttrAvailable->method('execute')->willReturn(true);
        $this->mockIsRatingCountAttrAvailable->method('execute')->willReturn(false);

        $this->mockStoreManager->method('isSingleStoreMode')
            ->willReturn(false);

        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStore');
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreByCode');
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreById');

        $productFixture = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with('Klevu product attribute for review count does not exist');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('debug');

        $updateRating = $this->instantiateUpdateRating();
        $updateRating->execute($productFixture);
    }

    public function testExecute_SingleStoreModeDisabled_NoStores_ProductInterfaceNoStores()
    {
        $this->setupPhp5();

        $this->mockIsRatingAttrAvailable->method('execute')->willReturn(true);
        $this->mockIsRatingCountAttrAvailable->method('execute')->willReturn(true);

        $this->mockStoreManager->method('isSingleStoreMode')
            ->willReturn(false);
        $this->mockStoreManager->expects($this->atLeastOnce())
            ->method('getStores')
            ->with(false)
            ->willReturn([]);

        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStore');
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreByCode');
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreById');

        $this->mockDataProvider->expects($this->never())
            ->method('getData');

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('debug');

        $productFixture = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $updateRating = $this->instantiateUpdateRating();
        $updateRating->execute($productFixture);
    }

    public function testExecute_SingleStoreModeDisabled_WithStores_ProductInterfaceNoStores()
    {
        $this->setupPhp5();

        $this->mockIsRatingAttrAvailable->method('execute')->willReturn(true);
        $this->mockIsRatingCountAttrAvailable->method('execute')->willReturn(true);

        $adminStoreMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adminStoreMock->method('getId')->willReturn(0);
        $adminStoreMock->method('getCode')->willReturn('admin');

        $storeMock3 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock3->method('getId')
            ->willReturn('3');
        $storeMock5 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock5->method('getId')
            ->willReturn('5');
        $storeMock8 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock8->method('getId')
            ->willReturn('8');

        $this->mockStoreManager->method('isSingleStoreMode')
            ->willReturn(false);
        $this->mockStoreManager->method('getStores')
            ->with(false)
            ->willReturn([
                $storeMock3,
                $storeMock5,
                $storeMock8,
            ]);
        $this->mockStoreManager->method('getStore')
            ->willReturnCallback(static function ($storeId) use ($storeMock3, $storeMock5, $storeMock8) {
                if (isset(${'storeMock' . $storeId})) {
                    return ${'storeMock' . $storeId};
                }

                throw NoSuchEntityException::singleField('entity_id', $storeId);
            });

        $this->mockStoreScopeResolver->method('getCurrentStore')
            ->willReturn($adminStoreMock);
        $this->mockStoreScopeResolver->expects($this->once())
            ->method('setCurrentStore')
            ->with($adminStoreMock);
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreByCode');
        $this->mockStoreScopeResolver->expects($this->exactly(3))
            ->method('setCurrentStoreById')
            ->withConsecutive(
                [3],
                [5],
                [8]
            );

        $ratingDataFixtures = [
            3 => [
                'sum' => 240,
                'count' => 3,
                'average' => 80.0,
                'store' => 3,
                'review_count' => 10,
            ],
            5 => [
                'sum' => 200,
                'count' => 4,
                'average' => 50.0,
                'store' => 5,
                'review_count' => 10,
            ],
            8 => [
                'sum' => 600,
                'count' => 10,
                'average' => 60.0,
                'store' => 8,
                'review_count' => 10,
            ],
        ];
        $this->mockDataProvider->expects($this->exactly(3))
            ->method('getData')
            ->withConsecutive(
                [42, 3],
                [42, 5],
                [42, 8]
            )->willReturnCallback(static function ($productId, $storeId) use ($ratingDataFixtures) {
                return isset($ratingDataFixtures[$storeId])
                    ? $ratingDataFixtures[$storeId]
                    : null;
            });

        $this->mockUpdateRatings->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                [
                    [$ratingDataFixtures[3]],
                ],
                [
                    [$ratingDataFixtures[5]],
                ],
                [
                    [$ratingDataFixtures[8]],
                ]
            );

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('debug');

        $productFixture = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productFixture->method('getId')
            ->willReturn(42);

        $updateRating = $this->instantiateUpdateRating();
        $updateRating->execute($productFixture);
    }

    public function testExecute_SingleStoreModeDisabled_ProductModelNoStores()
    {
        $this->setupPhp5();

        $this->mockIsRatingAttrAvailable->method('execute')->willReturn(true);
        $this->mockIsRatingCountAttrAvailable->method('execute')->willReturn(true);

        $this->mockStoreManager->method('isSingleStoreMode')
            ->willReturn(false);
        $this->mockStoreManager->expects($this->never())
            ->method('getStores');

        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStore');
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreByCode');
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreById');

        $this->mockDataProvider->expects($this->never())
            ->method('getData');

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('debug');

        $productFixture = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productFixture->expects($this->atLeastOnce())
            ->method('getStoreIds')
            ->willReturn([]);

        $updateRating = $this->instantiateUpdateRating();
        $updateRating->execute($productFixture);
    }

    public function testExecute_SingleStoreModeDisabled_ProductModelWithStores()
    {
        $this->setupPhp5();

        $this->mockIsRatingAttrAvailable->method('execute')->willReturn(true);
        $this->mockIsRatingCountAttrAvailable->method('execute')->willReturn(true);

        $adminStoreMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adminStoreMock->method('getId')->willReturn(0);
        $adminStoreMock->method('getCode')->willReturn('admin');

        $storeMock3 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock3->method('getId')
            ->willReturn('3');
        $storeMock5 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock5->method('getId')
            ->willReturn('5');
        $storeMock8 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock8->method('getId')
            ->willReturn('8');

        $this->mockStoreManager->method('isSingleStoreMode')
            ->willReturn(false);
        $this->mockStoreManager->method('getStores')
            ->willReturn([
                $storeMock3,
                $storeMock5,
                $storeMock8,
            ]);
        $this->mockStoreManager->method('getStore')
            ->willReturnCallback(static function ($storeId) use ($storeMock3, $storeMock5, $storeMock8) {
                if (isset(${'storeMock' . $storeId})) {
                    return ${'storeMock' . $storeId};
                }

                throw NoSuchEntityException::singleField('entity_id', $storeId);
            });

        $this->mockStoreScopeResolver->method('getCurrentStore')
            ->willReturn($adminStoreMock);
        $this->mockStoreScopeResolver->expects($this->once())
            ->method('setCurrentStore')
            ->with($adminStoreMock);
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreByCode');
        $this->mockStoreScopeResolver->expects($this->once())
            ->method('setCurrentStoreById')
            ->withConsecutive(
                [8]
            );

        $ratingDataFixtures = [
            3 => [
                'sum' => 240,
                'count' => 3,
                'average' => 80.0,
                'store' => 3,
                'review_count' => 10,
            ],
            5 => [
                'sum' => 200,
                'count' => 4,
                'average' => 50.0,
                'store' => 5,
                'review_count' => 10,
            ],
            8 => [
                'sum' => 600,
                'count' => 10,
                'average' => 60.0,
                'store' => 8,
                'review_count' => 10,
            ],
        ];
        $this->mockDataProvider->expects($this->once())
            ->method('getData')
            ->withConsecutive(
                [42, 8]
            )->willReturnCallback(static function ($productId, $storeId) use ($ratingDataFixtures) {
                return isset($ratingDataFixtures[$storeId])
                    ? $ratingDataFixtures[$storeId]
                    : null;
            });

        $this->mockUpdateRatings->expects($this->once())
            ->method('execute')
            ->withConsecutive(
                [
                    [$ratingDataFixtures[8]],
                ]
            );

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('debug');

        $productFixture = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productFixture->method('getId')
            ->willReturn(42);
        $productFixture->expects($this->atLeastOnce())
            ->method('getStoreIds')
            ->willReturn([8]);

        $updateRating = $this->instantiateUpdateRating();
        $updateRating->execute($productFixture);
    }

    public function testExecute_SingleStoreModeEnabled_ProductInterfaceNoStores()
    {
        $this->setupPhp5();

        $this->mockIsRatingAttrAvailable->method('execute')->willReturn(true);
        $this->mockIsRatingCountAttrAvailable->method('execute')->willReturn(true);

        $this->mockStoreManager->method('isSingleStoreMode')
            ->willReturn(true);

        $adminStoreMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $adminStoreMock->method('getId')->willReturn(0);
        $adminStoreMock->method('getCode')->willReturn('admin');

        $storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->method('getId')
            ->willReturn('1');

        $this->mockStoreManager->expects($this->atLeastOnce())
            ->method('getStores')
            ->with(true)
            ->willReturn([
                $adminStoreMock,
                $storeMock,
            ]);

        $this->mockStoreScopeResolver->method('getCurrentStore')
            ->willReturn($adminStoreMock);
        $this->mockStoreScopeResolver->expects($this->once())
            ->method('setCurrentStore')
            ->with($adminStoreMock);
        $this->mockStoreScopeResolver->method('getCurrentStore')
            ->willReturn($adminStoreMock);
        $this->mockStoreScopeResolver->expects($this->once())
            ->method('setCurrentStore')
            ->with($adminStoreMock);
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreByCode');
        $this->mockStoreScopeResolver->expects($this->exactly(2))
            ->method('setCurrentStoreById')
            ->withConsecutive(
                [0],
                [1]
            );

        $ratingDataFixtures = [
            0 => [
                'sum' => 240,
                'count' => 3,
                'average' => 80.0,
                'store' => 3,
                'review_count' => 10,
            ],
            1 => [
                'sum' => 200,
                'count' => 4,
                'average' => 50.0,
                'store' => 5,
                'review_count' => 10,
            ],
        ];

        $this->mockDataProvider->expects($this->exactly(2))
            ->method('getData')
            ->withConsecutive(
                [1, 0],
                [1, 1]
            )
            ->willReturnCallback(static function ($productId, $storeId) use ($ratingDataFixtures) {
                return isset($ratingDataFixtures[$storeId])
                    ? $ratingDataFixtures[$storeId]
                    : null;
            });

        $this->mockUpdateRatings->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [
                    [$ratingDataFixtures[0]],
                ],
                [
                    [$ratingDataFixtures[1]],
                ]
            );

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('debug');

        $productFixture = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productFixture->method('getId')
            ->willReturn('1');

        $updateRating = $this->instantiateUpdateRating();
        $updateRating->execute($productFixture);
    }

    public function testExecute_SingleStoreModeEnabled_ProductModelNoStores()
    {
        $this->setupPhp5();

        $this->mockIsRatingAttrAvailable->method('execute')->willReturn(true);
        $this->mockIsRatingCountAttrAvailable->method('execute')->willReturn(true);

        $this->mockStoreManager->method('isSingleStoreMode')
            ->willReturn(true);
        $this->mockStoreManager->expects($this->never())
            ->method('getStores');

        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStore');
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreByCode');
        $this->mockStoreScopeResolver->expects($this->never())
            ->method('setCurrentStoreById');

        $this->mockDataProvider->expects($this->never())
            ->method('getData');

        $this->mockLogger->expects($this->never())->method('emergency');
        $this->mockLogger->expects($this->never())->method('critical');
        $this->mockLogger->expects($this->never())->method('alert');
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockLogger->expects($this->never())->method('warning');
        $this->mockLogger->expects($this->never())->method('notice');
        $this->mockLogger->expects($this->never())->method('info');
        $this->mockLogger->expects($this->never())->method('debug');

        $productFixture = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productFixture->expects($this->atLeastOnce())
            ->method('getStoreIds')
            ->willReturn([]);

        $updateRating = $this->instantiateUpdateRating();
        $updateRating->execute($productFixture);
    }

    private function setupPhp5()
    {
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStoreScopeResolver = $this->getMockBuilder(StoreScopeResolverInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockIsRatingAttrAvailable = $this->getMockBuilder(IsRatingAttributeAvailable::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockIsRatingCountAttrAvailable = $this->getMockBuilder(IsRatingCountAttributeAvailable::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStoreManager = $this->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockUpdateRatings = $this->getMockBuilder(UpdateRatingsAttributes::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockDataProvider = $this->getMockBuilder(MagentoRatingDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return UpdateRating
     */
    private function instantiateUpdateRating()
    {
        return new UpdateRating(
            $this->mockLogger,
            $this->mockStoreScopeResolver,
            $this->mockIsRatingAttrAvailable,
            $this->mockIsRatingCountAttrAvailable,
            $this->mockStoreManager,
            $this->mockDataProvider,
            $this->mockUpdateRatings,
            []
        );
    }
}
