<?php

namespace Klevu\Search\Test\Integration\Helper;

use Klevu\Search\Helper\Api as ApiHelper;
use Klevu\Search\Helper\Config;
use Klevu\Search\Helper\VersionReader;
use Klevu\Search\Model\Api\Request;
use Klevu\Search\Model\Api\Response\Data as ResponseModel;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var VersionReader
     */
    private $versionReader;

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/general/locale/code en_GB
     * @magentoConfigFixture default_store general/locale/code en_GB
     * @magentoConfigFixture klevu_test_store_1_store general/locale/code en_GB
     * @magentoConfigFixture default/general/locale/timezone Europe/London
     * @magentoConfigFixture default_store general/locale/timezone Europe/London
     * @magentoConfigFixture klevu_test_store_1_store general/locale/timezone Europe/London
     * @magentoConfigFixture default/general/country/default GB
     * @magentoConfigFixture default_store general/country/default GB
     * @magentoConfigFixture klevu_test_store_1_store general/country/default GB
     * @magentoConfigFixture default/klevu_search/developer/theme_version v1
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/theme_version v1
     * @magentoConfigFixture default_store klevu_search/general/hostname box.klevu.com
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testCreateWebstore_ThemeV1()
    {
        $this->setupPhp5();

        $customerId = 42;
        $store = $this->storeManager->getStore('klevu_test_store_1');
        $storeName = '[Klevu] Test Website 1 - klevu_test_store_1 - [Klevu] Test Store 1 - ' . $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);

        $requestParameters = [
            'customerId' => $customerId,
            'storeName' => $storeName,
            'language' => 'en',
            'timezone' => 'Europe/London',
            'version' => $this->versionReader->getVersionString('Klevu_Search'),
            'country' => 'GB',
            'locale' => 'en_GB',
            'testMode' => false,
            'jsVersion' => 'v1',
        ];
        $responseXml = "<data>"
            . "<response>success</response>"
            . "<jsApiKey>klevu-1234567890</jsApiKey>"
            . "<restApiKey>ABCDE12345</restApiKey>"
            . "<tiersUrl>tiers.klevu.com</tiersUrl>"
            . "<hostedOn>box.klevu.com</hostedOn>"
            . "<cloudSearchUrl>eucs999.ksearchnet.com</cloudSearchUrl>"
            . "<cloudSearchUrlAPIv2>eucs999v2.ksearchnet.com</cloudSearchUrlAPIv2>"
            . "<restUrl>rest2.ksearchnet.com</restUrl>"
            . "<analyticsUrl>stats.ksearchnet.com</analyticsUrl>"
            . "<jsUrl>js.klevu.com</jsUrl>"
            . "<message>" . $storeName . " has been successfully configured on Klevu.</message>"
        . "</data>";

        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $hostname = $scopeConfig->getValue(Config::XML_PATH_HOSTNAME, ScopeInterface::SCOPE_STORES, 1);

        $apiRequestMock = $this->getApiRequestMock(
            'https://' . $hostname . '/n-search/addWebstore',
            'POST',
            $requestParameters,
            true,
            $responseXml
        );

        $this->objectManager->addSharedInstance($apiRequestMock, \Klevu\Search\Model\Api\Request::class);
        $this->objectManager->addSharedInstance($apiRequestMock, \Klevu\Search\Model\Api\Request\Post::class);

        /** @var ApiHelper $apiHelper */
        $apiHelper = $this->objectManager->get(ApiHelper::class);

        /** @noinspection PhpParamsInspection */
        $actualResult = $apiHelper->createWebstore($customerId, $store);
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($actualResult);
        } else {
            $this->assertTrue(is_array($actualResult), 'Result is array');
        }
        $this->assertSame(['success', 'webstore', 'message'], array_keys($actualResult), 'Result contains expected keys');
        $this->assertTrue($actualResult['success']);
        $this->assertInstanceOf(DataObject::class, $actualResult['webstore']);
        $this->assertSame(
            '[Klevu] Test Website 1 - klevu_test_store_1 - [Klevu] Test Store 1 - ' . $store->getBaseUrl(UrlInterface::URL_TYPE_WEB),
            $actualResult['webstore']->getData('store_name')
        );
        $this->assertSame('klevu-1234567890', $actualResult['webstore']->getData('js_api_key'));
        $this->assertSame('ABCDE12345', $actualResult['webstore']->getData('rest_api_key'));
        $this->assertFalse($actualResult['webstore']->getData('test_account_enabled'), 'Test Account Enabled');
        $this->assertSame('box.klevu.com', $actualResult['webstore']->getData('hosted_on'));
        $this->assertSame('eucs999.ksearchnet.com', $actualResult['webstore']->getData('cloud_search_url'));
        $this->assertSame('eucs999v2.ksearchnet.com', $actualResult['webstore']->getData('cloud_search_v2_url'));
        $this->assertSame('stats.ksearchnet.com', $actualResult['webstore']->getData('analytics_url'));
        $this->assertSame('js.klevu.com', $actualResult['webstore']->getData('js_url'));
        $this->assertSame('rest2.ksearchnet.com', $actualResult['webstore']->getData('rest_hostname'));
        $this->assertSame('tiers.klevu.com', $actualResult['webstore']->getData('tires_url')); // Note: known typo
        $this->assertSame($storeName . " has been successfully configured on Klevu.", $actualResult['message']);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/general/locale/code en_GB
     * @magentoConfigFixture default_store general/locale/code en_GB
     * @magentoConfigFixture klevu_test_store_1_store general/locale/code en_GB
     * @magentoConfigFixture default/general/locale/timezone Europe/London
     * @magentoConfigFixture default_store general/locale/timezone Europe/London
     * @magentoConfigFixture klevu_test_store_1_store general/locale/timezone Europe/London
     * @magentoConfigFixture default/general/country/default GB
     * @magentoConfigFixture default_store general/country/default GB
     * @magentoConfigFixture klevu_test_store_1_store general/country/default GB
     * @magentoConfigFixture default/klevu_search/developer/theme_version v1
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/general/hostname box.klevu.com
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testCreateWebstore_ThemeV2()
    {
        $this->setupPhp5();

        $customerId = 42;
        $store = $this->storeManager->getStore('klevu_test_store_1');
        $storeName = '[Klevu] Test Website 1 - klevu_test_store_1 - [Klevu] Test Store 1 - ' . $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);

        $requestParameters = [
            'customerId' => $customerId,
            'storeName' => $storeName,
            'language' => 'en',
            'timezone' => 'Europe/London',
            'version' => $this->versionReader->getVersionString('Klevu_Search'),
            'country' => 'GB',
            'locale' => 'en_GB',
            'testMode' => false,
            'jsVersion' => 'v2',
        ];
        $responseXml = "<data>"
            . "<response>success</response>"
            . "<jsApiKey>klevu-1234567890</jsApiKey>"
            . "<restApiKey>ABCDE12345</restApiKey>"
            . "<tiersUrl>tiers.klevu.com</tiersUrl>"
            . "<hostedOn>box.klevu.com</hostedOn>"
            . "<cloudSearchUrl>eucs999.ksearchnet.com</cloudSearchUrl>"
            . "<cloudSearchUrlAPIv2>eucs999v2.ksearchnet.com</cloudSearchUrlAPIv2>"
            . "<restUrl>rest2.ksearchnet.com</restUrl>"
            . "<analyticsUrl>stats.ksearchnet.com</analyticsUrl>"
            . "<jsUrl>js.klevu.com</jsUrl>"
            . "<message>" . $storeName . " has been successfully configured on Klevu.</message>"
            . "</data>";

        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $hostname = $scopeConfig->getValue(Config::XML_PATH_HOSTNAME, ScopeInterface::SCOPE_STORES, 1);

        $apiRequestMock = $this->getApiRequestMock(
            'https://' . $hostname . '/n-search/addWebstore',
            'POST',
            $requestParameters,
            true,
            $responseXml
        );

        $this->objectManager->addSharedInstance($apiRequestMock, \Klevu\Search\Model\Api\Request::class);
        $this->objectManager->addSharedInstance($apiRequestMock, \Klevu\Search\Model\Api\Request\Post::class);

        /** @var ApiHelper $apiHelper */
        $apiHelper = $this->objectManager->get(ApiHelper::class);

        /** @noinspection PhpParamsInspection */
        $actualResult = $apiHelper->createWebstore($customerId, $store);
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($actualResult);
        } else {
            $this->assertTrue(is_array($actualResult), 'Result is array');
        }
        $this->assertSame(['success', 'webstore', 'message'], array_keys($actualResult), 'Result contains expected keys');
        $this->assertTrue($actualResult['success']);
        $this->assertInstanceOf(DataObject::class, $actualResult['webstore']);
        $this->assertSame(
            '[Klevu] Test Website 1 - klevu_test_store_1 - [Klevu] Test Store 1 - ' . $store->getBaseUrl(UrlInterface::URL_TYPE_WEB),
            $actualResult['webstore']->getData('store_name')
        );
        $this->assertSame('klevu-1234567890', $actualResult['webstore']->getData('js_api_key'));
        $this->assertSame('ABCDE12345', $actualResult['webstore']->getData('rest_api_key'));
        $this->assertFalse($actualResult['webstore']->getData('test_account_enabled'), 'Test Account Enabled');
        $this->assertSame('box.klevu.com', $actualResult['webstore']->getData('hosted_on'));
        $this->assertSame('eucs999.ksearchnet.com', $actualResult['webstore']->getData('cloud_search_url'));
        $this->assertSame('eucs999v2.ksearchnet.com', $actualResult['webstore']->getData('cloud_search_v2_url'));
        $this->assertSame('stats.ksearchnet.com', $actualResult['webstore']->getData('analytics_url'));
        $this->assertSame('js.klevu.com', $actualResult['webstore']->getData('js_url'));
        $this->assertSame('rest2.ksearchnet.com', $actualResult['webstore']->getData('rest_hostname'));
        $this->assertSame('tiers.klevu.com', $actualResult['webstore']->getData('tires_url')); // Note: known typo
        $this->assertSame($storeName . " has been successfully configured on Klevu.", $actualResult['message']);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->versionReader = $this->objectManager->get(VersionReader::class);
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $parameters
     * @param bool $success
     * @param string $responseXml
     * @return Request|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getApiRequestMock($endpoint, $method, $parameters, $success, $responseXml)
    {
        if (!method_exists($this, 'createMock')) {
            return $this->getApiRequestMockLegacy($endpoint, $method, $parameters, $success, $responseXml);
        }

        $apiRequestMock = $this->createMock(Request::class);
        $apiRequestMock->method('setResponseModel')->willReturnSelf();
        $apiRequestMock->expects($this->once())
            ->method('setEndpoint')
            ->with($endpoint)
            ->willReturnSelf();
        $apiRequestMock->expects($this->once())
            ->method('setMethod')
            ->with($method)
            ->willReturnSelf();
        $apiRequestMock->expects($this->once())
            ->method('setData')
            ->with($parameters)
            ->willReturnSelf();

        $apiRequestMock->expects($this->once())
            ->method('send')
            ->willReturn(
                $this->getApiResponseMock($success, $responseXml)
            );

        return $apiRequestMock;
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $parameters
     * @param bool $success
     * @param string $responseXml
     * @return Request|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getApiRequestMockLegacy($endpoint, $method, $parameters, $success, $responseXml)
    {
        $apiRequestMock = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiRequestMock->expects($this->any())
            ->method('setResponseModel')
            ->willReturnSelf();
        $apiRequestMock->expects($this->once())
            ->method('setEndpoint')
            ->with($endpoint)
            ->willReturnSelf();
        $apiRequestMock->expects($this->once())
            ->method('setMethod')
            ->with($method)
            ->willReturnSelf();
        $apiRequestMock->expects($this->once())
            ->method('setData')
            ->with($parameters)
            ->willReturnSelf();

        $apiRequestMock->expects($this->once())
            ->method('send')
            ->willReturn(
                $this->getApiResponseMock($success, $responseXml)
            );

        return $apiRequestMock;
    }

    /**
     * @param bool $success
     * @param string $responseXml
     * @return ResponseModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getApiResponseMock($success, $responseXml)
    {
        if (method_exists($this, 'createMock')) {
            $zendResponseMock = $this->createMock(\Laminas\Http\Response::class);
            $zendResponseMock->method('isSuccess')->willReturn($success);
            $zendResponseMock->method('getBody')->willReturn($responseXml);
        } else {
            $zendResponseMock = $this->getMockBuilder(\Laminas\Http\Response::class)
                ->disableOriginalConstructor()
                ->getMock();
            $zendResponseMock->expects($this->any())
                ->method('isSuccess')
                ->willReturn($success);
            $zendResponseMock->expects($this->any())
                ->method('getBody')
                ->willReturn($responseXml);
        }

        /** @var ResponseModel $apiResponseMock */
        $apiResponseMock = $this->objectManager->create(ResponseModel::class);
        $apiResponseMock->setRawResponse($zendResponseMock);

        return $apiResponseMock;
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../_files/websiteFixtures_rollback.php';
    }
}
