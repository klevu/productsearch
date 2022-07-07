<?php

namespace Klevu\Search\Test\Integration\Service\Account;

use Klevu\Search\Api\SerializerInterface;
use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;
use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterfaceFactory as AccountFeaturesFactory;
use Klevu\Search\Api\Service\Account\GetFeaturesInterface;
use Klevu\Search\Model\Api\Action\Features as FeaturesApi;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Service\Account\GetFeatures;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @magentoDbIsolation disabled
 */
class GetAccountFeaturesServiceTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var FeaturesApi|MockObject
     */
    private $mockFeaturesApi;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $mockScopeConfig;
    /**
     * @var LoggerInterface|MockObject
     */
    private $mockLogger;
    /**
     * @var AccountFeaturesFactory|MockObject
     */
    private $accountFeaturesFactory;
    /**
     * @var RequestInterface|MockObject
     */
    private $mockRequest;
    /**
     * @var string[]
     */
    private $mockApiReturnDataArray = [
        'upgrade_url' => 'https://box.klevu.com/analytics/km',
        'upgrade_message' => 'UPGRADE MESSAGE',
        'preserve_layout_message' => 'PRESERVE LAYOUT MESSAGE',
        'enabled' => 'enabledaddtocartfront,boosting,enabledcmsfront,enabledcategorynavigation,allowgroupprices',
        'disabled' => 'enabledpopulartermfront,preserves_layout',
        'user_plan_for_store' => 'Enterprise',
        'response' => 'success'
    ];
    /**
     * @var ReinitableConfigInterface|MockObject
     */
    private $mockReinitableConfig;

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @dataProvider invalidRestApiKeysDataProvider
     */
    public function testLogsErrorAndReturnsEmptyFeaturesModelIfRestApiNotValid($restApiKey)
    {
        $this->setupPhp5();
        $store = $this->getStore();

        $this->mockRequest->expects($this->never())->method('getParam');
        $this->mockFeaturesApi->expects($this->never())->method('execute');
        $this->mockScopeConfig->method('getValue')->willReturn($restApiKey);
        $this->mockLogger->expects($this->once())->method('error');

        $accountFeaturesService = $this->instantiateGetFeatures();
        $accountFeatures = $accountFeaturesService->execute($store);

        $this->assertIsEmptyFeaturesModel($accountFeatures);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @dataProvider invalidStoresDataProvider
     */
    public function testLogsErrorAndReturnsEmptyFeaturesModelIfStoreNotValid($store)
    {
        $this->setupPhp5();

        $this->mockFeaturesApi->expects($this->never())->method('execute');
        $this->mockScopeConfig->method('getValue')->willReturn('someValidRestApiKey');
        $this->mockLogger->expects($this->once())->method('error');
        $this->mockReinitableConfig->expects($this->never())->method('reinit');

        $accountFeaturesService = $this->instantiateGetFeatures();
        $accountFeatures = $accountFeaturesService->execute($store);

        $this->assertIsEmptyFeaturesModel($accountFeatures);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testStoreIsLoadedFromRequestParamIfNotProvided()
    {
        $this->setupPhp5();
        $store = $this->getStore();
        $this->mockRequest->expects($this->once())->method('getParam')->willReturn($store->getId());

        $mockResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $mockResponse->expects($this->once())->method('isSuccess')->willReturn(true);
        $mockResponse->expects($this->atLeastOnce())->method('getData')->willReturn($this->mockApiReturnDataArray);
        $this->mockFeaturesApi->expects($this->atLeastOnce())->method('execute')->willReturn($mockResponse);
        $this->mockReinitableConfig->expects($this->once())->method('reinit');

        $this->mockScopeConfig->method('getValue')->willReturnCallback(function ($field) {
            if ($field === GetFeatures::XML_PATH_REST_API_KEY) {
                return 'someValidRestApiKey';
            }
            if ($field === GetFeatures::XML_PATH_UPGRADE_FEATURES) {
                $serializer = ObjectManager::getInstance()->get(SerializerInterface::class);
                return $serializer->serialize($this->mockApiReturnDataArray);
            }

            return null;
        });
        $accountFeaturesService = $this->instantiateGetFeatures();
        $accountFeatures = $accountFeaturesService->execute();

        $this->assertInstanceOf(AccountFeaturesInterface::class, $accountFeatures);
        $this->assertSame('https://box.klevu.com/analytics/km', $accountFeatures->getUpgradeUrl());
        $this->assertTrue(
            $accountFeatures->isFeatureAvailable('enabledaddtocartfront'),
            'Feature is available enabledaddtocartfront'
        );
        $this->assertFalse(
            $accountFeatures->isFeatureAvailable('preserves_layout'),
            'Feature is available preserves_layout'
        );
        $this->assertSame('PRESERVE LAYOUT MESSAGE', $accountFeatures->getPreserveLayoutMessage());

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetAccountFeaturesReturnsFeaturesModelWithDataOnSuccess()
    {
        $this->setupPhp5();
        $store = $this->getStore();

        $mockResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $mockResponse->expects($this->once())->method('isSuccess')->willReturn(true);
        $mockResponse->expects($this->atLeastOnce())->method('getData')->willReturn($this->mockApiReturnDataArray);
        $this->mockFeaturesApi->expects($this->atLeastOnce())->method('execute')->willReturn($mockResponse);
        $this->mockReinitableConfig->expects($this->once())->method('reinit');

        $this->mockScopeConfig->method('getValue')->willReturnCallback(function ($field) {
            if ($field === GetFeatures::XML_PATH_REST_API_KEY) {
                return 'someValidRestApiKey';
            }
            if ($field === GetFeatures::XML_PATH_UPGRADE_FEATURES) {
                return null;
            }

            return null;
        });
        $accountFeaturesService = $this->instantiateGetFeatures();
        $accountFeatures = $accountFeaturesService->execute($store->getId());

        $this->assertInstanceOf(AccountFeaturesInterface::class, $accountFeatures);
        $this->assertSame('https://box.klevu.com/analytics/km', $accountFeatures->getUpgradeUrl());
        $this->assertTrue(
            $accountFeatures->isFeatureAvailable('enabledaddtocartfront'),
            'Feature is available enabledaddtocartfront'
        );
        $this->assertFalse(
            $accountFeatures->isFeatureAvailable('preserves_layout'),
            'Feature is available preserves_layout'
        );
        $this->assertSame('PRESERVE LAYOUT MESSAGE', $accountFeatures->getPreserveLayoutMessage());

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetAccountFeaturesReturnsFeaturesModelWithoutDataOnFailureIfNotPreviouslySaved()
    {
        $this->setupPhp5();
        $store = $this->getStore();

        $mockResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $mockResponse->expects($this->once())->method('isSuccess')->willReturn(false);
        $this->mockFeaturesApi->expects($this->atLeastOnce())->method('execute')->willReturn($mockResponse);
        $this->mockReinitableConfig->expects($this->never())->method('reinit');

        $this->mockScopeConfig->method('getValue')->willReturnCallback(function ($field) {
            if ($field === GetFeatures::XML_PATH_REST_API_KEY) {
                return 'someValidRestApiKey';
            }
            if ($field === GetFeatures::XML_PATH_UPGRADE_FEATURES) {
                return null;
            }

            return null;
        });
        $accountFeaturesService = $this->instantiateGetFeatures();
        $accountFeatures = $accountFeaturesService->execute($store->getId());

        $this->assertIsEmptyFeaturesModel($accountFeatures);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetAccountFeaturesReturnsFeaturesModelWithDataOnFailureIfPreviouslySaved()
    {
        $this->setupPhp5();
        $store = $this->getStore();

        $mockResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $mockResponse->expects($this->once())->method('isSuccess')->willReturn(false);
        $this->mockFeaturesApi->expects($this->atLeastOnce())->method('execute')->willReturn($mockResponse);
        $this->mockReinitableConfig->expects($this->never())->method('reinit');

        $this->mockScopeConfig->method('getValue')->willReturnCallback(function ($field) {
            if ($field === GetFeatures::XML_PATH_REST_API_KEY) {
                return 'someValidRestApiKey';
            }
            if ($field === GetFeatures::XML_PATH_UPGRADE_FEATURES) {
                $serializer = ObjectManager::getInstance()->get(SerializerInterface::class);
                return $serializer->serialize($this->mockApiReturnDataArray);
            }

            return null;
        });

        $accountFeaturesService = $this->instantiateGetFeatures();
        $accountFeatures = $accountFeaturesService->execute($store->getId());

        $this->assertInstanceOf(AccountFeaturesInterface::class, $accountFeatures);
        $this->assertSame('https://box.klevu.com/analytics/km', $accountFeatures->getUpgradeUrl());
        $this->assertTrue(
            $accountFeatures->isFeatureAvailable('enabledaddtocartfront'),
            'Feature is available enabledaddtocartfront'
        );
        $this->assertFalse(
            $accountFeatures->isFeatureAvailable('preserves_layout'),
            'Feature is available preserves_layout'
        );
        $this->assertSame('PRESERVE LAYOUT MESSAGE', $accountFeatures->getPreserveLayoutMessage());

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetAccountFeaturesReturnsFeaturesModelWithDataOnExceptionIfPreviouslySaved()
    {
        $this->setupPhp5();
        $store = $this->getStore();

        $this->mockFeaturesApi->expects($this->atLeastOnce())
            ->method('execute')
            ->willThrowException(new LocalizedException(__('A test exception is thrown here')));

        $this->mockLogger->expects($this->once())->method('error');
        $this->mockReinitableConfig->expects($this->once())->method('reinit');

        $this->mockScopeConfig->method('getValue')->willReturnCallback(function ($field) {
            if ($field === GetFeatures::XML_PATH_REST_API_KEY) {
                return 'someValidRestApiKey';
            }
            if ($field === GetFeatures::XML_PATH_UPGRADE_FEATURES) {
                $serializer = ObjectManager::getInstance()->get(SerializerInterface::class);
                return $serializer->serialize($this->mockApiReturnDataArray);
            }

            return null;
        });

        $accountFeaturesService = $this->instantiateGetFeatures();
        $accountFeatures = $accountFeaturesService->execute($store->getId());

        $this->assertInstanceOf(AccountFeaturesInterface::class, $accountFeatures);
        $this->assertSame('https://box.klevu.com/analytics/km', $accountFeatures->getUpgradeUrl());
        $this->assertTrue(
            $accountFeatures->isFeatureAvailable('enabledaddtocartfront'),
            'Feature is available enabledaddtocartfront'
        );
        $this->assertFalse(
            $accountFeatures->isFeatureAvailable('preserves_layout'),
            'Feature is available preserves_layout'
        );
        $this->assertSame('PRESERVE LAYOUT MESSAGE', $accountFeatures->getPreserveLayoutMessage());

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testApiIsNotCalledIfLastSyncDatIsLessThanNumberOfHoursSet()
    {
        $this->setupPhp5();
        $store = $this->getStore();

        $this->mockFeaturesApi->expects($this->never())->method('execute');

        $this->mockLogger->expects($this->never())->method('error');
        $this->mockReinitableConfig->expects($this->never())->method('reinit');

        $this->mockScopeConfig->method('getValue')->willReturnCallback(function ($field) {
            if ($field === GetFeatures::XML_PATH_REST_API_KEY) {
                return 'someValidRestApiKey';
            }
            if ($field === GetFeatures::XML_PATH_UPGRADE_FEATURES) {
                $serializer = ObjectManager::getInstance()->get(SerializerInterface::class);
                return $serializer->serialize($this->mockApiReturnDataArray);
            }
            if ($field === GetFeatures::XML_PATH_FEATURES_LAST_SYNC_DATE) {
                return time() - (60 * 60 * (GetFeatures::API_DATA_SYNC_REQUIRED_EVERY_HOURS - 1));
            }

            return null;
        });

        $accountFeaturesService = $this->instantiateGetFeatures();
        $accountFeatures = $accountFeaturesService->execute($store->getId());

        $this->assertInstanceOf(AccountFeaturesInterface::class, $accountFeatures);
        $this->assertSame('https://box.klevu.com/analytics/km', $accountFeatures->getUpgradeUrl());
        $this->assertTrue(
            $accountFeatures->isFeatureAvailable('enabledaddtocartfront'),
            'Feature is available enabledaddtocartfront'
        );
        $this->assertFalse(
            $accountFeatures->isFeatureAvailable('preserves_layout'),
            'Feature is available preserves_layout'
        );
        $this->assertSame('PRESERVE LAYOUT MESSAGE', $accountFeatures->getPreserveLayoutMessage());

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testApiIsCalledIfLastSyncDatIsMoreThanNumberOfHoursSet()
    {
        $this->setupPhp5();
        $store = $this->getStore();

        $mockResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $mockResponse->expects($this->atLeastOnce())->method('isSuccess')->willReturn(true);
        $mockResponse->expects($this->atLeastOnce())->method('getData')->willReturn($this->mockApiReturnDataArray);

        $this->mockFeaturesApi->expects($this->atLeastOnce())->method('execute')->willReturn($mockResponse);
        $this->mockReinitableConfig->expects($this->once())->method('reinit');

        $this->mockScopeConfig->method('getValue')->willReturnCallback(function ($field) {
            if ($field === GetFeatures::XML_PATH_REST_API_KEY) {
                return 'someValidRestApiKey';
            }
            if ($field === GetFeatures::XML_PATH_UPGRADE_FEATURES) {
                $serializer = ObjectManager::getInstance()->get(SerializerInterface::class);
                return $serializer->serialize($this->mockApiReturnDataArray);
            }
            if ($field === GetFeatures::XML_PATH_FEATURES_LAST_SYNC_DATE) {
                return time() - (60 * 60 * (GetFeatures::API_DATA_SYNC_REQUIRED_EVERY_HOURS + 1));
            }

            return null;
        });

        $accountFeaturesService = $this->instantiateGetFeatures();
        $accountFeatures = $accountFeaturesService->execute($store->getId());

        $this->assertInstanceOf(AccountFeaturesInterface::class, $accountFeatures);
        $this->assertSame('https://box.klevu.com/analytics/km', $accountFeatures->getUpgradeUrl());
        $this->assertTrue(
            $accountFeatures->isFeatureAvailable('enabledaddtocartfront'),
            'Feature is available enabledaddtocartfront'
        );
        $this->assertFalse(
            $accountFeatures->isFeatureAvailable('preserves_layout'),
            'Feature is available preserves_layout'
        );
        $this->assertSame('PRESERVE LAYOUT MESSAGE', $accountFeatures->getPreserveLayoutMessage());

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return array
     */
    public function invalidRestApiKeysDataProvider()
    {
        return [
            [0],
            [null],
            [''],
            [[]],
            [(object)[]],
            [-32],
            ['tooShort'],
            ['  tooShort  ']
        ];
    }

    /**
     * @return array
     */
    public function invalidStoresDataProvider()
    {
        $mockStore = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            [$mockStore->method('getId')->willReturn(null)],
            [$mockStore->method('getId')->willReturn(0)],
            [$mockStore->method('getId')->willReturn(9999999)],
            [null],
            [''],
            [[]],
            [(object)[]],
            [-32],
            ['a string'],
            [999999999],
            [0]
        ];
    }

    /**
     * @return GetFeaturesInterface
     */
    private function instantiateGetFeatures()
    {
        return $this->objectManager->create(GetFeaturesInterface::class, [
            'featuresApi' => $this->mockFeaturesApi,
            'storeManager' => $this->storeManager,
            'scopeConfig' => $this->mockScopeConfig,
            'accountFeaturesFactory' => $this->accountFeaturesFactory,
            'logger' => $this->mockLogger,
            'request' => $this->mockRequest,
            'reinitableConfig' => $this->mockReinitableConfig
        ]);
    }

    /**
     * @param AccountFeaturesInterface $accountFeatures
     *
     * @return void
     */
    private function assertIsEmptyFeaturesModel(AccountFeaturesInterface $accountFeatures)
    {
        $this->assertInstanceOf(AccountFeaturesInterface::class, $accountFeatures);
        $this->assertNull($accountFeatures->getUpgradeUrl());
        $this->assertFalse(
            $accountFeatures->isFeatureAvailable('enabledaddtocartfront'),
            'Feature is available enabledaddtocartfront'
        );
        $this->assertFalse(
            $accountFeatures->isFeatureAvailable('preserves_layout'),
            'Feature is available preserves_layout'
        );
        $this->assertNull($accountFeatures->getPreserveLayoutMessage());
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
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->mockFeaturesApi = $this->getMockBuilder(FeaturesApi::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->mockScopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->accountFeaturesFactory = $this->objectManager->create(AccountFeaturesFactory::class);
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockRequest = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockReinitableConfig = $this->getMockBuilder(ReinitableConfigInterface::class)
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
