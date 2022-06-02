<?php

namespace Klevu\Search\test\Unit\Service\Catalog\Product;

use Klevu\Search\Service\Catalog\Product\Stock as StockService;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\Data\StockItemCollectionInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StockTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @dataProvider InStockProductIdsDataProvider
     */
    public function testGetKlevuStockStatusReturnsYesStringWhenParentAndChildInStock($parentId, $productId)
    {
        $this->setupPhp5();

        $stockService = $this->getStockService();
        $mockParentProduct = $parentId ? $this->getMockProduct($parentId) : null;
        $mockProduct = $this->getMockProduct($productId);

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
        $mockProduct = $this->getMockProduct($productId);

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

    /**
     * @return array[]
     */
    public function InStockProductIdsDataProvider()
    {
        return [
            ['10', '20'],
            [null, '20']
        ];
    }

    /**
     * @return array[]
     */
    public function OutOfStockProductIdsDataProvider()
    {
        return [
            ['10', '30'],
            ['30', '20']
        ];
    }

    public function testStockStatusIsLoadedFromCacheIfAvailable()
    {
        $this->setupPhp5();

        $mockProduct1 = $this->getMockProduct('10');
        $mockProduct2 = $this->getMockProduct('30');

        $mockStockRegistry = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockRegistry->expects($this->never())->method('getStockStatus');
        // i.e. data is loaded from cache, stockRegistry->getStockStatus is never called

        $mockStockItemCriteriaInterfaceFactory = $this->getMockStockItemCriteriaInterfaceFactory();
        $mockStockItemRepository = $this->getMockStockItemRepository();

        $stockService = $this->objectManager->getObject(StockService::class, [
            'stockRegistryInterface' => $mockStockRegistry,
            'stockItemCriteriaInterfaceFactory' => $mockStockItemCriteriaInterfaceFactory,
            'stockItemRepository' => $mockStockItemRepository
        ]);

        $stockService->preloadKlevuStockStatus([$mockProduct1->getId(), $mockProduct2->getId()]);

        $this->assertTrue($stockService->isInStock($mockProduct1));
        $this->assertFalse($stockService->isInStock($mockProduct2));
    }

    public function testCacheCanBeCleared()
    {
        $this->setupPhp5();

        $mockProduct1 = $this->getMockProduct('10');
        $mockProduct2 = $this->getMockProduct('30');

        $mockStockRegistry = $this->getMockStockRegistry();
        // i.e. data is not loaded from cache, stockRegistry->getStockStatus is called

        $mockStockItemCriteriaInterfaceFactory = $this->getMockStockItemCriteriaInterfaceFactory();
        $mockStockItemRepository = $this->getMockStockItemRepository();

        $stockService = $this->objectManager->getObject(StockService::class, [
            'stockRegistryInterface' => $mockStockRegistry,
            'stockItemCriteriaInterfaceFactory' => $mockStockItemCriteriaInterfaceFactory,
            'stockItemRepository' => $mockStockItemRepository
        ]);

        $stockService->preloadKlevuStockStatus([$mockProduct1->getId(), $mockProduct2->getId()]);
        $stockService->clearCache();

        $this->assertTrue($stockService->isInStock($mockProduct1));
        $this->assertFalse($stockService->isInStock($mockProduct2));
    }

    /**
     * @return StockService|object
     */
    private function getStockService()
    {
        $mockStockRegistry = $this->getMockStockRegistry();
        $mockStockItemCriteriaInterface = $this->getMockStockItemCriteriaInterfaceFactory();
        $mockStockItemRepository = $this->getMockStockItemRepository();

        return $this->objectManager->getObject(StockService::class, [
            'stockRegistryInterface' => $mockStockRegistry,
            'stockItemCriteriaInterface' => $mockStockItemCriteriaInterface,
            'stockItemRepository' => $mockStockItemRepository
        ]);
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
        $mockStockItemCollection->expects($this->any())
            ->method('getItems')
            ->willReturn([
                $mockStockItem1,
                $mockStockItem2
            ]);

        $mockStockItemRepository = $this->getMockBuilder(StockItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockItemRepository->expects($this->any())
            ->method('getList')
            ->willReturn($mockStockItemCollection);

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
        $mockStockItem->expects($this->any())
            ->method('getProductId')
            ->willReturn($productId);
        $mockStockItem->expects($this->any())
            ->method('getIsInStock')
            ->willReturn($stockStatus);

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
        $mockStockItemCriteria->expects($this->any())
            ->method('setProductsFilter');

        $mockStockItemCriteriaFactory = $this->getMockBuilder(StockItemCriteriaInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockItemCriteriaFactory->expects($this->any())
            ->method('create')
            ->willReturn($mockStockItemCriteria);

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
        $mockStockInterfaceInStock->expects($this->any())
            ->method('getStockStatus')
            ->willReturn(StockStatusInterface::STATUS_IN_STOCK);

        $mockStockInterfaceOutOfStock = $this->getMockBuilder(StockStatusInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockInterfaceOutOfStock->expects($this->any())
            ->method('getStockStatus')
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
        $mockStore->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn('1');

        $mockProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockProduct->expects($this->any())
            ->method('getId')
            ->willReturn($productId);
        $mockProduct->expects($this->any())
            ->method('getStore')
            ->willReturn($mockStore);

        return $mockProduct;
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = new ObjectManager($this);
    }
}
