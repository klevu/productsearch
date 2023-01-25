<?php

namespace Klevu\Search\Test\Integration\Service\Sync\Product;

use Klevu\Search\Service\Sync\Product\GetHistoryLength;
use Klevu\Search\Api\Service\Sync\Product\GetHistoryLengthInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GetHistoryLengthTest extends TestCase
{
    /**
     * @var  ObjectManager
     */
    private $objectManager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private $mockLogger;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $getHistoryLength = $this->instantiateGetHistoryLength();

        $this->assertInstanceOf(GetHistoryLength::class, $getHistoryLength);
    }

    public function testDefaultValueIsSet()
    {
        $this->setUpPhp5();

        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $value = (int)$scopeConfig->getValue(GetHistoryLength::XML_PATH_PRODUCT_SYNC_HISTORY_LENGTH);

        $this->assertSame(GetHistoryLength::DEFAULT_HISTORY_LENGTH, $value);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/history_length 10
     */
    public function testStoreConfigValueCanBeSet()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $value = (int)$scopeConfig->getValue(
            GetHistoryLength::XML_PATH_PRODUCT_SYNC_HISTORY_LENGTH,
            ScopeInterface::SCOPE_STORES,
            $store->getId()
        );

        $this->assertSame(10, $value);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/history_length 15
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/history_length 25
     */
    public function testExecuteReturnsStoreScopeValue()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $getHistoryLength = $this->instantiateGetHistoryLength();
        $value = $getHistoryLength->execute((int)$store->getId());

        $this->assertSame(25, $value);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/history_length 15
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/history_length 25
     */
    public function testExecuteReturnsGlobalScopeValueIfStoreNotSet()
    {
        $this->setUpPhp5();

        $getHistoryLength = $this->instantiateGetHistoryLength();
        $value = $getHistoryLength->execute();

        $this->assertSame(15, $value);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/history_length 15
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/history_length 25
     * @dataProvider InvalidStoreIdDataProvider
     */
    public function testExecuteReturnsDefaultValueIfStoreNotValid($storeId)
    {
        $this->setUpPhp5();

        $this->mockLogger->expects($this->exactly(2))
            ->method('error');

        $getHistoryLength = $this->objectManager->create(GetHistoryLengthInterface::class, [
            'logger' => $this->mockLogger
        ]);
        $value = $getHistoryLength->execute($storeId);

        $this->assertSame(1, $value);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testReturnsDefaultValueIfScopeConfigNotSet()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $mockScopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockScopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn(null);

        $getHistoryLength = $this->objectManager->create(GetHistoryLengthInterface::class, [
            'scopeConfig' => $mockScopeConfig
        ]);
        $value = $getHistoryLength->execute((int)$store->getId());

        $this->assertSame(GetHistoryLength::DEFAULT_HISTORY_LENGTH, $value);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @dataProvider invalidConfigSettingsDataProvider
     */
    public function testReturnsDefaultValueIfScopeConfigValueIsInvalid($invalidConfigValue)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $mockScopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockScopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($invalidConfigValue);

        $value = is_scalar($invalidConfigValue) && !is_bool($invalidConfigValue)
            ? $invalidConfigValue
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            : gettype($invalidConfigValue);

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'Invalid value for product sync history length provided. Expected a positive integer, %s provided',
                    is_object($invalidConfigValue) ? get_class($invalidConfigValue) : $value
                )
            );

        $getHistoryLength = $this->objectManager->create(GetHistoryLengthInterface::class, [
            'scopeConfig' => $mockScopeConfig,
            'logger' => $this->mockLogger
        ]);
        $value = $getHistoryLength->execute((int)$store->getId());

        $this->assertSame(GetHistoryLength::DEFAULT_HISTORY_LENGTH, $value);
    }

    /**
     * @return array
     */
    public function InvalidStoreIdDataProvider()
    {
        return [
            ['iengisjgr'],
            [[1]],
            [false],
            [true]
        ];
    }

    /**
     * @return array
     */
    public function invalidConfigSettingsDataProvider()
    {
        return [
            [0],
            [-3],
            [3.56],
            ['test'],
            [false],
            [true],
            [null],
            [[4]],
            [json_encode([4])],
            [implode(', ', [1, 2])]
        ];
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return GetHistoryLengthInterface
     */
    private function instantiateGetHistoryLength()
    {
        return $this->objectManager->get(GetHistoryLengthInterface::class);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode)
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
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
