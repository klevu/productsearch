<?php

namespace Klevu\Search\Test\Integration\Service\Account;

use Klevu\Search\Api\Service\Account\ConfirmIntegrationInterface;
use Klevu\Search\Exception\InvalidApiKeyException;
use Klevu\Search\Service\Account\GetAccountDetails;
use Klevu\Search\Service\Account\KlevuApi\GetAccountDetails as ApiGetAccountDetails;
use Klevu\Search\Service\Account\Model\AccountDetails;
use Klevu\Search\Validator\JsApiKeyValidator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class ConfirmIntegrationServiceTest extends TestCase
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

    public function testExecuteCanBeCalled()
    {
        $this->setUpPhp5();

        $mockGetApiAccountDetails = $this->getMockBuilder(ApiGetAccountDetails::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGetApiAccountDetails->expects($this->once())
            ->method('execute')
            ->willReturn($this->getMockApiResponse());

        $getAccountDetails = $this->objectManager->create(GetAccountDetails::class, [
            'getAccountDetailsApi' => $mockGetApiAccountDetails
        ]);

        $confirmIntegration = $this->objectManager->create(ConfirmIntegrationInterface::class, [
            'getAccountDetails' => $getAccountDetails
        ]);
        $apiKeys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];
        $storeId = 1;

        $confirmIntegration->execute($apiKeys, $storeId);
    }

    /**
     * @dataProvider invalidJsApiKeys
     */
    public function testCheckThrowsExceptionForIncorrectJsAPiKey($jsApiKey)
    {
        $this->setUpPhp5();
        $this->expectException(InvalidApiKeyException::class);
        $confirmIntegration = $this->objectManager->get(ConfirmIntegrationInterface::class);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $jsApiKey
        ];
        $storeId = 1;

        $confirmIntegration->execute($keys, $storeId);
    }

    /**
     * @dataProvider invalidRestApiKeys
     */
    public function testCheckThrowsExceptionForIncorrectRestAPiKey($restApiKey)
    {
        $this->setUpPhp5();
        $this->expectException(InvalidApiKeyException::class);
        $confirmIntegration = $this->objectManager->get(ConfirmIntegrationInterface::class);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $restApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];
        $storeId = 1;

        $confirmIntegration->execute($keys, $storeId);
    }

    public function testTrowsExceptionWhenStoreNotFound()
    {
        $this->setUpPhp5();
        $this->expectException(NoSuchEntityException::class);
        $confirmIntegration = $this->objectManager->get(ConfirmIntegrationInterface::class);
        $keys = [
            ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey,
            ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey
        ];
        $storeId = 3985395893;

        $confirmIntegration->execute($keys, $storeId);
    }

    /**
     * @return array
     */
    public function invalidJsApiKeys()
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
    public function invalidRestApiKeys()
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
     * @return void
     * @todo remove once support for PHP 5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }
}
