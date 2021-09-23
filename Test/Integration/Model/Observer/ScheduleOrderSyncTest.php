<?php

namespace Klevu\Search\Test\Integration\Model\Observer;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\Framework\App\ResourceConnection as FrameworkModelResource;

/**
 * Test for schedule order sync
 *
 * @see \Klevu\Search\Model\Observer\ScheduleOrderSync
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class ScheduleOrderSyncTest extends TestCase
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
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
    }

    /**
     * Sets up tests to reduce code duplication / improve readability regarding different asserts
     * - Tests setup
     * - Sets current store based on passed argument
     * - Rolls back any existing orders, and asserts starting conditions
     * - Places 2 orders via order fixtures
     * - Returns relevant order records from klevu_order_sync for asserts in individual tests
     *
     * @param string $storeCode
     * @return array
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    private function setupOrderTest($storeCode)
    {
        $this->setupPhp5();

        $store = $this->storeManager->getStore($storeCode);
        $this->storeManager->setCurrentStore((int)$store->getId());

        // Clear decks before starting tests
        self::loadAllOrderFixturesRollback();

        $klevuOrdersBefore = $this->getKlevuOrders(['KLEVUOBS100000001', 'KLEVUOBS100000002']);
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($klevuOrdersBefore);
        } else {
            $this->assertTrue(is_array($klevuOrdersBefore), 'Is Array');
        }
        $this->assertEmpty($klevuOrdersBefore);
        /** @var StoreManagerInterface $storeManager */

        self::loadAllOrderFixtures();

        return $this->getKlevuOrders(['KLEVUOBS100000001', 'KLEVUOBS100000002']);
    }

    /**
     * Feature: Orders are queued up for synchronisation at time of submission when order sync is enabled
     *
     * Scenario: Order is placed through the backend for a store which is not default
     *    Given: There are two stores configured
     *      and: Order sync is enabled globally
     *      and: The default store view does not have order sync enabled
     *      and: The additional store view does not have order sync enabled
     *     When: An order is placed in the ADMIN area
     *      and: The order is placed for the additional store
     *     Then: The order items are not queued up for synchronisation
     *
     * Ref: KS-5519
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture admin_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoDataFixture loadInitialFixtures
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testScheduleOrder_Admin_GlobalSyncEnabled_DefaultStoreSyncDisabled_AdditionalStoreSyncDisabled()
    {
        $klevuOrdersAfter = $this->setupOrderTest('admin');

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($klevuOrdersAfter);
        } else {
            $this->assertTrue(is_array($klevuOrdersAfter), 'Is Array');
        }
        $this->assertEmpty($klevuOrdersAfter);

        self::loadAllOrderFixturesRollback();
    }

    /**
     * Feature: Orders are queued up for synchronisation at time of submission when order sync is enabled
     *
     * Scenario: Order is placed through the backend for a store which is not default
     *    Given: There are two stores configured
     *      and: Order sync is enabled globally
     *      and: The default store view has order sync enabled
     *      and: The additional store view does not have order sync enabled
     *     When: An order is placed in the ADMIN area
     *      and: The order is placed for the additional store
     *     Then: The order items are not queued up for synchronisation
     *
     * Ref: KS-5519
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture admin_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoDataFixture loadInitialFixtures
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testScheduleOrder_Admin_GlobalSyncEnabled_DefaultStoreSyncEnabled_AdditionalStoreSyncDisabled()
    {
        $klevuOrdersAfter = $this->setupOrderTest('admin');

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($klevuOrdersAfter);
        } else {
            $this->assertTrue(is_array($klevuOrdersAfter), 'Is Array');
        }
        $this->assertEmpty($klevuOrdersAfter);

        self::loadAllOrderFixturesRollback();
    }

    /**
     * Feature: Orders are queued up for synchronisation at time of submission when order sync is enabled
     *
     * Scenario: Order is placed through the backend for a store which is not default
     *    Given: There are two stores configured
     *      and: Order sync is enabled globally
     *      and: The default store view does not have order sync enabled
     *      and: The additional store view has order sync enabled
     *     When: An order is placed in the ADMIN area
     *      and: The order is placed for the additional store
     *     Then: The order items are queued up for synchronisation
     *
     * Ref: KS-5519
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture admin_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoDataFixture loadInitialFixtures
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testScheduleOrder_Admin_GlobalSyncEnabled_DefaultStoreSyncDisabled_AdditionalStoreSyncEnabled()
    {
        $klevuOrdersAfter = $this->setupOrderTest('admin');

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($klevuOrdersAfter);
        } else {
            $this->assertTrue(is_array($klevuOrdersAfter), 'Is Array');
        }
        $this->assertCount(2, $klevuOrdersAfter);

        self::loadAllOrderFixturesRollback();
    }

    /**
     * Feature: Orders are queued up for synchronisation at time of submission when order sync is enabled
     *
     * Scenario: Order is placed through the backend for a store which is not default
     *    Given: There are two stores configured
     *      and: Order sync is disabled globally
     *      and: The default store view has order sync enabled
     *      and: The additional store view does not have order sync enabled
     *     When: An order is placed in the ADMIN area
     *      and: The order is placed for the additional store
     *     Then: The order items are not queued up for synchronisation
     *
     * Ref: KS-5519
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture admin_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoDataFixture loadInitialFixtures
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testScheduleOrder_Admin_GlobalSyncDisabled_DefaultStoreSyncEnabled_AdditionalStoreSyncDisabled()
    {
        $klevuOrdersAfter = $this->setupOrderTest('admin');

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($klevuOrdersAfter);
        } else {
            $this->assertTrue(is_array($klevuOrdersAfter), 'Is Array');
        }
        $this->assertEmpty($klevuOrdersAfter);

        self::loadAllOrderFixturesRollback();
    }

    /**
     * Feature: Orders are queued up for synchronisation at time of submission when order sync is enabled
     *
     * Scenario: Order is placed through the backend for a store which is not default
     *    Given: There are two stores configured
     *      and: Order sync is disabled globally
     *      and: The default store view does not have order sync enabled
     *      and: The additional store view has order sync enabled
     *     When: An order is placed in the ADMIN area
     *      and: The order is placed for the additional store
     *     Then: The order items are queued up for synchronisation
     *
     * Ref: KS-5519
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture admin_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoDataFixture loadInitialFixtures
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testScheduleOrder_Admin_GlobalSyncDisabled_DefaultStoreSyncDisabled_AdditionalStoreSyncEnabled()
    {
        $klevuOrdersAfter = $this->setupOrderTest('admin');

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($klevuOrdersAfter);
        } else {
            $this->assertTrue(is_array($klevuOrdersAfter), 'Is Array');
        }
        $this->assertCount(2, $klevuOrdersAfter);

        self::loadAllOrderFixturesRollback();
    }

    /**
     * Feature: Orders are queued up for synchronisation at time of submission when order sync is enabled
     *
     * Scenario: Order is placed through the frontend for a store which is not default
     *    Given: There are two stores configured
     *      and: Order sync is enabled globally
     *      and: The default store view does not have order sync enabled
     *      and: The additional store view does not have order sync enabled
     *     When: An order is placed in the FRONTEND area
     *      and: The order is placed on the additional store
     *     Then: The order items are not queued up for synchronisation
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture admin_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoDataFixture loadInitialFixtures
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testScheduleOrder_Frontend_GlobalSyncEnabled_DefaultStoreSyncDisabled_AdditionalStoreSyncDisabled()
    {
        $klevuOrdersAfter = $this->setupOrderTest('klevu_test_store_1');

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($klevuOrdersAfter);
        } else {
            $this->assertTrue(is_array($klevuOrdersAfter), 'Is Array');
        }
        $this->assertEmpty($klevuOrdersAfter);

        self::loadAllOrderFixturesRollback();
    }

    /**
     * Feature: Orders are queued up for synchronisation at time of submission when order sync is enabled
     *
     * Scenario: Order is placed through the frontend for a store which is not default
     *    Given: There are two stores configured
     *      and: Order sync is enabled globally
     *      and: The default store view has order sync enabled
     *      and: The additional store view does not have order sync enabled
     *     When: An order is placed in the FRONTEND area
     *      and: The order is placed on the additional store
     *     Then: The order items are not queued up for synchronisation
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture admin_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoDataFixture loadInitialFixtures
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testScheduleOrder_Frontend_GlobalSyncEnabled_DefaultStoreSyncEnabled_AdditionalStoreSyncDisabled()
    {
        $klevuOrdersAfter = $this->setupOrderTest('klevu_test_store_1');

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($klevuOrdersAfter);
        } else {
            $this->assertTrue(is_array($klevuOrdersAfter), 'Is Array');
        }
        $this->assertEmpty($klevuOrdersAfter);

        self::loadAllOrderFixturesRollback();
    }

    /**
     * Feature: Orders are queued up for synchronisation at time of submission when order sync is enabled
     *
     * Scenario: Order is placed through the frontend for a store which is not default
     *    Given: There are two stores configured
     *      and: Order sync is enabled globally
     *      and: The default store view does not have order sync enabled
     *      and: The additional store view has order sync enabled
     *     When: An order is placed in the FRONTEND area
     *      and: The order is placed on the additional store
     *     Then: The order items are queued up for synchronisation
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture admin_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoDataFixture loadInitialFixtures
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testScheduleOrder_Frontend_GlobalSyncEnabled_DefaultStoreSyncDisabled_AdditionalStoreSyncEnabled()
    {
        $klevuOrdersAfter = $this->setupOrderTest('klevu_test_store_1');

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($klevuOrdersAfter);
        } else {
            $this->assertTrue(is_array($klevuOrdersAfter), 'Is Array');
        }
        $this->assertCount(2, $klevuOrdersAfter);

        self::loadAllOrderFixturesRollback();
    }

    /**
     * Feature: Orders are queued up for synchronisation at time of submission when order sync is enabled
     *
     * Scenario: Order is placed through the frontend for a store which is not default
     *    Given: There are two stores configured
     *      and: Order sync is disabled globally
     *      and: The default store view has order sync enabled
     *      and: The additional store view does not have order sync enabled
     *     When: An order is placed in the FRONTEND area
     *      and: The order is placed on the additional store
     *     Then: The order items are not queued up for synchronisation
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture admin_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoDataFixture loadInitialFixtures
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testScheduleOrder_Frontend_GlobalSyncDisabled_DefaultStoreSyncEnabled_AdditionalStoreSyncDisabled()
    {
        $klevuOrdersAfter = $this->setupOrderTest('klevu_test_store_1');

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($klevuOrdersAfter);
        } else {
            $this->assertTrue(is_array($klevuOrdersAfter), 'Is Array');
        }
        $this->assertEmpty($klevuOrdersAfter);

        self::loadAllOrderFixturesRollback();
    }

    /**
     * Feature: Orders are queued up for synchronisation at time of submission when order sync is enabled
     *
     * Scenario: Order is placed through the frontend for a store which is not default
     *    Given: There are two stores configured
     *      and: Order sync is disabled globally
     *      and: The default store view does not have order sync enabled
     *      and: The additional store view has order sync enabled
     *     When: An order is placed in the FRONTEND area
     *      and: The order is placed on the additional store
     *     Then: The order items are queued up for synchronisation
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture admin_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoDataFixture loadInitialFixtures
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function testScheduleOrder_Frontend_GlobalSyncDisabled_DefaultStoreSyncDisabled_AdditionalStoreSyncEnabled()
    {
        $klevuOrdersAfter = $this->setupOrderTest('klevu_test_store_1');

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($klevuOrdersAfter);
        } else {
            $this->assertTrue(is_array($klevuOrdersAfter), 'Is Array');
        }
        $this->assertCount(2, $klevuOrdersAfter);

        self::loadAllOrderFixturesRollback();
    }

    /**
     * Returns the un-send klevu orders data
     *
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function getKlevuOrders($incrementIds = [])
    {
        /** @var FrameworkModelResource $resource */
        $resource = $this->objectManager->get(FrameworkModelResource::class);
        $connection = $resource->getConnection();

        $select = $connection->select();
        $select->from([
            'order_sync' => $resource->getTableName('klevu_order_sync'),
        ]);
        $select->join(
            ['order_item' => $resource->getTableName('sales_order_item')],
            'order_item.item_id = order_sync.order_item_id',
            []
        );
        $select->join(
            ['order' => $resource->getTableName('sales_order')],
            'order_item.order_id = order.entity_id',
            []
        );

        $select->where('order_sync.send=?', 0);
        $select->where('order.increment_id IN (?)', $incrementIds);

        $stmt = $connection->query($select);

        return $stmt->fetchAll();
    }

    /**
     * Loads website and products creation fixtures because annotations use a relative path
     *  from integration tests root
     */
    public static function loadInitialFixtures()
    {
        static::loadWebsiteFixtures();
        static::loadProductFixtures();
    }

    /**
     * Rolls back product and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadInitialFixturesRollback()
    {
        static::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
        static::loadKlevuOrderSyncFixturesRollback();
        static::loadOrderFixturesRollback();
    }

    /**
     * Rolls back sales order scripts and klevu order sync scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadAllOrderFixturesRollback()
    {
        static::loadKlevuOrderSyncFixturesRollback();
        static::loadOrderFixturesRollback();
    }

    /**
     * Loads sales order scripts and klevu order sync fixtures because annotations use a relative path
     *  from integration tests root
     */
    public static function loadAllOrderFixtures()
    {
        static::loadOrderFixtures();
    }

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        require __DIR__ . '/../../_files/productFixtures.php';
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
     * Loads store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        require __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        require __DIR__ . '/../../_files/productFixtures_rollback.php';
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
     * Rolls back store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        require __DIR__ . '/../../_files/websiteFixtures_rollback.php';
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
     * Loads klevu order sync fixtures scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuOrderSyncFixturesRollback()
    {
        require __DIR__ . '/_files/klevuOrderSyncFixtures_rollback.php';
    }
}
