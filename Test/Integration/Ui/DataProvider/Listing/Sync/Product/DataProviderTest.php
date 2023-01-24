<?php

namespace Klevu\Search\Test\Integration\Ui\DataProvider\Listing\Sync\Product;

use Klevu\Search\Model\Source\NextAction;
use Klevu\Search\Service\Sync\Product\GetCollection;
use Klevu\Search\Ui\DataProvider\Listing\Sync\Product\DataProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
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
class DataProviderTest extends TestCase
{
    /**
     * @var  ObjectManager
     */
    private $objectManager;
    /**
     * @var RequestInterface&MockObject
     */
    private $mockRequest;

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadKlevuSimpleSyncFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 0
     */
    public function testNothingReturnedWhenNotAtStoreLevel()
    {
        $this->setUpPhp5();

        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn(null);

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 0;

        $this->assertArrayHasKey('totalRecords', $data);
        $this->assertSame($expectedCount, $data['totalRecords']);

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadKlevuSimpleSyncFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 0
     */
    public function testNothingReturnedWhenStoreNotIntegrated()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 0;

        $this->assertArrayHasKey('totalRecords', $data);
        $this->assertSame($expectedCount, $data['totalRecords']);

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadKlevuSimpleSyncFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 0
     */
    public function testNothingReturnedWhenSyncIsDisabled()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 0;

        $this->assertArrayHasKey('totalRecords', $data);
        $this->assertSame($expectedCount, $data['totalRecords']);

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadKlevuSimpleSyncFixtures
     * @magentoDataFixture loadSyncHistorySimpleFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testSimpleProductWithNextActionUpdate()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_simple_1');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 1;

        $this->assertArrayHasKey('totalRecords', $data);
        $this->assertSame($expectedCount, $data['totalRecords']);

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $key = array_keys($data['items']);
        $item = $data['items'][$key[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNotNull($item['sync_row_id'], 'Row Id');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNotNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertSame((int)$product->getId(), (int)$item['product_id']);

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertSame(0, (int)$item['parent_id']);

        $this->assertArrayHasKey('next_action', $item);
        $this->assertSame(NextAction::ACTION_VALUE_UPDATE, (int)$item['next_action'], 'Next Action Update');

        $this->assertArrayHasKey('type_id', $item);
        $this->assertSame(Type::TYPE_SIMPLE, $item['type_id'], 'Product Type');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-' . $item['sync_row_id'], $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testSimpleProductWithNextActionAdd()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_simple_1');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 1;

        $this->assertArrayHasKey('totalRecords', $data);
        $this->assertSame($expectedCount, $data['totalRecords']);

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $key = array_keys($data['items']);
        $item = $data['items'][$key[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNull($item['sync_row_id'], 'Row ID');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertNull($item['product_id'], 'Product ID');

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertNull($item['parent_id'], 'Parent ID');

        $this->assertArrayHasKey('next_action', $item);
        $this->assertSame(NextAction::ACTION_VALUE_ADD, (int)$item['next_action'], 'Next Action Add');

        $this->assertArrayHasKey('type_id', $item);
        $this->assertSame(Type::TYPE_SIMPLE, $item['type_id'], 'Product Type');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-0', $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadKlevuSimpleSyncLastSyncedNowFixtures
     * @magentoDataFixture loadSyncHistorySimpleFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testSimpleProductWithNextActionNull()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_simple_1');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 1;

        $this->assertArrayHasKey('totalRecords', $data);
        $this->assertSame($expectedCount, $data['totalRecords']);

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $key = array_keys($data['items']);
        $item = $data['items'][$key[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNotNull($item['sync_row_id'], 'Row Id');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNotNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertSame((int)$product->getId(), (int)$item['product_id']);

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertSame(0, (int)$item['parent_id']);

        $this->assertArrayHasKey('next_action', $item);
        $this->assertNull($item['next_action'], 'Next Action NUll');

        $this->assertArrayHasKey('type_id', $item);
        $this->assertSame(Type::TYPE_SIMPLE, $item['type_id'], 'Product Type');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-' . $item['sync_row_id'], $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleDisabledProductFixtures
     * @magentoDataFixture loadKlevuSimpleSyncFixtures
     * @magentoDataFixture loadSyncHistorySimpleFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testSimpleProductWithNextActionDelete()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_simple_1');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 1;

        $this->assertArrayHasKey('totalRecords', $data);
        $this->assertSame($expectedCount, $data['totalRecords']);

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $key = array_keys($data['items']);
        $item = $data['items'][$key[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNotNull($item['sync_row_id'], 'Row Id');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNotNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertSame((int)$product->getId(), (int)$item['product_id']);

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertSame(0, (int)$item['parent_id']);

        $this->assertArrayHasKey('next_action', $item);
        $this->assertSame(NextAction::ACTION_VALUE_DELETE, (int)$item['next_action'], 'Next Action Delete');

        $this->assertArrayHasKey('type_id', $item);
        $this->assertSame(Type::TYPE_SIMPLE, $item['type_id'], 'Product Type');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-' . $item['sync_row_id'], $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadConfigurableProductFixtures
     * @magentoDataFixture loadKlevuConfigurableSyncFixtures
     * @magentoDataFixture loadSyncHistoryConfigFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testConfigurableProduct()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_configurable_1');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 5;

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $filteredItems = array_filter($data['items'], static function ($item) use ($product) {
            return isset($item['entity_id']) && ((int)$item['entity_id'] === (int)$product->getId());
        });
        $keys = array_keys($filteredItems);
        $item = $filteredItems[$keys[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNull($item['sync_row_id'], 'Row ID');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertNull($item['product_id'], 'Product ID');

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertNull($item['parent_id'], 'Parent ID');

        $this->assertArrayHasKey('next_action', $item);
        $this->assertNull($item['next_action'], 'Next Action NUll');

        $this->assertArrayHasKey('type_id', $item);
        $this->assertSame(Configurable::TYPE_CODE, $item['type_id'], 'Product Type');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-0', $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadConfigurableProductFixtures
     * @magentoDataFixture loadKlevuConfigurableSyncFixtures
     * @magentoDataFixture loadSyncHistoryConfigFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testConfigurableChildDisplayNewProductType()
    {
        $this->setUpPhp5();

        $parentProduct = $this->getProduct('klevu_configurable_1');
        $childProduct = $this->getProduct('klevu_simple_child_1');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 5;

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $filteredParentItem = array_filter($data['items'], static function ($item) use ($parentProduct) {
            return isset($item['entity_id']) && ((int)$item['entity_id'] === (int)$parentProduct->getId());
        });
        $keys = array_keys($filteredParentItem);
        $item = $filteredParentItem[$keys[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNull($item['sync_row_id'], 'Row ID');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertNull($item['product_id'], 'Product ID');

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertNull($item['parent_id'], 'Parent ID');

        $this->assertArrayHasKey('next_action', $item);
        $this->assertNull($item['next_action'], 'Next Action NUll');

        $this->assertArrayHasKey('type_id', $item);
        $this->assertSame(Configurable::TYPE_CODE, $item['type_id'], 'Product Type');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $parentProduct->getId() . '-0', $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $parentProduct->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $parentProduct->getId(), $item['link']);
        }

        $filteredChildItem = array_filter($data['items'], static function ($item) use ($childProduct) {
            return isset($item['entity_id']) &&
                (int)$item['entity_id'] === (int)$childProduct->getId() &&
                null !== $item['parent_id'];
        });
        $this->assertCount(1, $filteredChildItem);
        $keys = array_keys($filteredChildItem);
        $childItem = $filteredChildItem[$keys[0]];

        $this->assertArrayHasKey('sync_row_id', $childItem);
        $this->assertNotNull($childItem['sync_row_id'], 'Row ID');

        $this->assertArrayHasKey('last_synced_at', $childItem);
        $this->assertNotNull($childItem['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $childItem);
        $this->assertSame((int)$childProduct->getId(), (int)$childItem['product_id'], 'Product ID');

        $this->assertArrayHasKey('parent_id', $childItem);
        $this->assertSame((int)$parentProduct->getId(), (int)$childItem['parent_id'], 'Parent ID');

        $this->assertArrayHasKey('next_action', $childItem);
        $this->assertSame(NextAction::ACTION_VALUE_UPDATE, $childItem['next_action'], 'Next Action Update');

        $this->assertArrayHasKey('type_id', $childItem);
        $this->assertSame(Type::TYPE_SIMPLE, $childItem['type_id'], 'Product Type');

        $this->assertArrayHasKey('unique_entity_id', $childItem);
        $this->assertSame(
            $parentProduct->getId() . '-' . $childProduct->getId() . '-' . $childItem['sync_row_id'],
            $childItem['unique_entity_id']
        );

        $this->assertArrayHasKey('link', $childItem);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $childItem['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $childItem['link']);
            $this->assertStringContainsString('/id/' . $childProduct->getId(), $childItem['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $childItem['link']);
            $this->assertcontains('/store/' . $store->getId(), $childItem['link']);
            $this->assertcontains('/id/' . $childProduct->getId(), $childItem['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadBundleProductFixtures
     * @magentoDataFixture loadKlevuBundleSyncFixtures
     * @magentoDataFixture loadSyncHistoryBundleFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testBundleProductWithNextActionUpdate()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_bundle_product_test');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 3;

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $filteredItems = array_filter($data['items'], static function ($item) use ($product) {
            return isset($item['entity_id']) && ((int)$item['entity_id'] === (int)$product->getId());
        });
        $this->assertCount(1, $filteredItems);
        $keys = array_keys($filteredItems);
        $item = $filteredItems[$keys[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNotNull($item['sync_row_id'], 'Row Id');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNotNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertSame((int)$product->getId(), (int)$item['product_id']);

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertSame(0, (int)$item['parent_id']);

        $this->assertArrayHasKey('next_action', $item);
        $this->assertSame(NextAction::ACTION_VALUE_UPDATE, (int)$item['next_action'], 'Next Action Update');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-' . $item['sync_row_id'], $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadBundleProductFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testBundleProductWithNextActionAdd()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_bundle_product_test');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 2;

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $filteredItems = array_filter($data['items'], static function ($item) use ($product) {
            return isset($item['entity_id']) && ((int)$item['entity_id'] === (int)$product->getId());
        });
        $this->assertCount(1, $filteredItems);
        $keys = array_keys($filteredItems);
        $item = $filteredItems[$keys[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNull($item['sync_row_id'], 'Row ID');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertNull($item['product_id'], 'Product ID');

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertNull($item['parent_id'], 'Parent ID');

        $this->assertArrayHasKey('next_action', $item);
        $this->assertSame(NextAction::ACTION_VALUE_ADD, (int)$item['next_action'], 'Next Action Add');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-0', $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadBundleDisabledProductFixtures
     * @magentoDataFixture loadKlevuBundleSyncFixtures
     * @magentoDataFixture loadSyncHistoryBundleFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testBundleProductWithNextActionDelete()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_bundle_product_test');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 3;

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $filteredItems = array_filter($data['items'], static function ($item) use ($product) {
            return isset($item['entity_id']) && ((int)$item['entity_id'] === (int)$product->getId());
        });
        $this->assertCount(1, $filteredItems);
        $keys = array_keys($filteredItems);
        $item = $filteredItems[$keys[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNotNull($item['sync_row_id'], 'Row Id');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNotNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertSame((int)$product->getId(), (int)$item['product_id']);

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertSame(0, (int)$item['parent_id']);

        $this->assertArrayHasKey('next_action', $item);
        $this->assertSame(NextAction::ACTION_VALUE_DELETE, (int)$item['next_action'], 'Next Action Delete');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-' . $item['sync_row_id'], $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadBundleProductFixtures
     * @magentoDataFixture loadKlevuBundleSyncLastSyncedNowFixtures
     * @magentoDataFixture loadSyncHistoryBundleFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testBundleProductWithNextActionNull()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_bundle_product_test');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 3;

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $filteredItems = array_filter($data['items'], static function ($item) use ($product) {
            return isset($item['entity_id']) && ((int)$item['entity_id'] === (int)$product->getId());
        });
        $this->assertCount(1, $filteredItems);
        $keys = array_keys($filteredItems);
        $item = $filteredItems[$keys[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNotNull($item['sync_row_id'], 'Row Id');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNotNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertSame((int)$product->getId(), (int)$item['product_id']);

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertSame(0, (int)$item['parent_id']);

        $this->assertArrayHasKey('next_action', $item);
        $this->assertNull($item['next_action'], 'Next Action NUll');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-' . $item['sync_row_id'], $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadGroupedProductFixtures
     * @magentoDataFixture loadKlevuGroupedSyncFixtures
     * @magentoDataFixture loadSyncHistoryGroupedFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testGroupedProductWithNextActionUpdate()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_grouped_product_test');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 3;

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $filteredItems = array_filter($data['items'], static function ($item) use ($product) {
            return isset($item['entity_id']) && ((int)$item['entity_id'] === (int)$product->getId());
        });
        $this->assertCount(1, $filteredItems);
        $keys = array_keys($filteredItems);
        $item = $filteredItems[$keys[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNotNull($item['sync_row_id'], 'Row ID');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNotNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertSame((int)$product->getId(), (int)$item['product_id']);

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertSame(0, (int)$item['parent_id']);

        $this->assertArrayHasKey('next_action', $item);
        $this->assertSame(NextAction::ACTION_VALUE_UPDATE, (int)$item['next_action'], 'Next Action Update');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-' . $item['sync_row_id'], $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadGroupedProductFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testGroupedProductWithNextActionAdd()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_grouped_product_test');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 2;

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $filteredItems = array_filter($data['items'], static function ($item) use ($product) {
            return isset($item['entity_id']) && ((int)$item['entity_id'] === (int)$product->getId());
        });
        $this->assertCount(1, $filteredItems);
        $keys = array_keys($filteredItems);
        $item = $filteredItems[$keys[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNull($item['sync_row_id'], 'Row ID');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertNull($item['product_id'], 'Product ID');

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertNull($item['parent_id'], 'Parent ID');

        $this->assertArrayHasKey('next_action', $item);
        $this->assertSame(NextAction::ACTION_VALUE_ADD, (int)$item['next_action'], 'Next Action Add');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-0', $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadGroupedDisabledProductFixtures
     * @magentoDataFixture loadKlevuGroupedSyncFixtures
     * @magentoDataFixture loadSyncHistoryGroupedFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testGroupedProductWithNextActionDelete()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_grouped_product_test');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 3;

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $filteredItems = array_filter($data['items'], static function ($item) use ($product) {
            return isset($item['entity_id']) && ((int)$item['entity_id'] === (int)$product->getId());
        });
        $this->assertCount(1, $filteredItems);
        $keys = array_keys($filteredItems);
        $item = $filteredItems[$keys[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNotNull($item['sync_row_id'], 'Row Id');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNotNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertSame((int)$product->getId(), (int)$item['product_id']);

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertSame(0, (int)$item['parent_id']);

        $this->assertArrayHasKey('next_action', $item);
        $this->assertSame(NextAction::ACTION_VALUE_DELETE, (int)$item['next_action'], 'Next Action Delete');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-' . $item['sync_row_id'], $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadGroupedProductFixtures
     * @magentoDataFixture loadKlevuGroupedSyncLastSyncedNowFixtures
     * @magentoDataFixture loadSyncHistoryGroupedFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testGroupedProductWithNextActionNull()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_grouped_product_test');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        $expectedCount = 3;

        $this->assertArrayHasKey('items', $data);
        $this->assertCount($expectedCount, $data['items']);

        $filteredItems = array_filter($data['items'], static function ($item) use ($product) {
            return isset($item['entity_id']) && ((int)$item['entity_id'] === (int)$product->getId());
        });
        $this->assertCount(1, $filteredItems);
        $keys = array_keys($filteredItems);
        $item = $filteredItems[$keys[0]];

        $this->assertArrayHasKey('sync_row_id', $item);
        $this->assertNotNull($item['sync_row_id'], 'Row Id');

        $this->assertArrayHasKey('last_synced_at', $item);
        $this->assertNotNull($item['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('product_id', $item);
        $this->assertSame((int)$product->getId(), (int)$item['product_id']);

        $this->assertArrayHasKey('parent_id', $item);
        $this->assertSame(0, (int)$item['parent_id']);

        $this->assertArrayHasKey('next_action', $item);
        $this->assertNull($item['next_action'], 'Next Action NUll');

        $this->assertArrayHasKey('unique_entity_id', $item);
        $this->assertSame('0-' . $product->getId() . '-' . $item['sync_row_id'], $item['unique_entity_id']);

        $this->assertArrayHasKey('link', $item);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertStringContainsString('/store/' . $store->getId(), $item['link']);
            $this->assertStringContainsString('/id/' . $product->getId(), $item['link']);
        } else {
            $this->assertcontains(DataProvider::CATALOG_PRODUCT_EDIT_ROUTE, $item['link']);
            $this->assertcontains('/store/' . $store->getId(), $item['link']);
            $this->assertcontains('/id/' . $product->getId(), $item['link']);
        }

        static::loadWebsiteFixturesRollback();
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
     * @return DataProvider
     */
    private function instantiateDataProviderService()
    {
        $collection = $this->objectManager->create(GetCollection::class, [
            'request' => $this->mockRequest
        ]);

        return $this->objectManager->create(DataProvider::class, [
            'name' => 'sync_product_listing_data_source',
            'primaryFieldName' => 'unique_entity_id',
            'requestFieldName' => 'id',
            'getCollection' => $collection,
            'request' => $this->mockRequest
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
     * Loads klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuSimpleSyncLastSyncedNowFixtures()
    {
        include __DIR__ . '/../../../../../_files/klevuProductSync_lastSyncedNowFixtures.php';
    }

    /**
     * Rolls back klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuSimpleSyncLastSyncedNowFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/klevuProductSync_lastSyncedNowFixtures_rollback.php';
    }

    /**
     * Loads klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuBundleSyncLastSyncedNowFixtures()
    {
        include __DIR__ . '/../../../../../_files/klevuProductBundleSync_lastSyncedNowFixtures.php';
    }

    /**
     * Rolls back klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuBundleSyncLastSyncedNowFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/klevuProductBundleSync_lastSyncedNowFixtures_rollback.php';
    }

    /**
     * Loads klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuGroupedSyncLastSyncedNowFixtures()
    {
        include __DIR__ . '/../../../../../_files/klevuProductGroupedSync_lastSyncedNowFixtures.php';
    }

    /**
     * Rolls back klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuGroupedSyncLastSyncedNowFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/klevuProductGroupedSync_lastSyncedNowFixtures_rollback.php';
    }

    /**
     * Loads klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuSimpleSyncFixtures()
    {
        include __DIR__ . '/../../../../../_files/klevuProductSyncFixtures.php';
    }

    /**
     * Rolls back klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuSimpleSyncFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/klevuProductSyncFixtures_rollback.php';
    }

    /**
     * Loads klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuBundleSyncFixtures()
    {
        include __DIR__ . '/../../../../../_files/klevuProductBundleSyncFixtures.php';
    }

    /**
     * Rolls back klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuBundleSyncFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/klevuProductBundleSyncFixtures_rollback.php';
    }

    /**
     * Loads klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuGroupedSyncFixtures()
    {
        include __DIR__ . '/../../../../../_files/klevuProductGroupedSyncFixtures.php';
    }

    /**
     * Rolls back klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuGroupedSyncFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/klevuProductGroupedSyncFixtures_rollback.php';
    }

    /**
     * Loads klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuConfigurableSyncFixtures()
    {
        include __DIR__ . '/../../../../../_files/klevuProductConfigurableSyncFixtures.php';
    }

    /**
     * Rolls back klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuConfigurableSyncFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/klevuProductConfigurableSyncFixtures_rollback.php';
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
    public static function loadSimpleDisabledProductFixtures()
    {
        include __DIR__ . '/../../../../../_files/productDisabledFixtures.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSimpleDisabledProductFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/productDisabledFixtures_rollback.php';
    }

    /**
     * Loads simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadBundleProductFixtures()
    {
        include __DIR__ . '/../../../../../_files/productFixtures_bundleProduct.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadBundleProductFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/productFixtures_bundleProduct_rollback.php';
    }

    /**
     * Loads simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadBundleDisabledProductFixtures()
    {
        include __DIR__ . '/../../../../../_files/productFixtures_bundleProduct_disabled.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadBundleDisabledProductFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/productFixtures_bundleProduct_disabled_rollback.php';
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
     * Loads simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadGroupedProductFixtures()
    {
        include __DIR__ . '/../../../../../_files/productFixtures_groupedProduct.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadGroupedProductFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/productFixtures_groupedProduct_rollback.php';
    }

    /**
     * Loads simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadGroupedDisabledProductFixtures()
    {
        include __DIR__ . '/../../../../../_files/productFixtures_groupedProduct_disabled.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadGroupedDisabledProductFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/productFixtures_groupedProduct_disabled_rollback.php';
    }

    /**
     * Loads sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistorySimpleFixtures()
    {
        include __DIR__ . '/../../../../../_files/syncHistoryFixtures.php';
    }

    /**
     * Rolls back sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistorySimpleFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/syncHistoryFixtures_rollback.php';
    }

    /**
     * Loads sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryConfigFixtures()
    {
        include __DIR__ . '/../../../../../_files/syncHistoryFixtures_configurableProduct.php';
    }

    /**
     * Rolls back sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryConfigFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/syncHistoryFixtures_configurableProduct_rollback.php';
    }

    /**
     * Loads sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryBundleFixtures()
    {
        include __DIR__ . '/../../../../../_files/syncHistoryFixtures_bundleProduct.php';
    }

    /**
     * Rolls back sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryBundleFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/syncHistoryFixtures_bundleProduct_rollback.php';
    }

    /**
     * Loads sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryGroupedFixtures()
    {
        include __DIR__ . '/../../../../../_files/syncHistoryFixtures_groupedProduct.php';
    }

    /**
     * Rolls back sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryGroupedFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/syncHistoryFixtures_groupedProduct_rollback.php';
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
