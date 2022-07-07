<?php

namespace Klevu\Search\Test\Unit\Service\WebRestApi\Admin;

use Klevu\Search\Api\Service\WebRestApi\Admin\GetBearerTokenInterface;
use Klevu\Search\Service\WebRestApi\Admin\GetBearerTokenService;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Integration\Model\Oauth\TokenFactory;
use PHPUnit\Framework\TestCase;

class GetBearerTokenTest extends TestCase
{
    public function testItImplementsGetKmcUrlServiceInterface()
    {
        $observer = $this->instantiateGetBearerTokenService();
        $this->assertInstanceOf(GetBearerTokenInterface::class, $observer);
    }

    /**
     * @return GetBearerTokenService
     */
    private function instantiateGetBearerTokenService()
    {
        $mockAdminSession = $this->getMockBuilder(AdminSession::class)->disableOriginalConstructor()->getMock();
        $mockTokenFactory = $this->getMockBuilder(TokenFactory::class)->disableOriginalConstructor()->getMock();

        return new GetBearerTokenService(
            $mockTokenFactory,
            $mockAdminSession
        );
    }
}
