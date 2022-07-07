<?php

namespace Klevu\Search\Test\Integration\Service\Account;

use Klevu\Search\Api\Service\Account\GetKmcUrlServiceInterface;
use Klevu\Search\Service\Account\GetKmcUrlService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation disabled
 * @magentoDataFixture loadWebsiteFixtures
 */
class GetKmcUrlServiceTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;
    /**
     * @var RequestInterface|MockObject
     */
    private $mockRequest;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();
        $getKmcUrlService = $this->instantiateGetKmcUrlService();

        $this->assertInstanceOf(GetKmcUrlService::class, $getKmcUrlService);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/hostname store.klevu.com
     * @magentoConfigFixture klevu_test_website_1_website klevu_search/general/hostname website.klevu.com
     * @magentoConfigFixture default/klevu_search/general/hostname default.klevu.com
     */
    public function testExecuteReturnsStoreUrlAtStoreScope()
    {
        $this->setUpPhp5();

        $store = $this->getStore();
        $website = $this->getWebsite();
        $this->mockRequest->method('getParam')->willReturnMap([
            ['store', null, $store->getId()],
            ['website', null, $website->getId()]
        ]);

        $getKmcUrlService = $this->instantiateGetKmcUrlService();
        $kmcUrl = $getKmcUrlService->execute();

        if (method_exists($this, 'assertIsString')) {
            $this->assertIsString($kmcUrl);
        } else {
            $this->assertTrue(is_string($kmcUrl), 'Is String');
        }
        $this->assertSame('https://store.klevu.com', $kmcUrl);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/hostname store.klevu.com
     * @magentoConfigFixture klevu_test_website_1_website klevu_search/general/hostname website.klevu.com
     * @magentoConfigFixture default/klevu_search/general/hostname default.klevu.com
     */
    public function testExecuteReturnsWebsiteUrlAtWebsiteScope()
    {
        $this->setUpPhp5();
        if (version_compare($this->getMagentoVersion(), '2.4.0', '<')) {
            $this->markTestSkipped();
        }
        $website = $this->getWebsite();
        $this->mockRequest->method('getParam')->willReturnMap([
            ['store', null, null],
            ['website', null, $website->getId()]
        ]);

        $getKmcUrlService = $this->instantiateGetKmcUrlService();
        $kmcUrl = $getKmcUrlService->execute();

        if (method_exists($this, 'assertIsString')) {
            $this->assertIsString($kmcUrl);
        } else {
            $this->assertTrue(is_string($kmcUrl), 'Is String');
        }
        $this->assertSame('https://website.klevu.com', $kmcUrl);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/hostname store.klevu.com
     * @magentoConfigFixture klevu_test_website_1_website klevu_search/general/hostname website.klevu.com
     * @magentoConfigFixture default/klevu_search/general/hostname default.klevu.com
     */
    public function testExecuteReturnsDefaultUrlAtDefaultScope()
    {
        $this->setUpPhp5();

        $this->mockRequest->method('getParam')->willReturnMap([
            ['store', null, null],
            ['website', null, null]
        ]);

        $getKmcUrlService = $this->instantiateGetKmcUrlService();
        $kmcUrl = $getKmcUrlService->execute();

        if (method_exists($this, 'assertIsString')) {
            $this->assertIsString($kmcUrl);
        } else {
            $this->assertTrue(is_string($kmcUrl), 'Is String');
        }
        $this->assertSame('https://default.klevu.com', $kmcUrl);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoConfigFixture klevu_test_website_1_website klevu_search/general/hostname website.klevu.com
     * @magentoConfigFixture default/klevu_search/general/hostname default.klevu.com
     */
    public function testExecuteReturnsDefaultUrlAtStoreScopeIfNotSet()
    {
        $this->setUpPhp5();

        $store = $this->getStore();
        $website = $this->getWebsite();
        $this->mockRequest->method('getParam')->willReturnMap([
            ['store', null, $store->getId()],
            ['website', null, $website->getId()]
        ]);

        $getKmcUrlService = $this->instantiateGetKmcUrlService();
        $kmcUrl = $getKmcUrlService->execute();

        if (method_exists($this, 'assertIsString')) {
            $this->assertIsString($kmcUrl);
        } else {
            $this->assertTrue(is_string($kmcUrl), 'Is String');
        }
        $this->assertSame('https://' . GetKmcUrlService::KLEVU_MERCHANT_CENTER_URL_DEFAULT, $kmcUrl);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/hostname store.klevu.com
     * @magentoConfigFixture default/klevu_search/general/hostname default.klevu.com
     */
    public function testExecuteReturnsDefaultUrlAtWebsiteScopeIfNotSet()
    {
        $this->setUpPhp5();

        $website = $this->getWebsite();
        $this->mockRequest->method('getParam')->willReturnMap([
            ['store', null, null],
            ['website', null, $website->getId()]
        ]);

        $getKmcUrlService = $this->instantiateGetKmcUrlService();
        $kmcUrl = $getKmcUrlService->execute();

        if (method_exists($this, 'assertIsString')) {
            $this->assertIsString($kmcUrl);
        } else {
            $this->assertTrue(is_string($kmcUrl), 'Is String');
        }
        $this->assertSame('https://' . GetKmcUrlService::KLEVU_MERCHANT_CENTER_URL_DEFAULT, $kmcUrl);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @dataProvider incorrectScopeCodesDataProvider
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/hostname store.klevu.com
     * @magentoConfigFixture klevu_test_website_1_website klevu_search/general/hostname website.klevu.com
     * @magentoConfigFixture default/klevu_search/general/hostname default.klevu.com
     */
    public function testExecuteReturnsConstantIfWrongScopeSupplied($storeId, $websiteId)
    {
        $this->setUpPhp5();

        $this->mockRequest->method('getParam')->willReturnMap([
            ['store', null, $storeId],
            ['website', null, $websiteId]
        ]);

        $getKmcUrlService = $this->instantiateGetKmcUrlService();
        $kmcUrl = $getKmcUrlService->execute();

        if (method_exists($this, 'assertIsString')) {
            $this->assertIsString($kmcUrl);
        } else {
            $this->assertTrue(is_string($kmcUrl), 'Is String');
        }
        $this->assertSame('https://' . GetKmcUrlService::KLEVU_MERCHANT_CENTER_URL_DEFAULT, $kmcUrl);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return array[]
     */
    public function incorrectScopeCodesDataProvider()
    {
        return[
            ['325093059305', null],
            [null, '947589824789275'],
            [-10, null],
            [null, -10]
        ];
    }

    /**
     * @return GetKmcUrlServiceInterface
     */
    private function instantiateGetKmcUrlService()
    {
        return $this->objectManager->create(GetKmcUrlServiceInterface::class, [
            'scopeConfig' => $this->getScopeConfig(),
            'request' => $this->mockRequest
        ]);
    }

    /**
     * @return ScopeConfigInterface
     */
    private function getScopeConfig()
    {
        return $this->objectManager->get(ScopeConfigInterface::class);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode = 'klevu_test_store_1')
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @param string $websiteCode
     *
     * @return WebsiteInterface
     * @throws NoSuchEntityException
     */
    private function getWebsite($websiteCode = 'klevu_test_website_1')
    {
        /** @var WebsiteRepositoryInterface $websiteRepository */
        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);

        return $websiteRepository->get($websiteCode);
    }

    /**
     * @return string
     */
    private function getMagentoVersion()
    {
        /** @var ProductMetadataInterface $metaData */
        $metaData = $this->objectManager->get(ProductMetadataInterface::class);

        return $metaData->getVersion();
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->mockRequest = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }
}
