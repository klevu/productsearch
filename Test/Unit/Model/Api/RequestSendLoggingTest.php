<?php

namespace Klevu\Search\Test\Unit\Model\Api;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Api\Request;
use Klevu\Search\Model\Api\Response;
use Laminas\Http\Client;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestSendLoggingTest extends TestCase
{
    public function testSendStringifiesDataWhenLoggingLevelIsDebug()
    {
        $logLevel = LoggerConstants::ZEND_LOG_DEBUG;
        $mockRequest = $this->getPartialMockRequest($logLevel);
        $mockRequest->send();
    }

    /**
     * @dataProvider LogLevelsDataProvider
     */
    public function testSendDoesNotStringifiesDataWhenLoggingLevelIsLessThanDebug($logLevel)
    {
        $mockRequest = $this->getPartialMockRequest($logLevel);
        $mockRequest->send();
    }

    /**
     * @return array[]
     */
    public function LogLevelsDataProvider()
    {
        return [
            [LoggerConstants::ZEND_LOG_EMERG],
            [LoggerConstants::ZEND_LOG_ALERT],
            [LoggerConstants::ZEND_LOG_CRIT],
            [LoggerConstants::ZEND_LOG_ERR],
            [LoggerConstants::ZEND_LOG_WARN],
            [LoggerConstants::ZEND_LOG_NOTICE],
            [LoggerConstants::ZEND_LOG_INFO]
        ];
    }

    /**
     * @param $logLevel
     *
     * @return Request|MockObject
     */
    private function getPartialMockRequest($logLevel)
    {
        $mockKlevuApiResponse = $this->getKlevuApiMockResponse();
        $mockSearchHelper = $this->getMockSearchHelper($logLevel);
        $mockConfigHelper = $this->getMockConfigHelper($logLevel);
        $mockRempty = $this->getMockRempty();
        $mockClient = $this->getMockClient();

        $mockRequest = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([
                'modelApiResponse' => $mockKlevuApiResponse,
                'searchHelperData' => $mockSearchHelper,
                'searchHelperConfig' => $mockConfigHelper,
                'apiResponseEmpty' => $mockRempty
            ])
            ->onlyMethods(['build', 'getEndpoint', 'getMethod', 'getResponseModel'])
            ->getMock();
        $mockRequest->expects($this->once())->method('build')->willReturn($mockClient);
        $mockRequest->expects($this->any())->method('getEndpoint')->willReturn('some_string');
        $mockRequest->expects($this->any())->method('getMethod')->willReturn('POST');
        $mockRequest->expects($this->any())->method('getResponseModel')->willReturn($mockKlevuApiResponse);

        return $mockRequest;
    }

    /**
     * @param bool $isLoggingEnabled
     *
     * @return mixed
     */
    private function getMockSearchHelper($logLevel)
    {
        $mockSearchHelper = $this->getMockBuilder(SearchHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($logLevel === LoggerConstants::ZEND_LOG_DEBUG) {
            $mockSearchHelper->expects($this->any())
                ->method('log');
        } else {
            $mockSearchHelper->expects($this->never())
                ->method('log');
        }

        return $mockSearchHelper;
    }

    /**
     * @param $logLevel
     *
     * @return ConfigHelper|MockObject
     */
    private function getMockConfigHelper($logLevel)
    {
        $mockConfigHelper = $this->getMockBuilder(ConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockConfigHelper->expects($this->once())
            ->method('getLogLevel')
            ->willReturn($logLevel);

        return $mockConfigHelper;
    }

    /**
     * @return Client|MockObject
     */
    private function getMockClient()
    {
        $mockClientResponse = $this->getMockBuilder(\Laminas\Http\Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockClient->expects($this->once())
            ->method('send')
            ->willReturn($mockClientResponse);

        return $mockClient;
    }

    /**
     * @return Response|MockObject
     */
    private function getKlevuApiMockResponse()
    {
        return $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return Rempty|MockObject
     */
    private function getMockRempty()
    {
        return $this->getMockBuilder(\Klevu\Search\Model\Api\Response\Rempty::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}