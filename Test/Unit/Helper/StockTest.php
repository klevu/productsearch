<?php

namespace Klevu\Search\Test\Unit\Helper;

use Klevu\Search\Api\Service\Catalog\Product\StockServiceInterface;
use Klevu\Search\Helper\Stock as StockHelper;
use Klevu\Search\Service\Catalog\Product\Stock;
use Magento\Catalog\Model\Product;
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

        $mockStockServiceInterface = $this->getMockBuilder(StockServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockServiceInterface->expects($this->once())
            ->method('getKlevuStockStatus')
            ->willReturn(Stock::KLEVU_IN_STOCK);

        $stockHelper = $this->getStockHelper($mockStockServiceInterface);

        $mockParentProduct = $parentId ? $this->getMockProduct($parentId) : null;
        $mockProduct = $this->getMockProduct($productId);
        $result = $stockHelper->getKlevuStockStatus($mockParentProduct, $mockProduct);

        $this->assertSame(Stock::KLEVU_IN_STOCK, $result);
    }

    /**
     * @dataProvider OutOfStockProductIdsDataProvider
     */
    public function testGetKlevuStockStatusReturnsNoStringWhenParentInStockAndChildOutOfStock($parentId, $productId)
    {
        $this->setupPhp5();

        $mockStockServiceInterface = $this->getMockBuilder(StockServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStockServiceInterface->expects($this->once())
            ->method('getKlevuStockStatus')
            ->willReturn(Stock::KLEVU_OUT_OF_STOCK);

        $stockHelper = $this->getStockHelper($mockStockServiceInterface);

        $mockParentProduct = $parentId ? $this->getMockProduct($parentId) : null;
        $mockProduct = $this->getMockProduct($productId);
        $result = $stockHelper->getKlevuStockStatus($mockParentProduct, $mockProduct);

        $this->assertSame(Stock::KLEVU_OUT_OF_STOCK, $result);
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

    /**
     * @param StockServiceInterface|MockObject $mockStockServiceInterface
     *
     * @return StockHelper|object
     */
    private function getStockHelper($mockStockServiceInterface)
    {
        return $this->objectManager->getObject(StockHelper::class, [
            'stockRegistryInterface' => $this->getMockStockRegistry(),
            'stockService' => $mockStockServiceInterface,
        ]);
    }

    /**
     * @return StockRegistryInterface|MockObject
     */
    private function getMockStockRegistry()
    {
        return $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
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
