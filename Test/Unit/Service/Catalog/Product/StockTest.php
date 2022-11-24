<?php

namespace Klevu\Search\Test\Unit\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\GetStockStatusByIdInterface;
use Klevu\Search\Api\Service\Catalog\Product\Stock\GetCompositeProductStockStatusInterface;
use Klevu\Search\Service\Catalog\Product\Stock as StockService;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemCollectionInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Helper\Stock as MagentoStockHelper;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StockTest extends TestCase
{
    /**
     * @var GetStockStatusByIdInterface|MockObject
     */
    private $mockGetStockStatusById;
    /**
     * @var MagentoStockHelper&MockObject
     */
    private $mockMagentoStockHelper;
    /**
     * @var GetCompositeProductStockStatusInterface&MockObject
     */
    private $mockGetCompositeProductStockStatus;
    private $mockStockRegistryProvider;

    /**
     * @dataProvider InStockProductIdsDataProvider
     */
    public function testGetKlevuStockStatusReturnsYesStringWhenParentAndChildInStock($parentId, $productId)
    {
        $this->setupPhp5();

        $stockService = $this->getStockService();
        $mockParentProduct = $parentId ? $this->getMockProduct($parentId) : null;
        if ($mockParentProduct) {
            $mockParentProduct->method('isSaleable')->willReturn(true);
        }
        $mockProduct = $this->getMockProduct($productId);
        $mockProduct->method('isSaleable')->willReturn(true);

        $result = $stockService->getKlevuStockStatus($mockProduct, $mockParentProduct);

        $this->assertSame(StockService::KLEVU_IN_STOCK, $result);
    }

    /**
     * @dataProvider OutOfStockProductIdsDataProvider
     */
    public function testGetKlevuStockStatusReturnsNoStringWhenParentInStockAndChildOutOfStock($parentId, $productId)
    {
        $this->setupPhp5();

        $stockService = $this->getStockService();
        $mockParentProduct = $parentId ? $this->getMockProduct($parentId) : null;
        $mockProduct = $this->getMockProduct($productId);

        $result = $stockService->getKlevuStockStatus($mockParentProduct, $mockProduct);

        $this->assertSame(StockService::KLEVU_OUT_OF_STOCK, $result);
    }

    /**
     * @dataProvider InStockProductIdsDataProvider
     */
    public function testIsInStockReturnsTrueWhenParentAndChildInStock($parentId, $productId)
    {
        $this->setupPhp5();

        $stockService = $this->getStockService();
        $mockParentProduct = $parentId ? $this->getMockProduct($parentId) : null;
        if ($mockParentProduct) {
            $mockParentProduct->method('isSaleable')->willReturn(true);
        }
        $mockProduct = $this->getMockProduct($productId);
        $mockProduct->method('isSaleable')->willReturn(true);

        $result = $stockService->isInStock($mockProduct, $mockParentProduct);

        $this->assertTrue($result);
    }

    /**
     * @dataProvider OutOfStockProductIdsDataProvider
     */
    public function testIsInStockReturnsFalseWhenParentInStockAndChildOutOfStock($parentId, $productId)
    {
        $this->setupPhp5();

        $stockService = $this->getStockService();
        $mockParentProduct = $parentId ? $this->getMockProduct($parentId) : null;
        $mockProduct = $this->getMockProduct($productId);

        $result = $stockService->isInStock($mockProduct, $mockParentProduct);

        $this->assertFalse($result);
    }

    public function testStockStatusIsLoadedFromCacheIfAvailable()
    {
        $this->setupPhp5();

        $productId1 = '10';
        $productId2 = '30';
        $mockProduct1 = $this->getMockProduct($productId1);
        $mockProduct1->method('getTypeId')->willReturn('simple');
        $mockProduct1->method('isSaleable')->willReturn(true);
        $mockProduct2 = $this->getMockProduct($productId2);
        $mockProduct2->method('getTypeId')->willReturn('virtual');
        $mockProduct2->method('isSaleable')->willReturn(false);

        $mockStockRegistry = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockRegistry->expects($this->never())->method('getStockStatus');
        // i.e. data is loaded from cache, stockRegistry->getStockStatus is never called

        $mockStockItemCriteriaInterfaceFactory = $this->getMockStockItemCriteriaInterfaceFactory();
        $mockStockItemRepository = $this->getMockStockItemRepository();

        $this->mockGetStockStatusById->expects($this->once())
            ->method('execute')
            ->with([$productId1, $productId2])->willReturn([$productId1 => true, $productId2 => false]);

        $stockService = new StockService (
            $mockStockRegistry,
            $mockStockItemCriteriaInterfaceFactory,
            $mockStockItemRepository,
            $this->mockGetStockStatusById,
            $this->mockMagentoStockHelper,
            $this->mockGetCompositeProductStockStatus
        );

        $stockService->preloadKlevuStockStatus([$mockProduct1->getId(), $mockProduct2->getId()]);

        $this->assertTrue($stockService->isInStock($mockProduct1));
        $this->assertFalse($stockService->isInStock($mockProduct2));
    }

    public function testCacheCanBeCleared()
    {
        $this->setupPhp5();

        $productId1 = '10';
        $productId2 = '30';
        $mockProduct1 = $this->getMockProduct($productId1);;
        $mockProduct1->method('getTypeId')->willReturn('simple');
        $mockProduct1->method('isSaleable')->willReturn(true);
        $mockProduct2 = $this->getMockProduct($productId2);
        $mockProduct2->method('getTypeId')->willReturn('virtual');
        $mockProduct2->method('isSaleable')->willReturn(false);

        $mockStockRegistry = $this->getMockStockRegistry();
        // i.e. data is not loaded from cache, stockRegistry->getStockStatus is called

        $mockStockItemCriteriaInterfaceFactory = $this->getMockStockItemCriteriaInterfaceFactory();
        $mockStockItemRepository = $this->getMockStockItemRepository();

        $this->mockGetStockStatusById->expects($this->once())
            ->method('execute')
            ->with([$productId1, $productId2])->willReturn([$productId1 => true, $productId2 => false]);

        $stockService = new StockService (
            $mockStockRegistry,
            $mockStockItemCriteriaInterfaceFactory,
            $mockStockItemRepository,
            $this->mockGetStockStatusById,
            $this->mockMagentoStockHelper,
            $this->mockGetCompositeProductStockStatus
        );

        $stockService->preloadKlevuStockStatus([$mockProduct1->getId(), $mockProduct2->getId()]);
        $stockService->clearCache();

        $this->assertTrue($stockService->isInStock($mockProduct1));
        $this->assertFalse($stockService->isInStock($mockProduct2));
    }

    /**
     * @return array[]
     */
    public function InStockProductIdsDataProvider()
    {
        return [
            ['10', '20'],
            [null, '20'],
        ];
    }

    /**
     * @return array[]
     */
    public function OutOfStockProductIdsDataProvider()
    {
        return [
            ['10', '30'],
            ['30', '20'],
        ];
    }

    /**
     * @return StockService|object
     */
    private function getStockService()
    {
        $mockStockRegistry = $this->getMockStockRegistry();
        $mockStockItemCriteriaInterface = $this->getMockStockItemCriteriaInterfaceFactory();
        $mockStockItemRepository = $this->getMockStockItemRepository();

        return new StockService(
            $mockStockRegistry,
            $mockStockItemCriteriaInterface,
            $mockStockItemRepository,
            $this->mockGetStockStatusById,
            $this->mockMagentoStockHelper,
            $this->mockGetCompositeProductStockStatus
        );
    }

    /**
     * @return StockItemRepositoryInterface|MockObject
     */
    private function getMockStockItemRepository()
    {
        $mockStockItem1 = $this->getMockStockItem(10, StockStatusInterface::STATUS_IN_STOCK);
        $mockStockItem2 = $this->getMockStockItem(30, StockStatusInterface::STATUS_OUT_OF_STOCK);

        $mockStockItemCollection = $this->getMockBuilder(StockItemCollectionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockItemCollection->method('getItems')
            ->willReturn([
                $mockStockItem1,
                $mockStockItem2
            ]);

        $mockStockItemRepository = $this->getMockBuilder(StockItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockItemRepository->method('getList')->willReturn($mockStockItemCollection);

        return $mockStockItemRepository;
    }

    /**
     * @param string $productId
     * @param int $stockStatus
     *
     * @return StockItemInterface|MockObject
     */
    private function getMockStockItem($productId, $stockStatus)
    {
        $mockStockItem = $this->getMockBuilder(StockItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockItem->method('getProductId')->willReturn($productId);
        $mockStockItem->method('getIsInStock')->willReturn($stockStatus);

        return $mockStockItem;
    }

    /**
     * @return StockItemCriteriaInterfaceFactory|MockObject
     */
    private function getMockStockItemCriteriaInterfaceFactory()
    {
        $mockStockItemCriteria = $this->getMockBuilder(StockItemCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockItemCriteria->method('setProductsFilter');

        $mockStockItemCriteriaFactory = $this->getMockBuilder(StockItemCriteriaInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockItemCriteriaFactory->method('create')->willReturn($mockStockItemCriteria);

        return $mockStockItemCriteriaFactory;
    }

    /**
     * @return StockRegistryInterface|MockObject
     */
    private function getMockStockRegistry()
    {
        $mockStockInterfaceInStock = $this->getMockBuilder(StockStatusInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockInterfaceInStock->method('getStockStatus')
            ->willReturn(StockStatusInterface::STATUS_IN_STOCK);

        $mockStockInterfaceOutOfStock = $this->getMockBuilder(StockStatusInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockInterfaceOutOfStock->method('getStockStatus')
            ->willReturn(StockStatusInterface::STATUS_OUT_OF_STOCK);

        $mockStockRegistry = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockRegistry->expects($this->any())
            ->method('getStockStatus')
            ->willReturnMap([
                ['10', '1', $mockStockInterfaceInStock],
                ['20', '1', $mockStockInterfaceInStock],
                ['30', '1', $mockStockInterfaceOutOfStock]
            ]); // mapping [productId, websiteId, stockInterface to return]

        return $mockStockRegistry;
    }

    /**
     * @return Product|MockObject
     */
    private function getMockProduct($productId)
    {
        $mockStore = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStore->method('getWebsiteId')->willReturn('1');

        $mockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockProduct->method('getId')->willReturn($productId);
        $mockProduct->method('getStore')->willReturn($mockStore);

        return $mockProduct;
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->mockGetStockStatusById = $this->getMockBuilder(GetStockStatusByIdInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockMagentoStockHelper = $this->getMockBuilder(MagentoStockHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
//        $this->mockMagentoStockHelper
//            ->method('assignStatusToProduct')
//            ->willReturnCallback(static function ($product) {
//                $foo = 'bar';
//            });

        $this->mockGetCompositeProductStockStatus = $this->getMockBuilder(GetCompositeProductStockStatusInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockStockRegistryProvider = $this->getMockBuilder(StockRegistryProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
