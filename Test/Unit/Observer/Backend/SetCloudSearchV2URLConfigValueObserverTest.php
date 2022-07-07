<?php

namespace Klevu\Search\Test\Integration\Model\Observer\Backend;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Source\ThemeVersion;
use Klevu\Search\Observer\Backend\SetCloudSearchV2UrlConfigValueObserver;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriter;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SetCloudSearchV2URLConfigValueObserverTest extends TestCase
{
    /**
     * @var ScopeConfigWriter|MockObject
     */
    private $mockScopeConfigWriter;
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $mockScopeConfig;
    /**
     * @var ReinitableConfigInterface|MockObject
     */
    private $mockReinitableConfig;

    public function testImplementsObserverInterface()
    {
        $this->setUpPhp5();

        $this->assertInstanceOf(ObserverInterface::class, $this->instantiateObserver());
    }

    public function testSave_IsNotCalled_OtherSystemConfigSections()
    {
        $this->setUpPhp5();
        $this->mockScopeConfigWriter->expects($this->never())->method('save');

        $mockRequest = $this->getMockRequest('general');
        $mockConfigData = $this->getConfigData();

        $this->dispatchEvent($mockConfigData, $mockRequest);
    }

    public function testSave_IsNotCalled_ForThemeV1()
    {
        $this->setUpPhp5();
        $this->mockScopeConfigWriter->expects($this->never())->method('save');

        $mockRequest = $this->getMockRequest();
        $mockConfigData = $this->getConfigData();
        $mockConfigData['groups']['developer']['fields']['theme_version']['value'] = ThemeVersion::V1;

        $this->dispatchEvent($mockConfigData, $mockRequest);
    }

    public function testSave_IsNotCalled_ForMissingStoreId()
    {
        $this->setUpPhp5();
        $this->mockScopeConfigWriter->expects($this->never())->method('save');

        $mockRequest = $this->getMockRequest();
        $mockConfigData = $this->getConfigData();
        $mockConfigData['store'] = null;

        $this->dispatchEvent($mockConfigData, $mockRequest);
    }

    public function testSave_IsNotCalled_WhenV2UrlIsPresent()
    {
        $this->setUpPhp5();
        $this->mockScopeConfigWriter->expects($this->never())->method('save');

        $this->mockScopeConfig->expects($this->atLeastOnce())->method('getValue')->willReturnCallback(function($xmlPath) {
            switch ($xmlPath) {
                case ConfigHelper::XML_PATH_CLOUD_SEARCH_URL:
                    return 'eucs26.ksearchnet.com';
                case ConfigHelper::XML_PATH_CLOUD_SEARCH_V2_URL:
                    return 'eucs26v2.ksearchnet.com';
                default:
                    return '';
            }
        });

        $mockRequest = $this->getMockRequest();
        $mockConfigData = $this->getConfigData();

        $this->dispatchEvent($mockConfigData, $mockRequest);
    }

    public function testSave_IsNotCalled_WhenV1UrlIsMissing()
    {
        $this->setUpPhp5();
        $this->mockScopeConfigWriter->expects($this->never())->method('save');

        $this->mockScopeConfig->expects($this->atLeastOnce())->method('getValue')->willReturnCallback(function($xmlPath) {
            switch ($xmlPath) {
                case ConfigHelper::XML_PATH_CLOUD_SEARCH_V2_URL:
                    return 'eucs26v2.ksearchnet.com';
                default:
                    return '';
            }
        });

        $mockRequest = $this->getMockRequest();
        $mockConfigData = $this->getConfigData();

        $this->dispatchEvent($mockConfigData, $mockRequest);
    }

    public function testSave_IsCalled_When_ThemeV2_StoreIdSet_V1UrlSet_V2UrlNotSet()
    {
        $this->setUpPhp5();
        $this->mockScopeConfigWriter->expects($this->once())->method('save');

        $this->mockScopeConfig->expects($this->atLeastOnce())->method('getValue')->willReturnCallback(function($xmlPath) {
            switch ($xmlPath) {
                case ConfigHelper::XML_PATH_CLOUD_SEARCH_URL:
                    return 'eucs26.ksearchnet.com';
                default:
                    return '';
            }
        });

        $mockRequest = $this->getMockRequest();
        $mockConfigData = $this->getConfigData();

        $this->dispatchEvent($mockConfigData, $mockRequest);
    }

    public function testSave_IsCalled_When_ThemeV2_StoreIdSet_V1UrlSet_V2UrlIsDefault()
    {
        $this->setUpPhp5();
        $this->mockScopeConfigWriter->expects($this->once())->method('save');

        $this->mockScopeConfig->expects($this->atLeastOnce())->method('getValue')->willReturnCallback(function($xmlPath) {
            switch ($xmlPath) {
                case ConfigHelper::XML_PATH_CLOUD_SEARCH_URL:
                    return 'eucs26.ksearchnet.com';
                case ConfigHelper::XML_PATH_CLOUD_SEARCH_V2_URL:
                    return SetCloudSearchV2UrlConfigValueObserver::DEFAULT_CLOUD_SEARCH_V2_VALUE;
                default:
                    return '';
            }
        });

        $mockRequest = $this->getMockRequest();
        $mockConfigData = $this->getConfigData();

        $this->dispatchEvent($mockConfigData, $mockRequest);
    }

    /**
     * @param array $mockConfigData
     * @param $mockRequest
     *
     * @return void
     */
    private function dispatchEvent(array $mockConfigData, $mockRequest)
    {
        $mockEvent = $this->getMockBuilder(Event::class)->disableOriginalConstructor()->getMock();
        $mockEvent->method('getDataUsingMethod')->willReturnMap([
            ['request', null, $mockRequest],
        ]);
        $mockEvent->method('getData')->willReturnMap([
            ['configData', null, $mockConfigData]
        ]);

        $mockEventObserver = $this->getMockBuilder(EventObserver::class)->disableOriginalConstructor()->getMock();
        $mockEventObserver->method('getEvent')->willReturn($mockEvent);
        $mockEventObserver->method('getDataUsingMethod')->willReturnMap([
            ['request', null, $mockRequest],
        ]);
        $mockEventObserver->method('getData')->willReturnMap([
            ['configData', null, $mockConfigData]
        ]);

        $observer = $this->instantiateObserver();
        $observer->execute($mockEventObserver);
    }

    /**
     * @return HttpRequest|MockObject
     */
    private function getMockRequest($section = SetCloudSearchV2UrlConfigValueObserver::CONFIG_SECTION)
    {
        $mockRequest = $this->getMockBuilder(HttpRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRequest->expects($this->once())
            ->method('getFullActionName')
            ->willReturn(SetCloudSearchV2UrlConfigValueObserver::FULL_ACTION_NAME);
        $mockRequest->expects($this->once())
            ->method('getParam')
            ->willReturnCallback(function ($param) use ($section) {
            if ($param === 'section') {
                return $section;
            }

            return null;
        });

        return $mockRequest;
    }

    /**
     * @return SetCloudSearchV2UrlConfigValueObserver
     */
    private function instantiateObserver()
    {
        return new SetCloudSearchV2UrlConfigValueObserver(
            $this->mockScopeConfigWriter,
            $this->mockScopeConfig,
            $this->mockReinitableConfig
        );
    }

    /**
     * @return array
     */
    private function getConfigData()
    {
        return [
            'store' => '1',
            'section' => 'klevu_search',
            'groups' => [
                'developer' => [
                    'fields' => [
                        'theme_version' => ['value' => ThemeVersion::V2],
                    ]
                ]
            ]
        ];
    }

    private function setUpPhp5()
    {
        $this->mockScopeConfigWriter = $this->getMockBuilder(ScopeConfigWriter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockScopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockReinitableConfig = $this->getMockBuilder(ReinitableConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
