<?php

namespace Klevu\Search\Test\Integration\Service\Sync\Product;

use Klevu\Search\Api\Service\Sync\Product\GetRecordsPerPageInterface;
use Klevu\Search\Service\Sync\Product\GetRecordsPerPage;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreFactory;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RecordsPerPageTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var LoggerInterface|MockObject
     */
    private $mockLogger;
    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $mockScopeConfig;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $getRecordsPerPageService = $this->instantiateGetRecordsPerPageService();

        $this->assertInstanceOf(GetRecordsPerPage::class, $getRecordsPerPageService);
    }

    public function testReturnsDefaultLevelWhenDbValueIsNotSet()
    {
        $this->setupPhp5();

        $getRecordsPerPageService = $this->instantiateGetRecordsPerPageService();
        $recordsPerPage = $getRecordsPerPageService->execute();

        $this->assertSame(GetRecordsPerPage::PRODUCT_SYNC_RECORDS_PER_PAGE_DEFAULT, $recordsPerPage);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture default/klevu_search/developer/product_sync_records_per_page 100
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/product_sync_records_per_page 50
     */
    public function testReturnsStoreLevelValue()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $storeIds = [
            $store,
            (int)$store->getId(),
            (string)$store->getId(),
            $store->getCode()
        ];

        foreach ($storeIds as $storeId) {
            $getRecordsPerPage = $this->instantiateGetRecordsPerPageService();
            $recordsPerPage = $getRecordsPerPage->execute($storeId);

            $this->assertSame(50, $recordsPerPage);
        }
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture default/klevu_search/developer/product_sync_records_per_page 100
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/product_sync_records_per_page 50
     */
    public function testReturnsDefaultValueIfIncorrectStoreDataProvided()
    {
        $this->setupPhp5();

        $storeFactory = $this->objectManager->get(StoreFactory::class);
        $store = $storeFactory->create();

        $storeIds = [
            $store,
            9999999999999999,
            '999999999999999',
            'some_incorrect_string'
        ];

        foreach ($storeIds as $storeId) {
            $getRecordsPerPageService = $this->instantiateGetRecordsPerPageService();
            $recordsPerPage = $getRecordsPerPageService->execute($storeId);

            $this->assertSame(GetRecordsPerPage::PRODUCT_SYNC_RECORDS_PER_PAGE_DEFAULT, $recordsPerPage);
        }
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture default/klevu_search/developer/product_sync_records_per_page 100
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/product_sync_records_per_page 50
     */
    public function testReturnsDefaultValueIfIncorrectStoreTypeProvided()
    {
        $this->setupPhp5();

        $storeFactory = $this->objectManager->get(StoreFactory::class);
        $store = $storeFactory->create();

        $storeIds = [
            [$store],
            123.45,
            '999999999999999',
            json_decode(json_encode(['1' => '2'])),
            false,
            true
        ];

        $this->mockLogger->expects($this->exactly(count($storeIds)))->method('error');

        foreach ($storeIds as $storeId) {
            $getRecordsPerPageService = $this->instantiateGetRecordsPerPageService(
                ['logger' => $this->mockLogger]
            );
            $recordsPerPage = $getRecordsPerPageService->execute($storeId);

            $this->assertSame(GetRecordsPerPage::PRODUCT_SYNC_RECORDS_PER_PAGE_DEFAULT, $recordsPerPage);
        }
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @dataProvider invalidDatabaseValueDataProvider
     */
    public function testDefaultReturnedWhenDatabaseValueInvalid($value)
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $this->mockScopeConfig->expects($this->once())
            ->method('getValue')
            ->willReturn($value);

        $getRecordsPerPageService = $this->instantiateGetRecordsPerPageService(
            [
                'scopeConfig' => $this->mockScopeConfig
            ]
        );
        $recordsPerPage = $getRecordsPerPageService->execute($store->getId());

        $this->assertSame(GetRecordsPerPage::PRODUCT_SYNC_RECORDS_PER_PAGE_DEFAULT, $recordsPerPage);
    }

    /**
     * @return array
     */
    public function invalidDatabaseValueDataProvider()
    {
        return [
            [-3],
            [31.43],
            ['some string'],
            [json_encode([1])],
            [null],
            [true],
            [false],
            [501],
            [0]
        ];
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->mockScopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * @param array $params
     *
     * @return GetRecordsPerPageInterface
     */
    private function instantiateGetRecordsPerPageService(array $params = [])
    {
        return $this->objectManager->create(GetRecordsPerPageInterface::class, $params);
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
