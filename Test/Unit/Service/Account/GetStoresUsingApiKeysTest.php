<?php

namespace Klevu\Search\Test\Unit\Service\Account;

use Klevu\Search\Api\Service\Account\GetStoresUsingApiKeysInterface;
use Klevu\Search\Service\Account\GetStoresUsingApiKeys;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Validator\ValidatorInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class GetStoresUsingApiKeysTest extends TestCase
{
    public function testItImplementsGetKmcUrlServiceInterface()
    {
        $observer = $this->instantiateGetStoresUsingApiKeysService();
        $this->assertInstanceOf(GetStoresUsingApiKeysInterface::class, $observer);
    }

    /**
     * @return GetStoresUsingApiKeys
     */
    private function instantiateGetStoresUsingApiKeysService()
    {
        $mockStoreManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockScopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockJsApiValidator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRestApiValidator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new GetStoresUsingApiKeys(
            $mockStoreManager,
            $mockScopeConfig,
            $mockJsApiValidator,
            $mockRestApiValidator
        );
    }
}
