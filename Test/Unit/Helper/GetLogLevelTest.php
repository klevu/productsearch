<?php

namespace Klevu\Search\Test\Unit\Helper;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\VersionReader;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Config\Model\ResourceModel\ConfigFactory as ConfigResourceFactory;
use Magento\Framework\App\Config as ScopeConfig;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Url;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetLogLevelTest extends TestCase
{
    /**
     * @var ScopeConfig|MockObject
     */
    private $mockScopeConfig;
    /**
     * @var Url|MockObject
     */
    private $mockUrl;
    /**
     * @var StoreManager|MockObject
     */
    private $mockStoreManager;
    /**
     * @var Store|MockObject
     */
    private $mockStore;
    /**
     * @var Http|MockObject
     */
    private $mockRequest;
    /**
     * @var ScopeConfig\Value|MockObject
     */
    private $mockConfigValue;
    /**
     * @var ResourceConnection|MockObject
     */
    private $mockResourceConnection;
    /**
     * @var VersionReader|MockObject
     */
    private $mockVersionReader;
    /**
     * @var State|MockObject
     */
    private $mockState;
    /**
     * @var MockObject&ConfigResource
     */
    private $mockConfigResource;
    /**
     * @var MockObject&SerializerInterface
     */
    private $mockSerializer;

    public function testReturnsDefaultLevelWhenDbValueIsNullAndStoreProvided()
    {
        $this->setupPhp5();

        $this->mockState->expects($this->never())->method('getAreaCode');

        $this->mockScopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ConfigHelper::XML_PATH_LOG_LEVEL, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);

        $configHelper = $this->instantiateConfigHelper();

        $expected = LoggerConstants::ZEND_LOG_INFO;
        $this->assertSame($expected, $configHelper->getLogLevel($this->mockStore));
    }

    public function testReturnsDefaultLevelWhenDbValueIsNull()
    {
        $this->setupPhp5();

        $this->mockState->expects($this->once())->method('getAreaCode')->wilLReturn('adminhtml');

        $this->mockRequest->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn(1);

        $this->mockScopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ConfigHelper::XML_PATH_LOG_LEVEL, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);

        $configHelper = $this->instantiateConfigHelper();

        $expected = LoggerConstants::ZEND_LOG_INFO;
        $this->assertSame($expected, $configHelper->getLogLevel());
    }

    public function testReturnsDbValueWhenNotNullAndStoreProvided()
    {
        $this->setupPhp5();

        $this->mockRequest->expects($this->never())->method('getParam');

        $this->mockScopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ConfigHelper::XML_PATH_LOG_LEVEL, ScopeInterface::SCOPE_STORE)
            ->willReturn(\Klevu\Logger\Constants::ZEND_LOG_DEBUG);

        $configHelper = $this->instantiateConfigHelper();

        $expected = \Klevu\Logger\Constants::ZEND_LOG_DEBUG;
        $this->assertSame($expected, $configHelper->getLogLevel($this->mockStore));
    }

    public function testReturnsDbValueWhenNotNull()
    {
        $this->setupPhp5();

        $this->mockState->expects($this->once())->method('getAreaCode')->wilLReturn('adminhtml');

        $this->mockRequest->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn(1);

        $this->mockScopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ConfigHelper::XML_PATH_LOG_LEVEL, ScopeInterface::SCOPE_STORE)
            ->willReturn(\Klevu\Logger\Constants::ZEND_LOG_DEBUG);

        $configHelper = $this->instantiateConfigHelper();

        $expected = \Klevu\Logger\Constants::ZEND_LOG_DEBUG;
        $this->assertSame($expected, $configHelper->getLogLevel());
    }

    /**
     * @dataProvider incorrectDbValues
     */
    public function testReturnsDefaultIfConfigValueNotNumeric($dbValue)
    {
        $this->setupPhp5();

        $this->mockRequest->expects($this->never())->method('getParam');

        $this->mockScopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ConfigHelper::XML_PATH_LOG_LEVEL, ScopeInterface::SCOPE_STORE)
            ->willReturn($dbValue);

        $configHelper = $this->instantiateConfigHelper();

        $expected = \Klevu\Logger\Constants::ZEND_LOG_INFO;
        $this->assertSame($expected, $configHelper->getLogLevel($this->mockStore));
    }

    public function testHandlesGetAppAreaException()
    {
        $this->setupPhp5();

        $this->mockState->expects($this->once())
            ->method('getAreaCode')
            ->willThrowException(
                new LocalizedException(__('Area code is not set'))
            );

        $this->mockRequest->expects($this->never())->method('getParam');

        $this->mockScopeConfig->expects($this->once())
            ->method('getValue')
            ->with(ConfigHelper::XML_PATH_LOG_LEVEL, ScopeInterface::SCOPE_STORE)
            ->willReturn(\Klevu\Logger\Constants::ZEND_LOG_DEBUG);

        $configHelper = $this->instantiateConfigHelper();

        $expected = \Klevu\Logger\Constants::ZEND_LOG_DEBUG;
        $this->assertSame($expected, $configHelper->getLogLevel());
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->mockScopeConfig = $this->getMockBuilder(ScopeConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockUrl = $this->getMockBuilder(Url::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStoreManager = $this->getMockBuilder(StoreManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRequest = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStore = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockConfigValue = $this->getMockBuilder(\Magento\Framework\App\Config\Value::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockResourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockVersionReader = $this->getMockBuilder(VersionReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockState = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockConfigResource = $this->getMockBuilder(ConfigResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockSerializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ConfigHelper
     */
    private function instantiateConfigHelper()
    {
        $mockConfigResourceFactory = $this->getMockBuilder(ConfigResourceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockConfigResourceFactory->method('create')->willReturn($this->mockConfigResource);

        return new ConfigHelper(
            $this->mockScopeConfig,
            $this->mockUrl,
            $this->mockStoreManager,
            $this->mockRequest,
            $this->mockStore,
            $this->mockConfigValue,
            $this->mockResourceConnection,
            $this->mockVersionReader,
            $this->mockState,
            $this->mockSerializer,
            $mockConfigResourceFactory
        );
    }

    /**
     * @return array
     */
    public function incorrectDbValues()
    {
        return [
            [\Psr\Log\LogLevel::DEBUG],
            ['123d'],
            [[1]],
            [json_encode(['1'])],
            [json_decode(json_encode(['1']))],
        ];
    }
}
