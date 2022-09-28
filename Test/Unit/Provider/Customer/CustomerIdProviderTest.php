<?php

namespace Klevu\Search\Test\Unit\Provider\Customer;

use Klevu\Search\Api\Provider\Customer\CustomerIdProviderInterface;
use Klevu\Search\Provider\Customer\CustomerIdProvider;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\TestCase;

class CustomerIdProviderTest extends TestCase
{
    public function testItImplementsCustomerIdProviderInterface()
    {
        $observer = $this->instantiateSessionIdProvider();
        $this->assertInstanceOf(CustomerIdProviderInterface::class, $observer);
    }

    /**
     * @return CustomerIdProvider
     */
    private function instantiateSessionIdProvider()
    {
        $mockEncryptor = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new CustomerIdProvider(
            $mockEncryptor
        );
    }
}
