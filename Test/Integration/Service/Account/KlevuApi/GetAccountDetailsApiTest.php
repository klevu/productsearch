<?php

namespace Klevu\Search\Test\Integration\Service\Account\KlevuApi;

use Klevu\Search\Api\Service\Account\KlevuApi\GetAccountDetailsInterface;
use Klevu\Search\Exception\InvalidApiResponseException;
use Klevu\Search\Exception\MissingApiUrlException;
use Klevu\Search\Service\Account\KlevuApi\GetAccountDetails;
use Klevu\Search\Service\Account\Model\AccountDetails;
use Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Framework\HTTP\ClientFactory as HttpClientFactory;
use \Magento\Framework\HTTP\ClientInterface as HttpClientInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetAccountDetailsApiTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var string
     */
    private $mockRestApiKey = 'klevu-someValidRestApiKey';
    /**
     * @var string
     */
    private $mockJsApiKey = 'klevu-someValidJsApiKey';

    public function testImplementsGetAccountDetailsInterface()
    {
        $this->setUpPhp5();
        $getAccountDetails = $this->objectManager->create(GetAccountDetails::class);

        $this->assertInstanceOf(GetAccountDetailsInterface::class, $getAccountDetails);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/api_url mock.klevu.url
     */
    public function testReturnsArray()
    {
        $this->setUpPhp5();

        $mockHttpClient = $this->getMockBuilder(HttpClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockHttpClient->expects($this->atLeast(2))->method('addHeader');
        $mockHttpClient->expects($this->once())->method('get');
        $mockHttpClient->expects($this->once())->method('getStatus')->willReturn(200);
        $mockHttpClient->expects($this->once())->method('getBody')->willReturn($this->getMockSuccessResponseBody());

        $mockHttpClientFactory = $this->getMockBuilder(HttpClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockHttpClientFactory->method('create')->willReturn($mockHttpClient);

        $getAccountDetails = $this->objectManager->create(GetAccountDetails::class, [
            'httpClientFactory' => $mockHttpClientFactory
        ]);

        $apiKeys = [
            GetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey,
            GetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey
        ];
        $store = $this->getStore();
        $response = $getAccountDetails->execute($apiKeys, $store->getId());

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($response);
        } else {
            $this->assertTrue(is_array($response), 'Is Array');
        }
        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_EMAIL, $response);
        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_PLATFORM, $response);
        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_URL_TIERS, $response);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/api_url mock.klevu.url
     */
    public function testThrowsExceptionIfResponseIs400()
    {
        $this->setUpPhp5();

        $mockHttpClient = $this->getMockBuilder(HttpClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockHttpClient->expects($this->atLeast(2))->method('addHeader');
        $mockHttpClient->expects($this->once())->method('get');
        $mockHttpClient->expects($this->once())->method('getStatus')->willReturn(400);
        $mockHttpClient->expects($this->once())->method('getBody')->willReturn($this->getMock400ErrorResponseBody());

        $mockHttpClientFactory = $this->getMockBuilder(HttpClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockHttpClientFactory->method('create')->willReturn($mockHttpClient);

        $getAccountDetails = $this->objectManager->create(GetAccountDetails::class, [
            'httpClientFactory' => $mockHttpClientFactory
        ]);

        $apiKeys = [
            GetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey,
            GetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey
        ];
        $store = $this->getStore();

        $this->expectException(InvalidApiResponseException::class);
        $this->expectExceptionCode(400);

        $getAccountDetails->execute($apiKeys, $store->getId());

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/api_url mock.klevu.url
     */
    public function testThrowsExceptionIfResponseIs401()
    {
        $this->setUpPhp5();

        $mockHttpClient = $this->getMockBuilder(HttpClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockHttpClient->expects($this->atLeast(2))->method('addHeader');
        $mockHttpClient->expects($this->once())->method('get');
        $mockHttpClient->expects($this->once())->method('getStatus')->willReturn(401);
        $mockHttpClient->expects($this->once())->method('getBody')->willReturn($this->getMock401ErrorResponseBody());

        $mockHttpClientFactory = $this->getMockBuilder(HttpClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockHttpClientFactory->method('create')->willReturn($mockHttpClient);

        $getAccountDetails = $this->objectManager->create(GetAccountDetails::class, [
            'httpClientFactory' => $mockHttpClientFactory
        ]);

        $apiKeys = [
            GetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey,
            GetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey
        ];
        $store = $this->getStore();

        $this->expectException(InvalidApiResponseException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('Invalid Keys. Please Cancel and try again.');

        $getAccountDetails->execute($apiKeys, $store->getId());

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/api_url 0
     */
    public function testThrowsExceptionIfHostnameIsNotSet()
    {
        $this->setUpPhp5();
        $apiKeys = [
            GetAccountDetails::REQUEST_PARAM_JS_API_KEY => $this->mockJsApiKey,
            GetAccountDetails::REQUEST_PARAM_REST_API_KEY => $this->mockRestApiKey
        ];
        $store = $this->getStore();

        $this->expectException(MissingApiUrlException::class);
        $getAccountDetails = $this->objectManager->create(GetAccountDetails::class);
        $getAccountDetails->execute($apiKeys, $store->getId());

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return false|string
     */
    private function getMockSuccessResponseBody()
    {
        return json_encode([
            GetAccountDetails::RESPONSE_SUCCESS_ACTIVE => true,
            GetAccountDetails::RESPONSE_SUCCESS_COMPANY_NAME => 'Klevu',
            GetAccountDetails::RESPONSE_SUCCESS_EMAIL => 'user@klevu.com',
            GetAccountDetails::RESPONSE_SUCCESS_PLATFORM => AccountDetails::PLATFORM_MAGENTO,
            GetAccountDetails::RESPONSE_SUCCESS_URL_ANALYTICS => 'stats.klevu.com',
            GetAccountDetails::RESPONSE_SUCCESS_URL_CAT_NAV => 'box.klevu.com',
            GetAccountDetails::RESPONSE_SUCCESS_URL_INDEXING => "indexing-qa.ksearchnet.com",
            GetAccountDetails::RESPONSE_SUCCESS_URL_JS => 'js.klevu.com',
            GetAccountDetails::RESPONSE_SUCCESS_URL_SEARCH => 'eucsv2.klevu.com',
            GetAccountDetails::RESPONSE_SUCCESS_URL_TIERS => "tiers-qa.klevu.com",
        ]);
    }

    /**
     * @return false|string
     */
    private function getMock400ErrorResponseBody()
    {
        return json_encode([
            'error' => 'ERROR',
            'message' => 'Something went wrong',
            'status' => 400,
        ]);
    }

    /**
     * @return false|string
     */
    private function getMock401ErrorResponseBody()
    {
        return json_encode([
            'error' => 'ERROR',
            'message' => 'Something went wrong',
            'status' => 401,
        ]);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    protected function getStore($storeCode = 'klevu_test_store_1')
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @return void
     * @todo remove once support for PHP 5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }
}
