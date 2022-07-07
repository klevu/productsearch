<?php

namespace Klevu\Search\Test\Integration\Service\Account;

use Klevu\Search\Api\Service\Account\GetAccountDetailsInterface;
use Klevu\Search\Api\Service\Account\KlevuApi\GetAccountDetailsInterface as ApiGetAccountDetailsInterface;
use Klevu\Search\Exception\InactiveApiAccountException;
use Klevu\Search\Exception\IncorrectPlatformException;
use Klevu\Search\Exception\InvalidApiKeyException;
use Klevu\Search\Exception\InvalidApiResponseException;
use Klevu\Search\Service\Account\KlevuApi\GetAccountDetails as ApiGetAccountDetails;
use Klevu\Search\Service\Account\Model\AccountDetails;
use Klevu\Search\Validator\JsApiKeyValidator;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class GetAccountDetailsServiceTest extends TestCase
{
    /**
     * @var string
     */
    private $mockRestApiKey = 'klevu-someValidRestApiKey';
    /**
     * @var string
     */
    private $mockJsApiKey = 'klevu-someValidJsApiKey';

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testReturnsCompanyData()
    {
        $this->setUpPhp5();

        $mockApiResponse = $this->getMockApiResponse();

        $mockApiGetAccountDetails = $this->getMockBuilder(ApiGetAccountDetailsInterface::class)->getMock();
        $mockApiGetAccountDetails->expects($this->once())->method('execute')->willReturn($mockApiResponse);

        $getAccountDetails = $this->objectManager->create(GetAccountDetailsInterface::class, [
            'getAccountDetailsApi' => $mockApiGetAccountDetails
        ]);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];
        $storeId = 1; // store id does not matter here as response is mocked, store id is never used

        $accountDetails = $getAccountDetails->execute($keys, $storeId);

        $this->assertSame('Klevu', $accountDetails->getCompany());
        $this->assertSame('user@klevu.com', $accountDetails->getEmail());
        $this->assertTrue($accountDetails->getActive(), 'Is Actice');
        $this->assertSame(AccountDetails::PLATFORM_MAGENTO, $accountDetails->getPlatform());
        $this->assertSame('stats.klevu.com', $accountDetails->getAnalyticsUrl());
        $this->assertSame('cn26.ksearchnet.com', $accountDetails->getCatNavUrl());
        $this->assertSame('cnstats.ksearchnet.com', $accountDetails->getCatNavTrackingUrl());
        $this->assertSame('indexing-qa.ksearchnet.com', $accountDetails->getIndexingUrl());
        $this->assertSame('js.klevu.com', $accountDetails->getJsUrl());
        $this->assertSame('eucs26v2.ksearchnet.com', $accountDetails->getSearchUrl());
        $this->assertNotEmpty('tiers.klevu.com', $accountDetails->getTiersUrl());
    }

    /**
     * @dataProvider invalidJsApiKeysDataProvider
     */
    public function testThrowsExceptionForIncorrectJsApiKey($jsApiKey)
    {
        $this->setUpPhp5();

        $getAccountDetails = $this->objectManager->get(GetAccountDetailsInterface::class);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $jsApiKey
        ];
        $storeId = 1; // store id does not matter here as exception will be thrown before it is used

        $this->expectException(InvalidApiKeyException::class);
        $this->expectExceptionCode(400);
        $getAccountDetails->execute($keys, $storeId);
    }

    /**
     * @dataProvider invalidRestApiKeysDataProvider
     */
    public function testThrowsExceptionForIncorrectRestApiKey($restApiKey)
    {
        $this->setUpPhp5();

        $getAccountDetails = $this->objectManager->get(GetAccountDetailsInterface::class);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $restApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];
        $storeId = 1; // store id does not matter here as exception will be thrown before it is used

        $this->expectException(InvalidApiKeyException::class);
        $this->expectExceptionCode(400);
        $getAccountDetails->execute($keys, $storeId);
    }

    public function testThrowsExceptionForInactiveAccount()
    {
        $this->setUpPhp5();

        $mockApiResponse = $this->getMockApiResponse();
        $mockApiResponse[ApiGetAccountDetails::RESPONSE_SUCCESS_ACTIVE] = false;

        $mockApiGetAccountDetails = $this->getMockBuilder(ApiGetAccountDetailsInterface::class)->getMock();
        $mockApiGetAccountDetails->expects($this->once())->method('execute')->willReturn($mockApiResponse);

        $getAccountDetails = $this->objectManager->create(GetAccountDetailsInterface::class, [
            'getAccountDetailsApi' => $mockApiGetAccountDetails
        ]);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];
        $storeId = 1; // store id does not matter here as exception will be thrown before it is used

        $this->expectException(InactiveApiAccountException::class);
        $this->expectExceptionMessage('This account is not active.');
        $this->expectExceptionCode(400);

        $getAccountDetails->execute($keys, $storeId);
    }

    public function testThrowsExceptionForOtherPlatforms()
    {
        $this->setUpPhp5();

        $platform = 'shopify';
        $mockApiResponse = $this->getMockApiResponse();
        $mockApiResponse[ApiGetAccountDetails::RESPONSE_SUCCESS_PLATFORM] = $platform;

        $mockApiGetAccountDetails = $this->getMockBuilder(ApiGetAccountDetailsInterface::class)->getMock();
        $mockApiGetAccountDetails->expects($this->once())->method('execute')->willReturn($mockApiResponse);

        $getAccountDetails = $this->objectManager->create(GetAccountDetailsInterface::class, [
            'getAccountDetailsApi' => $mockApiGetAccountDetails
        ]);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];
        $storeId = 1; // store id does not matter here as exception will be thrown before it is used

        $this->expectException(IncorrectPlatformException::class);
        $this->expectExceptionMessage(
            'This account is can not be integrated with Magento. This account is for ' . $platform
        );
        $this->expectExceptionCode(400);

        $getAccountDetails->execute($keys, $storeId);
    }

    /**
     * @dataProvider apiResponseMissingFieldsDataProvider
     */
    public function testThrowsExceptionForMissingData($field)
    {
        $this->setUpPhp5();

        $mockApiResponse = $this->getMockApiResponse();
        $mockApiResponse[$field] = null;

        $mockApiGetAccountDetails = $this->getMockBuilder(ApiGetAccountDetailsInterface::class)->getMock();
        $mockApiGetAccountDetails->expects($this->once())->method('execute')->willReturn($mockApiResponse);

        $getAccountDetails = $this->objectManager->create(GetAccountDetailsInterface::class, [
            'getAccountDetailsApi' => $mockApiGetAccountDetails
        ]);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];
        $storeId = 1; // store id does not matter here as exception will be thrown before it is used

        $this->expectException(InvalidApiResponseException::class);
        $this->expectExceptionCode(404);

        $getAccountDetails->execute($keys, $storeId);
    }

    /**
     * @return array
     */
    private function getMockApiResponse()
    {
        return [
            ApiGetAccountDetails::RESPONSE_SUCCESS_ACTIVE => true,
            ApiGetAccountDetails::RESPONSE_SUCCESS_PLATFORM => AccountDetails::PLATFORM_MAGENTO,
            ApiGetAccountDetails::RESPONSE_SUCCESS_EMAIL => 'user@klevu.com',
            ApiGetAccountDetails::RESPONSE_SUCCESS_COMPANY_NAME => 'Klevu',
            ApiGetAccountDetails::RESPONSE_SUCCESS_URL_ANALYTICS => 'stats.klevu.com',
            ApiGetAccountDetails::RESPONSE_SUCCESS_URL_CAT_NAV => 'cn26.ksearchnet.com',
            ApiGetAccountDetails::RESPONSE_SUCCESS_URL_INDEXING => 'indexing-qa.ksearchnet.com',
            ApiGetAccountDetails::RESPONSE_SUCCESS_URL_JS => 'js.klevu.com',
            ApiGetAccountDetails::RESPONSE_SUCCESS_URL_SEARCH => 'eucs26v2.ksearchnet.com',
            ApiGetAccountDetails::RESPONSE_SUCCESS_URL_TIERS => 'tiers.klevu.com',
        ];
    }

    /**
     * @return array
     */
    public function invalidJsApiKeysDataProvider()
    {
        return [
            [null],
            [0],
            [123],
            ['incorrect-format'],
            [['array is not valid']],
            ['x' . JsApiKeyValidator::JS_API_KEY_BEGINS . 'yz']
        ];
    }

    /**
     * @return array
     */
    public function invalidRestApiKeysDataProvider()
    {
        return [
            [null],
            [0],
            [123],
            ['too-short'],
            ['   too-short   '],
            [['array is not valid']]
        ];
    }

    /**
     * @return array[]
     */
    public function apiResponseMissingFieldsDataProvider()
    {
        return [
            [ApiGetAccountDetails::RESPONSE_SUCCESS_EMAIL],
            [ApiGetAccountDetails::RESPONSE_SUCCESS_URL_ANALYTICS],
            [ApiGetAccountDetails::RESPONSE_SUCCESS_URL_CAT_NAV],
            [ApiGetAccountDetails::RESPONSE_SUCCESS_URL_INDEXING],
            [ApiGetAccountDetails::RESPONSE_SUCCESS_URL_JS],
            [ApiGetAccountDetails::RESPONSE_SUCCESS_URL_SEARCH],
            [ApiGetAccountDetails::RESPONSE_SUCCESS_URL_TIERS],
        ];
    }

    /**
     * @return void
     * @todo remove once support for PHP 5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }
}
