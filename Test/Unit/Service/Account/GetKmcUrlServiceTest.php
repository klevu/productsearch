<?php

namespace Klevu\Search\Test\Unit\Service\Account;

use Klevu\Search\Api\Service\Account\GetKmcUrlServiceInterface;
use Klevu\Search\Service\Account\GetKmcUrlService;
use Magento\Framework\App\Config\ConfigSourceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GetKmcUrlServiceTest extends TestCase
{
    public function testItImplementsGetKmcUrlServiceInterface()
    {
        $observer = $this->instantiateKmcUrlService();
        $this->assertInstanceOf(GetKmcUrlServiceInterface::class, $observer);
    }

    /**
     * @return GetKmcUrlService
     */
    private function instantiateKmcUrlService()
    {
        $mockConfigSource = $this->getMockBuilder(ConfigSourceInterface::class)->disableOriginalConstructor()->getMock();
        $mockScopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $MockRequest = $this->getMockBuilder(RequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockLogger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();

        return new GetKmcUrlService(
            $mockConfigSource,
            $mockScopeConfig,
            $MockRequest,
            $mockLogger
        );
    }
}
