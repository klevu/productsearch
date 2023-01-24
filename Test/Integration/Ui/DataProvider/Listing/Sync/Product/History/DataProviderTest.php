<?php

namespace Klevu\Search\Test\Integration\Ui\DataProvider\Listing\Sync\Product\History;

use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Model\Product\Sync\ResourceModel\History as HistoryResourceModel;
use Klevu\Search\Ui\DataProvider\Listing\Sync\Product\History\DataProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface&MockObject
     */
    private $mockLogger;

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @dataProvider invalidStoreIdDataProvider
     */
    public function testEmptyDataReturnedIfStoreIdIsInvalid($invalidStore)
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_simple_1');

        $this->mockRequest->method('getParam')
            ->willReturnCallback(static function ($param) use ($product, $invalidStore) {
                switch ($param) {
                    case 'store':
                        return $invalidStore;
                    case 'unique_entity_id':
                        return '0-' . $product->getId() . '-0';
                    default:
                        return null;
                }
            });

        $errorMessage = sprintf(
            'Invalid Store ID provided. Expected string or int, received %s',
            is_object($invalidStore) ? get_class($invalidStore) : gettype($invalidStore) // phpcs:ignore
        );
        $this->mockLogger->expects($this->once())
            ->method('error')
        ->with($errorMessage);

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($data);
        } else {
            $this->assertTrue(is_array($data));
        }
        $this->assertArrayHasKey('items', $data);
        $this->assertCount(0, $data['items']);

        $this->assertArrayHasKey('totalRecords', $data);
        $this->assertSame(0, $data['totalRecords']);
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     */
    public function testEmptyDataReturnedIfStoreIdProvidedDoesNotExist()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_simple_1');

        $this->mockRequest->method('getParam')
            ->willReturnCallback(static function ($param) use ($product) {
                switch ($param) {
                    case 'store':
                        return 999999999999999999;
                    case 'unique_entity_id':
                        return '0-' . $product->getId() . '-0';
                    default:
                        return null;
                }
            });
        
        $this->mockLogger->expects($this->once())
            ->method('error');

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($data);
        } else {
            $this->assertTrue(is_array($data));
        }
        $this->assertArrayHasKey('items', $data);
        $this->assertCount(0, $data['items']);

        $this->assertArrayHasKey('totalRecords', $data);
        $this->assertSame(0, $data['totalRecords']);
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @dataProvider invalidEntityIdDataProvider
     */
    public function testEmptyDataReturnedIfUniqueEntityIdIsInvalid($invalidEntityId)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $this->mockRequest->method('getParam')
            ->willReturnCallback(static function ($param) use ($store, $invalidEntityId) {
                switch ($param) {
                    case 'store':
                        return $store->getId();
                    case 'unique_entity_id':
                        return $invalidEntityId;
                    default:
                        return null;
                }
            });

        $errorMessage = sprintf(
            'Invalid unique_entity_id provided. Expected string or int, received %s',
            is_object($invalidEntityId) ? get_class($invalidEntityId) : gettype($invalidEntityId) // phpcs:ignore
        );
        $this->mockLogger->expects($this->atLeastOnce())
            ->method('error')
            ->with($errorMessage);

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($data);
        } else {
            $this->assertTrue(is_array($data));
        }
        $this->assertArrayHasKey('items', $data);
        $this->assertCount(0, $data['items']);

        $this->assertArrayHasKey('totalRecords', $data);
        $this->assertSame(0, $data['totalRecords']);
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @dataProvider invalidProductIdDataProvider
     */
    public function testEmptyDataReturnedIfProductIdIsInvalid($invalidProductId)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $this->mockRequest->method('getParam')
            ->willReturnCallback(static function ($param) use ($store, $invalidProductId) {
                switch ($param) {
                    case 'store':
                        return $store->getId();
                    case 'unique_entity_id':
                        return '0-' . $invalidProductId;
                    default:
                        return null;
                }
            });

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($data);
        } else {
            $this->assertTrue(is_array($data));
        }
        $this->assertArrayHasKey('items', $data);
        $this->assertCount(0, $data['items']);

        $this->assertArrayHasKey('totalRecords', $data);
        $this->assertSame(0, $data['totalRecords']);
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadKlevuSimpleSyncFixtures
     * @magentoDataFixture loadSyncHistorySimpleFixtures
     */
    public function testReturnsItemsAndTotalRecords()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $this->mockRequest->method('getParam')
            ->willReturnCallback(static function ($param) use ($store, $product) {
                switch ($param) {
                    case 'store':
                        return $store->getId();
                    case 'unique_entity_id':
                        return '0-' . $product->getId() . '-0';
                    default:
                        return null;
                }
            });

        $this->mockLogger->expects($this->never())
            ->method('error');

        $dataProvider = $this->instantiateDataProviderService();
        $data = $dataProvider->getData();

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($data);
        } else {
            $this->assertTrue(is_array($data));
        }
        $this->assertArrayHasKey('totalRecords', $data);
        $this->assertSame(5, $data['totalRecords']);

        $this->assertArrayHasKey('items', $data);
        $this->assertCount(5, $data['items']);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($data['items']);
        } else {
            $this->assertTrue(is_array($data['items']));
        }
        $keys = array_keys($data['items']);
        $firstItem = $data['items'][$keys[0]];
        $this->assertArrayHasKey(HistoryResourceModel::ENTITY_ID, $firstItem);
        $this->assertArrayHasKey(History::FIELD_PRODUCT_ID, $firstItem);
        $this->assertArrayHasKey(History::FIELD_PARENT_ID, $firstItem);
        $this->assertArrayHasKey(History::FIELD_STORE_ID, $firstItem);
        $this->assertArrayHasKey(History::FIELD_SYNCED_AT, $firstItem);
        $this->assertArrayHasKey(History::FIELD_ACTION, $firstItem);
        $this->assertArrayHasKey(History::FIELD_SUCCESS, $firstItem);
        $this->assertArrayHasKey(History::FIELD_MESSAGE, $firstItem);
        
        $this->assertSame($product->getId(), $firstItem[History::FIELD_PRODUCT_ID]);
        $this->assertSame($store->getId(), $firstItem[History::FIELD_STORE_ID]);
    }

    /**
     * @return array[]
     */
    public function invalidStoreIdDataProvider()
    {
        return [
            [null],
            [true],
            [false],
            [[1]]
        ];
    }

    /**
     * @return array
     */
    public function invalidEntityIdDataProvider()
    {
        return [
            [null],
            [true],
            [false],
            [[1]]
        ];
    }

    /**
     * @return array
     */
    public function invalidProductIdDataProvider()
    {
        return [
            [null],
            [true],
            [false],
            [0],
            ['some string']
        ];
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
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return DataProvider
     */
    private function instantiateDataProviderService()
    {
        return $this->objectManager->create(DataProvider::class, [
            'name' => 'sync_product_history_listing_data_source',
            'primaryFieldName' => 'sync_id',
            'requestFieldName' => 'id',
            'request' => $this->mockRequest,
            'logger' => $this->mockLogger
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
        include __DIR__ . '/../../../../../../_files/productFixtures.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSimpleProductFixturesRollback()
    {
        include __DIR__ . '/../../../../../../_files/productFixtures_rollback.php';
    }

    /**
     * Loads klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuSimpleSyncFixtures()
    {
        include __DIR__ . '/../../../../../../_files/klevuProductSyncFixtures.php';
    }

    /**
     * Rolls back klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuSimpleSyncFixturesRollback()
    {
        include __DIR__ . '/../../../../../../_files/klevuProductSyncFixtures_rollback.php';
    }

    /**
     * Loads sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistorySimpleFixtures()
    {
        include __DIR__ . '/../../../../../../_files/syncHistoryFixtures.php';
    }

    /**
     * Rolls back sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistorySimpleFixturesRollback()
    {
        include __DIR__ . '/../../../../../../_files/syncHistoryFixtures_rollback.php';
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../../../../_files/websiteFixtures_rollback.php';
    }
}
