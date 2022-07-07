<?php

namespace Klevu\Search\Test\Unit\Observer\Backend;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Observer\Backend\RestApiKeyChanged as RestApiKeyChangedObserver;
use Klevu\Search\Service\Account\GetFeatures;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriterInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AdminSystemConfigRestApiKeyChangedObserverTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $mockStoreManager;
    /**
     * @var ScopeConfigWriterInterface|MockObject
     */
    private $mockConfigWriter;
    /**
     * @var MockObject|LoggerInterface
     */
    private $mockLogger;
    /**
     * @var ReinitableConfigInterface|mixed
     */
    private $mockReinitableConfig;

    public function testItImplementsObserverInterface()
    {
        $this->setupPhp5();

        $observer = $this->instantiateRestApiKeyChangedObserver();
        $this->assertInstanceOf(ObserverInterface::class, $observer);
    }

    public function testDoesNotResetLastSyncDateIfRestApiIsNotUpdated()
    {
        $this->setupPhp5();

        $this->mockStoreManager->expects($this->never())->method('getStore');

        $this->mockConfigWriter->expects($this->never())->method('save');

        $mockEvent = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $mockEvent->method('getData')->willReturnMap([
            ['changed_paths', null, ['some/other/path']],
            ['store', null, 'default']
        ]);

        $observer = $this->instantiateRestApiKeyChangedObserver();
        $observer->execute($mockEvent);
    }

    /**
     * @dataProvider changedPathsDataProvider
     */
    public function testResetsLastSyncDateIfRestApiUpdated($xmlFieldPath)
    {
        $this->setupPhp5();
        $store = 'default';

        $mockStore = $this->getMockBuilder(StoreInterface::class)->getMock();
        $mockStore->method('getId')->willReturn(1);

        $this->mockStoreManager->expects($this->once())
            ->method('getStore')
            ->with($store)
            ->willReturn($mockStore);

        $this->mockConfigWriter->expects($this->once())
            ->method('save')
            ->with(
                GetFeatures::XML_PATH_FEATURES_LAST_SYNC_DATE,
                0,
                ScopeInterface::SCOPE_STORES,
                1
            );

        $mockEvent = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $mockEvent->method('getData')->willReturnMap([
            ['changed_paths', null, [$xmlFieldPath]],
            ['store', null, 'default'],
            ['website', null, 'base']
        ]);

        $observer = $this->instantiateRestApiKeyChangedObserver();
        $observer->execute($mockEvent);
    }

    public function changedPathsDataProvider()
    {
        return [
            [ConfigHelper::XML_PATH_REST_API_KEY]
        ];
    }

    /**
     * @return RestApiKeyChangedObserver
     */
    private function instantiateRestApiKeyChangedObserver()
    {
        return new RestApiKeyChangedObserver(
            $this->mockStoreManager,
            $this->mockConfigWriter,
            $this->mockLogger,
            $this->mockReinitableConfig
        );
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->mockStoreManager = $this->getMockBuilder(StoreManagerInterface::class)->getMock();
        $this->mockConfigWriter = $this->getMockBuilder(ScopeConfigWriterInterface::class)->getMock();
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->mockReinitableConfig = $this->getMockBuilder(ReinitableConfigInterface::class)->getMock();
    }
}
