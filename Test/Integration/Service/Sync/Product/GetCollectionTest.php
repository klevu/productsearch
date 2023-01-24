<?php

namespace Klevu\Search\Test\Integration\Service\Sync\Product;

use Klevu\Search\Api\Ui\DataProvider\Listing\Sync\GetCollectionInterface;
use Klevu\Search\Service\Sync\Product\GetCollection;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetCollectionTest extends TestCase
{
    /**
     * @var  ObjectManager
     */
    private $objectManager;
    /**
     * @var RequestInterface&MockObject
     */
    private $mockRequest;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $getCollectionService = $this->instantiateGetCollectionService();

        $this->assertInstanceOf(GetCollectionInterface::class, $getCollectionService);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadKlevuSimpleSyncFixtures
     * @magentoDataFixture loadSyncHistorySimpleFixtures
     */
    public function testSimpleProductsThatAreSyncedArePresentInData()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_simple_1');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $getCollectionService = $this->instantiateGetCollectionService();
        $collection = $getCollectionService->execute();

        $this->assertInstanceOf(Collection::class, $collection);

        $size = $collection->getSize();
        $this->assertSame(1, $size);

        $item = $collection->getFirstItem();
        $data = $item->toArray([]);

        $this->assertArrayHasKey('entity_id', $data);
        $this->assertSame((int)$product->getId(), (int)$data['entity_id'], 'Entity ID');

        $this->assertArrayHasKey('sync_row_id', $data);
        $this->assertNotNull($data['sync_row_id'], 'Row ID');

        $this->assertArrayHasKey('last_synced_at', $data);
        $this->assertNotNull($data['last_synced_at'], 'Synced At');

        $this->assertArrayHasKey('store_id', $data);
        $this->assertSame((int)$store->getId(), (int)$data['store_id'], 'Store ID');

        $this->assertArrayHasKey('product_id', $data);
        $this->assertSame((int)$product->getId(), (int)$data['product_id'], 'Product ID');

        $this->assertArrayHasKey('parent_id', $data);
        $this->assertSame(0, (int)$data['parent_id'], 'Parent ID');

        $expected = $data['product_id'] . '-' . $data['parent_id'];
        $this->assertSame($expected, $item->getId(), 'Get ID');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadConfigurableProductFixtures
     * @magentoDataFixture loadKlevuConfigurableSyncFixtures
     * @magentoDataFixture loadSyncHistoryConfigFixtures
     */
    public function testConfigurableProductsThatAreSyncedArePresentInData()
    {
        $this->setUpPhp5();

        $parentProduct = $this->getProduct('klevu_configurable_1');
        $childProduct1 = $this->getProduct('klevu_simple_child_1');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $getCollectionService = $this->instantiateGetCollectionService();
        $collection = $getCollectionService->execute();

        $this->assertInstanceOf(Collection::class, $collection);

        $size = $collection->getSize();
        // Size should include parent and two variants, plus variants as standalones (without sync data)
        $this->assertSame(5, $size);

        $items = $collection->getItems();

        $this->assertArrayHasKey($childProduct1->getId() . '-' . $parentProduct->getId(), $items);
        $child1 = $items[$childProduct1->getId() . '-' . $parentProduct->getId()];

        $this->assertArrayHasKey('entity_id', $child1);
        $this->assertSame((int)$child1->getId(), (int)$child1['entity_id'], 'Entity ID - Child');

        $this->assertArrayHasKey('sync_row_id', $child1);
        $this->assertNotNull($child1['sync_row_id'], 'Row ID - Child');

        $this->assertArrayHasKey('last_synced_at', $child1);
        $this->assertNotNull($child1['last_synced_at'], 'Synced At - Child');

        $this->assertArrayHasKey('store_id', $child1);
        $this->assertSame((int)$store->getId(), (int)$child1['store_id'], 'Store ID - Child');

        $this->assertArrayHasKey('product_id', $child1);
        $this->assertSame((int)$childProduct1->getId(), (int)$child1['product_id'], 'Parent ID - Child');

        $this->assertArrayHasKey('parent_id', $child1);
        $this->assertSame((int)$parentProduct->getId(), (int)$child1['parent_id'], 'Product ID - Child');

        $expected = $child1['product_id'] . '-' . $child1['parent_id'];
        $this->assertSame($expected, $child1->getId(), 'Get ID - Child');

        $this->assertArrayHasKey($parentProduct->getId() . '-0', $items);
        $parent = $items[$parentProduct->getId() . '-0'];

        $this->assertArrayHasKey('entity_id', $parent);
        $this->assertSame((int)$parent->getId(), (int)$parent['entity_id'], 'Entity ID - Parent');

        $this->assertArrayHasKey('sync_row_id', $parent);
        $this->assertNull($parent['sync_row_id'], 'Row ID - Parent');

        $this->assertArrayHasKey('last_synced_at', $parent);
        $this->assertNull($parent['last_synced_at'], 'Synced At - Parent');

        $this->assertArrayHasKey('store_id', $parent);
        $this->assertSame((int)$store->getId(), (int)$parent['store_id'], 'Store ID - Parent');

        $this->assertArrayHasKey('product_id', $parent);
        $this->assertNull($parent['product_id'], 'Product ID - Parent');

        $this->assertArrayHasKey('parent_id', $parent);
        $this->assertNull($parent['parent_id'], 'Parent ID - Parent');

        $expected = $parent['entity_id'] . '-0';
        $this->assertSame($expected, $parent->getId(), 'Get ID - Parent');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadConfigurableProductFixtures
     */
    public function testSimpleAndConfigurableProductsArePresentInDataWhenNotSynced()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_simple_1');
        $parentProduct = $this->getProduct('klevu_configurable_1');
        $childProduct1 = $this->getProduct('klevu_simple_child_1');

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $getCollectionService = $this->instantiateGetCollectionService();
        $collection = $getCollectionService->execute();

        $this->assertInstanceOf(Collection::class, $collection);

        $size = $collection->getSize();
        // Size should include simple, configurable, and two variants; plus variants as standalones (without sync data)
        $this->assertSame(6, $size, 'Collection Size');

        $items = $collection->getItems();
        // simple product
        $this->assertArrayHasKey($childProduct1->getId() . '-0', $items);
        $simple = $items[$product->getId() . '-0'];
        $this->assertArrayHasKey('sync_row_id', $simple);
        $this->assertNull($simple['sync_row_id'], 'Row ID');
        $this->assertArrayHasKey('last_synced_at', $simple);
        $this->assertNull($simple['last_synced_at'], 'Synced At');
        $this->assertArrayHasKey('store_id', $simple);
        $this->assertSame((int)$store->getId(), (int)$simple['store_id'], 'Store ID');
        $this->assertArrayHasKey('entity_id', $simple);
        $this->assertSame((int)$product->getId(), (int)$simple['entity_id'], 'Entity ID');
        $expected = $simple['entity_id'] . '-0';
        $this->assertSame($expected, $simple->getId(), 'Get ID');

        // simple child of configurable as child of configurable
        $this->assertArrayHasKey($childProduct1->getId() . '-' . $parentProduct->getId(), $items);
        $child1 = $items[$childProduct1->getId() . '-' . $parentProduct->getId()];
        $this->assertArrayHasKey('entity_id', $child1);
        $this->assertSame((int)$child1->getId(), (int)$child1['entity_id'], 'Entity ID - Child');
        $this->assertArrayHasKey('sync_row_id', $child1);
        $this->assertNull($child1['sync_row_id'], 'Row ID - Child');
        $this->assertArrayHasKey('last_synced_at', $child1);
        $this->assertNull($child1['last_synced_at'], 'Synced At - Child');
        $this->assertArrayHasKey('store_id', $child1);
        $this->assertSame((int)$store->getId(), (int)$child1['store_id'], 'Store ID - Child');
        $this->assertArrayHasKey('product_id', $child1);
        $this->assertNull($child1['product_id'], 'Product ID - Child');
        $this->assertArrayHasKey('product_parent_id', $child1);
        $this->assertSame((int)$parentProduct->getId(), (int)$child1['product_parent_id'], 'Magento Parent ID - Child');
        $this->assertArrayHasKey('parent_id', $child1);
        $this->assertNull($child1['parent_id'], 'Klevu Parent ID - Child');
        $expected = $child1['entity_id'] . '-' . $child1['product_parent_id'];
        $this->assertSame($expected, $child1->getId(), 'Get ID - Child');

        // simple child of configurable as standalone product
        $this->assertArrayHasKey($childProduct1->getId() . '-0', $items);
        $child1Simple = $items[$childProduct1->getId() . '-0'];
        $this->assertArrayHasKey('entity_id', $child1Simple);
        $this->assertSame((int)$child1Simple->getId(), (int)$child1Simple['entity_id'], 'Entity ID - Child');
        $this->assertArrayHasKey('sync_row_id', $child1Simple);
        $this->assertNull($child1Simple['sync_row_id'], 'Row ID - Child');
        $this->assertArrayHasKey('last_synced_at', $child1Simple);
        $this->assertNull($child1Simple['last_synced_at'], 'Synced At - Child');
        $this->assertArrayHasKey('store_id', $child1Simple);
        $this->assertSame((int)$store->getId(), (int)$child1Simple['store_id'], 'Store ID - Child');
        $this->assertArrayHasKey('product_id', $child1Simple);
        $this->assertNull($child1Simple['product_id'], 'Product ID - Child');
        $this->assertArrayHasKey('product_parent_id', $child1Simple);
        $this->assertSame(0, (int)$child1Simple['product_parent_id'], 'Magento Parent ID - Child');
        $this->assertArrayHasKey('parent_id', $child1Simple);
        $this->assertNull($child1Simple['parent_id'], 'Klevu Parent ID - Child');
        $expected = $child1Simple['entity_id'] . '-0';
        $this->assertSame($expected, $child1Simple->getId(), 'Get ID - Child');

        // configurable
        $this->assertArrayHasKey($parentProduct->getId() . '-0', $items);
        $parent = $items[$parentProduct->getId() . '-0'];
        $this->assertArrayHasKey('entity_id', $parent);
        $this->assertSame((int)$parent->getId(), (int)$parent['entity_id'], 'Entity ID - Parent');
        $this->assertArrayHasKey('sync_row_id', $parent);
        $this->assertNull($parent['sync_row_id'], 'Row ID - Parent');
        $this->assertArrayHasKey('last_synced_at', $parent);
        $this->assertNull($parent['last_synced_at'], 'Synced At - Parent');
        $this->assertArrayHasKey('store_id', $parent);
        $this->assertSame((int)$store->getId(), (int)$parent['store_id'], 'Store ID - Parent');
        $this->assertArrayHasKey('product_id', $parent);
        $this->assertNull($parent['product_id'], 'Product ID - Parent');
        $this->assertArrayHasKey('product_parent_id', $parent);
        $this->assertSame(0, (int)$parent['product_parent_id'], 'Parent ID - Parent');
        $this->assertArrayHasKey('parent_id', $child1);
        $this->assertNull($child1['parent_id'], 'Klevu Parent ID - Parent');
        $expected = $parent['entity_id'] . '-0';
        $this->assertSame($expected, $parent->getId(), 'Get ID - Parent');
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadConfigurableProductFixtures
     * @magentoDataFixture loadKlevuSimpleSyncFixtures
     * @magentoDataFixture loadKlevuConfigurableSyncFixtures
     * @magentoDataFixture loadSyncHistorySimpleFixtures
     * @magentoDataFixture loadSyncHistoryConfigFixtures
     */
    public function testSyncedDataAppearsInDataWhenProductsNotPresent()
    {
        $this->setUpPhp5();

        $simpleProduct = $this->getProduct('klevu_simple_1');
        $parentProduct = $this->getProduct('klevu_configurable_1');
        $childProduct1 = $this->getProduct('klevu_simple_child_1');

        static::loadConfigurableProductFixturesRollback();
        static::loadSimpleProductFixturesRollback();

        $store = $this->getStore('klevu_test_store_1');
        $this->mockRequest->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $getCollectionService = $this->instantiateGetCollectionService();
        $collection = $getCollectionService->execute();

        $this->assertInstanceOf(Collection::class, $collection);

        $items = $collection->getItems();
        $this->assertSame(3, count($items), 'Collection Size');

        // simple product
        $this->assertArrayHasKey($simpleProduct->getId() . '-0', $items);
        $simple = $items[$simpleProduct->getId() . '-0'];
        $this->assertArrayHasKey('sync_row_id', $simple);
        $this->assertNotNull($simple['sync_row_id'], 'Row ID');
        $this->assertArrayHasKey('last_synced_at', $simple);
        $this->assertNotNull($simple['last_synced_at'], 'Synced At');
        $this->assertArrayHasKey('store_id', $simple);
        $this->assertSame((int)$store->getId(), (int)$simple['store_id'], 'Store ID');
        $this->assertArrayHasKey('entity_id', $simple);
        $this->assertSame((int)$simpleProduct->getId(), (int)$simple['product_id'], 'Entity ID');
        $expected = $simple['product_id'] . '-0';
        $this->assertSame($expected, $simple->getId(), 'Get ID');

        // configurable - is not stored in sync table and has been deleted from magento
        $this->assertArrayNotHasKey($parentProduct->getId() . '-0', $items);

        // child product
        $this->assertArrayHasKey($childProduct1->getId() . '-' . $parentProduct->getId(), $items);
        $child = $items[$childProduct1->getId() . '-' . $parentProduct->getId()];
        $this->assertArrayHasKey('sync_row_id', $child);
        $this->assertNotNull($child['sync_row_id'], 'Row ID');
        $this->assertArrayHasKey('last_synced_at', $child);
        $this->assertNotNull($child['last_synced_at'], 'Synced At');
        $this->assertArrayHasKey('store_id', $child);
        $this->assertSame((int)$store->getId(), (int)$child['store_id'], 'Store ID');
        $this->assertArrayHasKey('entity_id', $child);
        $this->assertSame((int)$childProduct1->getId(), (int)$child['product_id'], 'Entity ID');
        $expected = $child['product_id'] . '-' . $child['parent_id'];
        $this->assertSame($expected, $child->getId(), 'Get ID');
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
     * @return GetCollection
     */
    private function instantiateGetCollectionService()
    {
        return $this->objectManager->create(GetCollection::class, [
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
    public static function loadKlevuSimpleSyncFixtures()
    {
        include __DIR__ . '/../../../_files/klevuProductSyncFixtures.php';
    }

    /**
     * Rolls back klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuSyncFixturesRollback()
    {
        include __DIR__ . '/../../../_files/klevuProductSyncFixtures_rollback.php';
    }

    /**
     * Loads klevu sync configurable product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuConfigurableSyncFixtures()
    {
        include __DIR__ . '/../../../_files/klevuProductConfigurableSyncFixtures.php';
    }

    /**
     * Rolls back klevu sync configurable product  creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuConfigurableSyncFixturesRollback()
    {
        include __DIR__ . '/../../../_files/klevuProductConfigurableSyncFixtures_rollback.php';
    }

    /**
     * Loads simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSimpleProductFixtures()
    {
        include __DIR__ . '/../../../_files/productFixtures.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSimpleProductFixturesRollback()
    {
        include __DIR__ . '/../../../_files/productFixtures_rollback.php';
    }

    /**
     * Loads configurable product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadConfigurableProductFixtures()
    {
        include __DIR__ . '/../../../_files/productFixtures_configurableProduct.php';
    }

    /**
     * Rolls back configurable product  creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadConfigurableProductFixturesRollback()
    {
        include __DIR__ . '/../../../_files/productFixtures_configurableProduct_rollback.php';
    }

    /**
     * Loads sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistorySimpleFixtures()
    {
        include __DIR__ . '/../../../_files/syncHistoryFixtures.php';
    }

    /**
     * Rolls back sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistorySimpleFixturesRollback()
    {
        include __DIR__ . '/../../../_files/syncHistoryFixtures_rollback.php';
    }

    /**
     * Loads sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryConfigFixtures()
    {
        include __DIR__ . '/../../../_files/syncHistoryFixtures_configurableProduct.php';
    }

    /**
     * Rolls back sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryConfigFixturesRollback()
    {
        include __DIR__ . '/../../../_files/syncHistoryFixtures_configurableProduct_rollback.php';
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
