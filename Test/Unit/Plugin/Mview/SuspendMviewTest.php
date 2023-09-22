<?php

namespace Klevu\Search\Test\Unit\Plugin\Mview;

use Klevu\Search\Model\Indexer\Sync\ProductStockSyncIndexer;
use Klevu\Search\Model\Indexer\Sync\ProductSyncIndexer;
use Klevu\Search\Plugin\Mview\View as MviewViewPlugin;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexerProcessor;
use Magento\Framework\Mview\View as MviewView;
use Magento\Framework\Mview\View\StateInterface;
use Magento\Framework\Mview\View\StateInterface as MviewStateInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SuspendMviewTest extends TestCase
{
    /**
     * @var MviewStateInterface|MockObject
     */
    private $mockState;
    /**
     * @var MviewView|MockObject
     */
    private $mockView;
    /**
     * @var LoggerInterface|MockObject
     */
    private $mockLogger;

    public function testSetVersionIdIsNotCalledOnStateForOtherIndexers()
    {
        $this->setupPhp5();

        $this->mockState->expects($this->once())
            ->method('getMode')
            ->willReturn(StateInterface::MODE_ENABLED);
        $this->mockState->expects($this->once())
            ->method('getViewId')
            ->willReturn(PriceIndexerProcessor::INDEXER_ID);
        $this->mockState->expects($this->never())
            ->method('setVersionId');

        $this->mockView->expects($this->exactly(2))
            ->method('getState')
            ->willReturn($this->mockState);

        $proceed = static function () {
        };

        $indexers = [
            ProductSyncIndexer::INDEXER_ID,
            ProductStockSyncIndexer::INDEXER_ID
        ];

        $plugin = new MviewViewPlugin($this->mockLogger, $indexers);
        $plugin->aroundSuspend($this->mockView, $proceed);
    }

    public function testSetVersionIdIsNotCalledOnStateForKlevuProductSyncIndexersWhenDisabled()
    {
        $this->setupPhp5();

        $this->mockState->expects($this->once())
            ->method('getMode')
            ->willReturn(StateInterface::MODE_DISABLED);
        $this->mockState->expects($this->never())
            ->method('getViewId');
        $this->mockState->expects($this->never())
            ->method('setVersionId');

        $this->mockView->expects($this->exactly(2))
            ->method('getState')
            ->willReturn($this->mockState);

        $proceed = static function () {
        };

        $indexers = [
            ProductSyncIndexer::INDEXER_ID,
            ProductStockSyncIndexer::INDEXER_ID
        ];


        $plugin = new MviewViewPlugin($this->mockLogger, $indexers);
        $plugin->aroundSuspend($this->mockView, $proceed);
    }

    public function testSetVersionIdIsCalledOnStateForKlevuProductSyncIndexer()
    {
        $this->setupPhp5();

        $version = 12345;

        $this->mockState->expects($this->once())
            ->method('getVersionId')
            ->willReturn($version);
        $this->mockState->expects($this->once())
            ->method('getMode')
            ->willReturn(StateInterface::MODE_ENABLED);
        $this->mockState->expects($this->once())
            ->method('getViewId')
            ->willReturn(ProductSyncIndexer::INDEXER_ID);
        $this->mockState->expects($this->once())
            ->method('setVersionId')
            ->with($version);
        $this->mockState->expects($this->once())
            ->method('save');

        $this->mockView->expects($this->exactly(2))
            ->method('getState')
            ->willReturn($this->mockState);

        $proceed = static function () {
        };

        $indexers = [
            ProductSyncIndexer::INDEXER_ID,
            ProductStockSyncIndexer::INDEXER_ID
        ];

        $plugin = new MviewViewPlugin($this->mockLogger, $indexers);
        $plugin->aroundSuspend($this->mockView, $proceed);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->mockState = $this->getMockBuilder(MviewStateInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockView = $this->getMockBuilder(MviewView::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
