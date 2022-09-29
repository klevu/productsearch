<?php

namespace Klevu\Search\Test\Unit\Provider\Customer;

use Klevu\Search\Api\Provider\Customer\SessionIdProviderInterface;
use Klevu\Search\Provider\Customer\SessionIdProvider;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Session\SessionManagerInterface;
use PHPUnit\Framework\TestCase;

class SessionIdProviderTest extends TestCase
{
    public function testItImplementsSessionIdProviderInterface()
    {
        $observer = $this->instantiateSessionIdProvider();
        $this->assertInstanceOf(SessionIdProviderInterface::class, $observer);
    }

    /**
     * @return SessionIdProvider
     */
    private function instantiateSessionIdProvider()
    {
        $mockEncryptor = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockSessionManager = $this->getMockBuilder(SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new SessionIdProvider(
            $mockEncryptor,
            $mockSessionManager
        );
    }
}
