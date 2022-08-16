<?php

namespace Klevu\Search\Testest\Unit\Service\Catalog\Product;

use Klevu\Search\Service\Catalog\Product\GetStockStatusById;
use Magento\CatalogInventory\Api\Data\StockItemCollectionInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetStockStatusByIdTest extends TestCase
{
    /**
     * @var StockItemCriteriaInterfaceFactory|MockObject
     */
    private $mockStockItemCriteriaFactory;
    /**
     * @var StockItemCriteriaInterface|MockObject
     */
    private $mockStockItemCriteria;
    /**
     * @var StockItemRepositoryInterface|MockObject
     */
    private $mockStockItemRepository;

    public function testReturnsEmptyArrayIfNoProductIdsProvided()
    {
        $this->setupPhp5();

        $productIds = [];
        $scopeId = null;

        $getStockStatusById = $this->instantiateGetStockStatusById();
        $stockStatus = $getStockStatusById->execute($productIds, $scopeId);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($stockStatus);
        } else {
            $this->assertTrue(is_array($stockStatus), 'Is Array');
        }
        $this->assertCount(0, $stockStatus);
    }

    public function testReturnsEmptyArrayIfNoStockItemsFound()
    {
        $this->setupPhp5();

        $productIds = [1, 2, 3];
        $scopeId = null;

        $this->mockStockItemCriteria->expects($this->once())->method('setProductsFilter')->with($productIds);

        $this->mockStockItemCriteriaFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mockStockItemCriteria);

        $mockStockItemCollecton = $this->getMockBuilder(StockItemCollectionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockItemCollecton->expects($this->once())->method('getItems')->willReturn([]);

        $this->mockStockItemRepository->expects($this->once())
            ->method('getList')
            ->with($this->mockStockItemCriteria)
            ->willReturn($mockStockItemCollecton);

        $getStockStatusById = $this->instantiateGetStockStatusById();
        $stockStatus = $getStockStatusById->execute($productIds, $scopeId);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($stockStatus);
        } else {
            $this->assertTrue(is_array($stockStatus), 'Is Array');
        }
        $this->assertCount(0, $stockStatus);
    }

    public function testReturnsArray()
    {
        $this->setupPhp5();

        $productIds = [1, 2 ,3];
        $scopeId = null;
        $stockItems = [
            1 => ['product_id' => 1, 'in_stock' => true],
            2 => ['product_id' => 2, 'in_stock' => false],
            3 => ['product_id' => 3, 'in_stock' => true]
        ];

        $this->mockStockItemCriteria->expects($this->once())->method('setProductsFilter')->with($productIds);

        $this->mockStockItemCriteriaFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mockStockItemCriteria);

        $mockStockItem1 = $this->getMockBuilder(StockItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockItem1->expects($this->once())->method('getProductId')->willReturn($stockItems[1]['product_id']);
        $mockStockItem1->expects($this->once())->method('getIsInStock')->willReturn($stockItems[1]['in_stock']);

        $mockStockItem2 = $this->getMockBuilder(StockItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockItem2->expects($this->once())->method('getProductId')->willReturn($stockItems[2]['product_id']);
        $mockStockItem2->expects($this->once())->method('getIsInStock')->willReturn($stockItems[2]['in_stock']);

        $mockStockItem3 = $this->getMockBuilder(StockItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockItem3->expects($this->once())->method('getProductId')->willReturn($stockItems[3]['product_id']);
        $mockStockItem3->expects($this->once())->method('getIsInStock')->willReturn($stockItems[3]['in_stock']);

        $mockStockItemCollection = $this->getMockBuilder(StockItemCollectionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockItemCollection->expects($this->once())
            ->method('getItems')
            ->willReturn([
                $mockStockItem1,
                $mockStockItem2,
                $mockStockItem3
            ]);

        $this->mockStockItemRepository->expects($this->once())
            ->method('getList')
            ->with($this->mockStockItemCriteria)
            ->willReturn($mockStockItemCollection);

        $getStockStatusById = $this->instantiateGetStockStatusById();
        $stockStatus = $getStockStatusById->execute($productIds, $scopeId);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($stockStatus);
        } else {
            $this->assertTrue(is_array($stockStatus), 'Is Array');
        }
        $this->assertCount(3, $stockStatus);

        $this->assertArrayHasKey(1, $stockStatus);
        $this->assertTrue($stockStatus[1]);

        $this->assertArrayHasKey(2, $stockStatus);
        $this->assertFalse($stockStatus[2]);

        $this->assertArrayHasKey(3, $stockStatus);
        $this->assertTrue($stockStatus[3]);
    }

    private function setupPhp5()
    {
        $this->mockStockItemCriteriaFactory = $this->getMockBuilder(StockItemCriteriaInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStockItemCriteria = $this->getMockBuilder(StockItemCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStockItemRepository = $this->getMockBuilder(StockItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function instantiateGetStockStatusById()
    {
        return new GetStockStatusById(
            $this->mockStockItemCriteriaFactory,
            $this->mockStockItemRepository
        );
    }
}
