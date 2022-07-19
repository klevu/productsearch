<?php

namespace Klevu\Search\Test\Integration\Model\Order;

use Klevu\Search\Api\Service\Sync\GetOrderSelectMaxLimitInterface;
use Klevu\Search\Model\Api\Action\Producttracking;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Model\Order\Sync;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Config\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class SyncTest extends TestCase
{
    /**
     * @var string
     */
    private $installDir;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ScopeInterface
     */
    private $scopeConfig;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var int[]
     */
    private $executeCallsPerApiKey = [];

    /**
     * Feature: Order synchronisation by cron can be enabled or disabled by administrators
     *
     * Scenario: Order sync is enabled for all stores
     *    Given: Order Sync Enabled is set to Yes in the default scope
     *      and: klevu_test_store_1 has unsent orders
     *      and: klevu_test_store_2 has unsent orders
     *     When: The order synchronisation cron task is run
     *     Then: Order information is sent via the API for klevu_test_store_1
     *      and: Order information is sent via the API for klevu_test_store_2
     *      and: Order information for all stores contains required fields
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadAllFixtures
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-klevu_test_store_1
     * @magentoConfigFixture klevu_test_store_2_store klevu_search/general/js_api_key klevu-klevu_test_store_2
     */
    public function testRunTriggersApiRequest_SyncEnabledAllStores()
    {
        $this->setupPhp5();

        /** @var ScopeConfigInterface $scopeConfig */
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $this->assertTrue($scopeConfig->isSetFlag('klevu_search/product_sync/order_sync_enabled'));

        $this->executeCallsPerApiKey = [
            'klevu-klevu_test_store_1' => 0,
            'klevu-klevu_test_store_2' => 0,
        ];

        /** @var Sync $orderSyncModel */
        $orderSyncModel = $this->objectManager->create(Sync::class, [
            'apiActionProducttracking' => $this->getProducttrackingActionMock(true),
        ]);
        $orderSyncModel->run();

        $this->assertCount(2, $this->executeCallsPerApiKey);
        $this->assertSame(21, $this->executeCallsPerApiKey['klevu-klevu_test_store_1'], 'Execute calls for klevu_test_store_1');
        $this->assertSame(5, $this->executeCallsPerApiKey['klevu-klevu_test_store_2'], 'Execute calls for klevu_test_store_2');

        static::loadAllFixturesRollback();
    }

    /**
     * Feature: Order synchronisation by cron can be enabled or disabled by administrators
     *
     * Scenario: Order sync is enabled for a single store
     *    Given: Order Sync Enabled is set to Yes in the store scope for klevu_test_store_1
     *      and: Order Sync Enabled is set to No in the store scope for klevu_test_store_2
     *      and: klevu_test_store_1 has unsent orders
     *      and: klevu_test_store_2 has unsent orders
     *     When: The order synchronisation cron task is run
     *     Then: Order information is sent via the API for klevu_test_store_1
     *      and: Order information is not sent via the API for klevu_test_store_2
     *      and: Order information for all stores contains required fields
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadAllFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-klevu_test_store_1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_2_store klevu_search/general/js_api_key klevu-klevu_test_store_2
     * @magentoConfigFixture klevu_test_store_2_store klevu_search/product_sync/order_sync_enabled 0
     */
    public function testRunTriggersApiRequest_SyncEnabledSingleStore()
    {
        $this->setupPhp5();

        $this->executeCallsPerApiKey = [
            'klevu-klevu_test_store_1' => 0,
            'klevu-klevu_test_store_2' => 0,
        ];

        /** @var Sync $orderSyncModel */
        $orderSyncModel = $this->objectManager->create(Sync::class, [
            'apiActionProducttracking' => $this->getProducttrackingActionMock(true),
        ]);
        $orderSyncModel->run();

        $this->assertCount(2, $this->executeCallsPerApiKey);
        $this->assertSame(21, $this->executeCallsPerApiKey['klevu-klevu_test_store_1'], 'Execute calls for klevu_test_store_1');
        $this->assertSame(0, $this->executeCallsPerApiKey['klevu-klevu_test_store_2'], 'Execute calls for klevu_test_store_2');

        static::loadAllFixturesRollback();
    }

    /**
     * Feature: The maximum number of records sent in a single order synchronisation run can be defined by administrators
     *
     * Scenario: Maximum batch size is defined in stores configuration and is lower than the total number of unsent orders
     *    Given: Order Sync Enabled is set to Yes for all stores
     *      and: Maximum Batch Size is set to 10 for all stores
     *      and: klevu_test_store_1 has 21 unsent orders
     *      and: klevu_test_store_2 has 5 unsent orders
     *     When: The order synchronisation cron task is run
     *     Then: Order information is sent for 10 records via the API for klevu_test_store_1
     *      and: Order information is sent for 5 records via the API for klevu_test_store_2
     *      and: Order information for all stores contains required fields
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadAllFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-klevu_test_store_1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_max_batch_size 10
     * @magentoConfigFixture klevu_test_store_2_store klevu_search/general/js_api_key klevu-klevu_test_store_2
     * @magentoConfigFixture klevu_test_store_2_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_2_store klevu_search/product_sync/order_sync_max_batch_size 10
     */
    public function testRunTriggersApiRequest_MaxBatchSizeApplied()
    {
        $this->setupPhp5();

        $this->executeCallsPerApiKey = [
            'klevu-klevu_test_store_1' => 0,
            'klevu-klevu_test_store_2' => 0,
        ];

        /** @var Sync $orderSyncModel */
        $orderSyncModel = $this->objectManager->create(Sync::class, [
            'apiActionProducttracking' => $this->getProducttrackingActionMock(true),
        ]);
        $orderSyncModel->run();

        $this->assertCount(2, $this->executeCallsPerApiKey);
        $this->assertSame(10, $this->executeCallsPerApiKey['klevu-klevu_test_store_1'], 'Execute calls for klevu_test_store_1');
        $this->assertSame(5, $this->executeCallsPerApiKey['klevu-klevu_test_store_2'], 'Execute calls for klevu_test_store_2');

        static::loadAllFixturesRollback();
    }

    /**
     * Feature: Order synchronisation can be triggered programmatically for specified stores
     *
     * Scenario: Order sync is enabled for all stores
     *    Given: Order Sync Enabled is set to Yes for all stores
     *      and: klevu_test_store_1 has unsent orders
     *      and: klevu_test_store_2 has unsent orders
     *     When: The order synchronisation method is called for klevu_test_store_1 only
     *     Then: Order information is sent via the API for klevu_test_store_1
     *      and: Order information is not sent via the API for klevu_test_store_2
     *      and: Order information for all stores contains required fields
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadAllFixtures
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-klevu_test_store_1
     * @magentoConfigFixture klevu_test_store_2_store klevu_search/general/js_api_key klevu-klevu_test_store_2
     */
    public function testRunTriggersApiRequest_ExplicitStores()
    {
        $this->setupPhp5();

        $this->executeCallsPerApiKey = [
            'klevu-klevu_test_store_1' => 0,
            'klevu-klevu_test_store_2' => 0,
        ];

        /** @var Sync $orderSyncModel */
        $orderSyncModel = $this->objectManager->create(Sync::class, [
            'apiActionProducttracking' => $this->getProducttrackingActionMock(true),
        ]);
        $orderSyncModel->setStoreCodesToRun(['klevu_test_store_1']);
        $orderSyncModel->run();

        $this->assertCount(2, $this->executeCallsPerApiKey);
        $this->assertSame(21, $this->executeCallsPerApiKey['klevu-klevu_test_store_1'], 'Execute calls for klevu_test_store_1');
        $this->assertSame(0, $this->executeCallsPerApiKey['klevu-klevu_test_store_2'], 'Execute calls for klevu_test_store_2');

        static::loadAllFixturesRollback();
    }

    /**
     * Feature: Order synchronisation will not run if another order synchronisation process is already underway
     *
     * Scenario: Order synchronisation is already in progress
     *    Given: A non-expired lock file exists for the order sync process
     *      and: Order Sync Enabled is set to Yes for all stores
     *      and: klevu_test_store_1 has unsent orders
     *      and: klevu_test_store_2 has unsent orders
     *     When: An order synchronisation process is initiated
     *     Then: No orders information is sent by API for any store
     *
     * @depends testRunTriggersApiRequest_SyncEnabledAllStores
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadAllFixtures
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-klevu_test_store_1
     * @magentoConfigFixture klevu_test_store_2_store klevu_search/general/js_api_key klevu-klevu_test_store_2
     */
    public function testRunExitsIfLockFilePresent()
    {
        $this->setupPhp5();

        $sourceFilePath = $this->installDir . '/var/klevu_running_order_sync.lock';
        $this->createSourceFile($sourceFilePath, time());
        $this->assertFileExists($sourceFilePath);

        $this->executeCallsPerApiKey = [
            'klevu-klevu_test_store_1' => 0,
            'klevu-klevu_test_store_2' => 0,
        ];

        /** @var Sync $orderSyncModel */
        $orderSyncModel = $this->objectManager->create(Sync::class, [
            'apiActionProducttracking' => $this->getProducttrackingActionMock(true),
        ]);
        $orderSyncModel->run();

        $this->assertCount(2, $this->executeCallsPerApiKey);
        $this->assertSame(0, $this->executeCallsPerApiKey['klevu-klevu_test_store_1'], 'Execute calls for klevu_test_store_1');
        $this->assertSame(0, $this->executeCallsPerApiKey['klevu-klevu_test_store_2'], 'Execute calls for klevu_test_store_2');

        static::loadAllFixturesRollback();
    }

    /**
     * Feature: Order synchronisation will not run if another order synchronisation process is already underway
     *
     * Scenario: A previous order synchronisation process exited without completing
     *    Given: A lock file exists for the order sync process
     *      and: The lock file is two hours old
     *      and: Order Sync Enabled is set to Yes for all stores
     *      and: klevu_test_store_1 has unsent orders
     *      and: klevu_test_store_2 has unsent orders
     *     When: An order synchronisation process is initiated
     *     Then: Order information is sent via the API for klevu_test_store_1
     *      and: Order information is not sent via the API for klevu_test_store_2
     *      and: Order information for all stores contains required fields
     *
     * @depends testRunExitsIfLockFilePresent
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadAllFixtures
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-klevu_test_store_1
     * @magentoConfigFixture klevu_test_store_2_store klevu_search/general/js_api_key klevu-klevu_test_store_2
     */
    public function testRunClearsLockFileIfExpired()
    {
        $this->setupPhp5();

        $sourceFilePath = $this->installDir . '/var/klevu_running_order_sync.lock';
        $this->createSourceFile($sourceFilePath, time() - (60 * 60 * 2));
        $this->assertFileExists($sourceFilePath);

        $this->executeCallsPerApiKey = [
            'klevu-klevu_test_store_1' => 0,
            'klevu-klevu_test_store_2' => 0,
        ];

        /** @var Sync $orderSyncModel */
        $orderSyncModel = $this->objectManager->create(Sync::class, [
            'apiActionProducttracking' => $this->getProducttrackingActionMock(true),
        ]);
        $orderSyncModel->run();

        $this->assertCount(2, $this->executeCallsPerApiKey);
        $this->assertSame(21, $this->executeCallsPerApiKey['klevu-klevu_test_store_1'], 'Execute calls for klevu_test_store_1');
        $this->assertSame(5, $this->executeCallsPerApiKey['klevu-klevu_test_store_2'], 'Execute calls for klevu_test_store_2');

        static::loadAllFixturesRollback();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadAllFixtures
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-klevu_test_store_1
     */
    public function testWhileLoopIteratesCorrectNumberOfTimes()
    {
        $this->setupPhp5();

        $mockGetOrderSelectMaxLimit = $this->getMockBuilder(GetOrderSelectMaxLimitInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockGetOrderSelectMaxLimit->expects($this->once())->method('execute')->willReturn(5);

        $mockProductTrackingAction = $this->getProducttrackingActionMock(true);
        $mockProductTrackingAction->expects($this->exactly(21))->method('execute');

        $orderSyncModel = $this->objectManager->create(Sync::class, [
            'apiActionProducttracking' => $mockProductTrackingAction,
            'getOrderSelectMaxLimit' => $mockGetOrderSelectMaxLimit
        ]);
        $orderSyncModel->run();
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->installDir = $GLOBALS['installDir'];
        $this->objectManager = Bootstrap::getObjectManager();
        $this->scopeConfig = $this->objectManager->get(ScopeInterface::class);
        $this->productMetadata = $this->objectManager->get(ProductMetadataInterface::class);

        $this->deleteSourceFile($this->installDir . '/var/klevu_running_order_sync.lock');

        // Support for Magento 2.1.x. See https://github.com/magento/magento2/issues/2907#issuecomment-169476734
        $currentScope = version_compare($this->productMetadata->getVersion(), '2.2.0', '>=')
            ? Area::AREA_CRONTAB
            : 'cron';
        $this->scopeConfig->setCurrentScope($currentScope);
    }

    /**
     * @param string|bool $return
     * @return Producttracking|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getProducttrackingActionMock($return)
    {
        if (!method_exists($this, 'createMock')) {
            return $this->getProducttrackingActionMockLegacy($return);
        }

        $response = $this->getResponseMock(
            true === $return,
            is_string($return) ? $return : null
        );

        $producttrackingActionMock = $this->createMock(Producttracking::class);

        $expectedArrayKeys = [
            'klevu_apiKey',
            'klevu_type',
            'klevu_productId',
            'klevu_unit',
            'klevu_salePrice',
            'klevu_currency',
            'klevu_shopperIP',
            'Klevu_sessionId',
            'klevu_orderDate',
            'klevu_emailId',
            'klevu_storeTimezone',
            'Klevu_clientIp',
            'klevu_checkoutDate',
            'klevu_productPosition',
        ];
        $producttrackingActionMock
            ->method('execute')
            ->willReturnCallback(function ($arguments) use ($expectedArrayKeys, $response) {
                if (method_exists($this, 'assertIsArray')) {
                    $this->assertIsArray($arguments);
                } else {
                    $this->assertTrue(is_array($arguments), 'Is Array');
                }

                foreach ($expectedArrayKeys as $expectedArrayKey) {
                    $this->assertArrayHasKey($expectedArrayKey, $arguments);
                }

                $this->assertSame('checkout', $arguments['klevu_type']);

                if (!isset($this->executeCallsPerApiKey[$arguments['klevu_apiKey']])) {
                    $this->executeCallsPerApiKey[$arguments['klevu_apiKey']] = 0;
                }
                $this->executeCallsPerApiKey[$arguments['klevu_apiKey']]++;

                return $response;
            });

        return $producttrackingActionMock;
    }

    /**
     * @param string|bool $return
     * @return Producttracking|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getProducttrackingActionMockLegacy($return)
    {
        $response = $this->getResponseMock(
            true === $return,
            is_string($return) ? $return : null
        );

        $producttrackingActionMock = $this->getMockBuilder(Producttracking::class)
            ->disableOriginalConstructor()
            ->getMock();

        $expectedArrayKeys = [
            'klevu_apiKey',
            'klevu_type',
            'klevu_productId',
            'klevu_unit',
            'klevu_salePrice',
            'klevu_currency',
            'klevu_shopperIP',
            'Klevu_sessionId',
            'klevu_orderDate',
            'klevu_emailId',
            'klevu_storeTimezone',
            'Klevu_clientIp',
            'klevu_checkoutDate',
            'klevu_productPosition',
        ];
        $producttrackingActionMock
            ->expects($this->any())
            ->method('execute')
            ->willReturnCallback(function ($arguments) use ($expectedArrayKeys, $response) {
                if (method_exists($this, 'assertIsArray')) {
                    $this->assertIsArray($arguments);
                } else {
                    $this->assertTrue(is_array($arguments), 'Is Array');
                }
                foreach ($expectedArrayKeys as $expectedArrayKey) {
                    $this->assertArrayHasKey($expectedArrayKey, $arguments);
                }

                $this->assertSame('checkout', $arguments['klevu_type']);

                if (!isset($this->executeCallsPerApiKey[$arguments['klevu_apiKey']])) {
                    $this->executeCallsPerApiKey[$arguments['klevu_apiKey']] = 0;
                }
                $this->executeCallsPerApiKey[$arguments['klevu_apiKey']]++;

                return $response;
            });


        return $producttrackingActionMock;
    }

    /**
     * @param bool $isSuccess
     * @param string|null $message
     * @return Response|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getResponseMock($isSuccess, $message = '')
    {
        if (!method_exists($this, 'createMock')) {
            return $this->getResponseMockLegacy($isSuccess, $message);
        }

        $responseMock = $this->createMock(Response::class);
        $responseMock->method('isSuccess')->willReturn($isSuccess);
        $responseMock->method('getMessage')->willReturn($message);

        return $responseMock;
    }

    /**
     * @param bool $isSuccess
     * @param string|null $message
     * @return Response|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getResponseMockLegacy($isSuccess, $message = '')
    {
        $responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $responseMock->expects($this->any())->method('isSuccess')->willReturn($isSuccess);
        $responseMock->expects($this->any())->method('getMessage')->willReturn($message);

        return $responseMock;
    }

    /**
     * @param string $sourceFilePath
     * @return void
     */
    private function createSourceFile($sourceFilePath, $mtime)
    {
        touch($sourceFilePath, $mtime);

        if (!file_exists($sourceFilePath)) {
            throw new \RuntimeException(sprintf(
                'Could not create test source file !%s"',
                $sourceFilePath
            ));
        }
    }

    /**
     * @param string $sourceFilePath
     */
    private function deleteSourceFile($sourceFilePath)
    {
        if (file_exists($sourceFilePath)) {
            unlink($sourceFilePath);
        }
    }

    /**
     * Loads store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        require __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        require __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        require_once __DIR__ . '/../../_files/productFixtures.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        require_once __DIR__ . '/../../_files/productFixtures_rollback.php';
    }

    /**
     * Loads order creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadOrderFixtures()
    {
        require __DIR__ . '/_files/orderFixtures.php';
    }

    /**
     * Rolls back order creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadOrderFixturesRollback()
    {
        require __DIR__ . '/_files/orderFixtures_rollback.php';
    }

    /**
     * Loads klevu order sync fixtures scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuOrderSyncFixtures()
    {
        require __DIR__ . '/_files/klevuOrderSyncFixtures.php';
    }

    /**
     *
     */
    public static function loadAllFixtures()
    {
        static::loadWebsiteFixtures();
        static::loadProductFixtures();
        static::loadOrderFixtures();
        static::loadKlevuOrderSyncFixtures();
    }

    /**
     *
     */
    public static function loadAllFixturesRollback()
    {
        static::loadOrderFixturesRollback();
        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }
}
