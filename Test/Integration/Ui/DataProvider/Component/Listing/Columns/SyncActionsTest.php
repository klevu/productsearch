<?php

namespace Klevu\Search\Test\Integration\Ui\DataProvider\Component\Listing\Columns;

use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Ui\Component\Listing\Columns\SyncActions;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 * @magentoDbIsolation disabled
 */
class SyncActionsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var ContextInterface|MockObject
     */
    private $mockContext;
    /**
     * @var array
     */
    private $data;

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadConfigurableProductFixtures
     */
    public function testDoesNotReturnsActionsForConfigurableProduct()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_configurable_1');
        $store = $this->getStore('klevu_test_store_1');

        $this->mockContext->method('getRequestParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataSource = [];
        $dataSource['data'] = [];
        $dataSource['data']['items'] = [
            [
                History::FIELD_PRODUCT_ID => $product->getId(),
                History::FIELD_STORE_ID => $store->getId(),
                ProductInterface::TYPE_ID => $product->getTypeId(),
                'entity_id' => $product->getId(),
                'unique_entity_id' => '0-' . $product->getId() . '-0'
            ]
        ];

        $syncAction = $this->instantiateSyncActions();
        $actions = $syncAction->prepareDataSource($dataSource);

        $this->assertArrayHasKey('data', $actions);
        if (method_exists($this, 'isArray')) {
            $this->isArray($actions['data']);
        } else {
            $this->assertTrue(is_array($actions['data']), 'Is Array');
        }
        $data = $actions['data'];
        $this->assertArrayHasKey('items', $data);
        if (method_exists($this, 'isArray')) {
            $this->isArray($data['items']);
        } else {
            $this->assertTrue(is_array($data['items']), 'Is Array');
        }
        $items = $data['items'];
        $itemKeys = array_keys($items);
        $firstItem = $items[$itemKeys[0]];
        $this->assertArrayNotHasKey('actions', $firstItem);
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 0
     */
    public function testReturnsHistoryLinkForSimpleProductWhenSyncDisabled()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_simple_1');
        $store = $this->getStore('klevu_test_store_1');

        $this->mockContext->method('getRequestParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataSource = [];
        $dataSource['data'] = [];
        $dataSource['data']['items'] = [
            [
                History::FIELD_PRODUCT_ID => $product->getId(),
                History::FIELD_STORE_ID => $store->getId(),
                ProductInterface::TYPE_ID => $product->getTypeId(),
                'entity_id' => $product->getId(),
                'unique_entity_id' => '0-' . $product->getId() . '-0'
            ]
        ];

        $syncAction = $this->instantiateSyncActions();
        $actions = $syncAction->prepareDataSource($dataSource);

        $this->assertArrayHasKey('data', $actions);
        if (method_exists($this, 'isArray')) {
            $this->isArray($actions['data']);
        } else {
            $this->assertTrue(is_array($actions['data']), 'Is Array');
        }
        $data = $actions['data'];
        $this->assertArrayHasKey('items', $data);
        if (method_exists($this, 'isArray')) {
            $this->isArray($data['items']);
        } else {
            $this->assertTrue(is_array($data['items']), 'Is Array');
        }
        $items = $data['items'];
        $itemKeys = array_keys($items);
        $firstItem = $items[$itemKeys[0]];
        $this->assertArrayHasKey('actions', $firstItem);
        if (method_exists($this, 'isArray')) {
            $this->isArray($firstItem['actions']);
        } else {
            $this->assertTrue(is_array($firstItem['actions']), 'Is Array');
        }
        $actions = $firstItem['actions'];
        $this->assertArrayHasKey('history', $actions);
        $this->assertArrayNotHasKey('sync', $actions);
        $this->assertArrayNotHasKey('schedule', $actions);

        if (method_exists($this, 'isArray')) {
            $this->isArray($actions['history']);
        } else {
            $this->assertTrue(is_array($actions['history']), 'Is Array');
        }
        $history = $actions['history'];
        $this->assertArrayHasKey('callback', $history);
        $callback = $history['callback'];
        $callbackKeys = array_keys($callback);

        $callbacks = [
            $callbackKeys[0] => [
                'provider' => 'sync_product_listing.sync_product_listing' .
                    '.container.sync_product_history_modal.history.sync_product_history_listing',
                'target' => 'destroyInserted',
            ],
            $callbackKeys[1] => [
                'provider' => 'sync_product_listing.sync_product_listing' .
                    '.container.sync_product_history_modal.history.sync_product_history_listing',
                'target' => 'updateData',
            ],
            $callbackKeys[2] => [
                'provider' => 'sync_product_listing.sync_product_listing.container.sync_product_history_modal',
                'target' => 'openModal',
            ]
        ];
        foreach ($callbacks as $key => $expectedCallback) {
            $this->assertArrayHasKey($key, $callback);
            $currentCallback = $callback[$key];
            if (method_exists($this, 'isArray')) {
                $this->isArray($currentCallback);
            } else {
                $this->assertTrue(is_array($currentCallback), 'Is Array');
            }
            $this->assertArrayHasKey('provider', $currentCallback);
            $this->assertArrayHasKey('target', $currentCallback);
            $this->assertSame($expectedCallback['provider'], $currentCallback['provider']);
            $this->assertSame($expectedCallback['target'], $currentCallback['target']);
        }
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testReturnsSyncLinksForSimpleProductWhenSyncIsEnabled()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_simple_1');
        $store = $this->getStore('klevu_test_store_1');

        $this->mockContext->method('getRequestParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataSource = [];
        $dataSource['data'] = [];
        $dataSource['data']['items'] = [
            [
                History::FIELD_PRODUCT_ID => $product->getId(),
                History::FIELD_STORE_ID => $store->getId(),
                ProductInterface::TYPE_ID => $product->getTypeId(),
                'entity_id' => $product->getId(),
                'unique_entity_id' => '0-' . $product->getId() . '-0'
            ]
        ];

        $syncAction = $this->instantiateSyncActions();
        $actions = $syncAction->prepareDataSource($dataSource);

        $this->assertArrayHasKey('data', $actions);
        if (method_exists($this, 'isArray')) {
            $this->isArray($actions['data']);
        } else {
            $this->assertTrue(is_array($actions['data']), 'Is Array');
        }
        $data = $actions['data'];
        $this->assertArrayHasKey('items', $data);
        if (method_exists($this, 'isArray')) {
            $this->isArray($data['items']);
        } else {
            $this->assertTrue(is_array($data['items']), 'Is Array');
        }
        $items = $data['items'];
        $itemKeys = array_keys($items);
        $firstItem = $items[$itemKeys[0]];
        $this->assertArrayHasKey('actions', $firstItem);
        if (method_exists($this, 'isArray')) {
            $this->isArray($firstItem['actions']);
        } else {
            $this->assertTrue(is_array($firstItem['actions']), 'Is Array');
        }
        $actions = $firstItem['actions'];
        $this->assertArrayHasKey('history', $actions);
        $this->assertArrayHasKey('sync', $actions);
        $this->assertArrayHasKey('schedule', $actions);

        $this->assertArrayHasKey('href', $actions['sync']);
        $syncLink = $actions['sync']['href'];
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(SyncActions::URL_PATH_KLEVU_SYNC, $syncLink);
            $this->assertStringContainsString('id/0-' . $product->getId() . '-0', $syncLink);
            $this->assertStringContainsString('store/' . $store->getId(), $syncLink);
        } else {
            $this->assertContains(SyncActions::URL_PATH_KLEVU_SYNC, $syncLink);
            $this->assertContains('id/0-' . $product->getId(), $syncLink);
            $this->assertContains('store/' . $store->getId(), $syncLink);
        }

        $this->assertArrayHasKey('href', $actions['schedule']);
        $scheduleLink = $actions['schedule']['href'];
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(SyncActions::URL_PATH_KLEVU_SCHEDULE, $scheduleLink);
            $this->assertStringContainsString('id/0-' . $product->getId() . '-0', $scheduleLink);
            $this->assertStringContainsString('store/' . $store->getId(), $scheduleLink);
        } else {
            $this->assertContains(SyncActions::URL_PATH_KLEVU_SCHEDULE, $scheduleLink);
            $this->assertContains('id/0-' . $product->getId() . '-0', $scheduleLink);
            $this->assertContains('store/' . $store->getId(), $scheduleLink);
        }
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->mockContext = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->data = [
            'config' => [
                'component' => 'Magento_Ui/js/grid/columns/actions',
                'dataType' => 'actions',
                'indexField' => 'unique_entity_id',
                'label' => 'Actions',
                'sortOrder' => '200'
            ],
            'js_config' => [
                'extends ' => 'sync_product_listing'
            ],
            'name' => 'actions',
            'sortOrder' => '200'
        ];
    }

    /**
     * @return SyncActions
     */
    private function instantiateSyncActions()
    {
        return $this->objectManager->create(SyncActions::class, [
            'context' => $this->mockContext,
            'data' => $this->data
        ]);
    }

    /**
     * @param string $sku
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku)
    {
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        return $productRepository->get($sku);
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
     * Loads simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSimpleProductFixtures()
    {
        include __DIR__ . '/../../../../../_files/productFixtures.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSimpleProductFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/productFixtures_rollback.php';
    }

    /**
     * Loads simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadConfigurableProductFixtures()
    {
        include __DIR__ . '/../../../../../_files/productFixtures_configurableProduct.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadConfigurableProductFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/productFixtures_configurableProduct_rollback.php';
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/websiteFixtures_rollback.php';
    }
}
