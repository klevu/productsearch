<?php
// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Repository;

use InvalidArgumentException;
use Klevu\Search\Api\Data\HistoryInterface;
use Klevu\Search\Api\KlevuProductSyncHistoryRepositoryInterface as HistoryRepositoryInterface;
use Klevu\Search\Exception\Sync\Product\CouldNotDeleteHistoryException;
use Klevu\Search\Exception\Sync\Product\CouldNotSaveHistoryException;
use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Model\Product\Sync\ResourceModel\History as HistoryResourceModel;
use Klevu\Search\Model\Source\NextAction;
use Klevu\Search\Repository\KlevuProductSyncHistoryRepository as HistoryRepository;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class SyncHistoryRepositoryTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $historyRepository = $this->instantiateHistoryRepository();

        $this->assertInstanceOf(HistoryRepository::class, $historyRepository);
    }

    public function testCreateReturnsInstanceOfKlevuInterface()
    {
        $this->setUpPhp5();

        $syncHistoryRepository = $this->instantiateHistoryRepository();
        $syncHistoryEntity = $syncHistoryRepository->create();

        $this->assertInstanceOf(HistoryInterface::class, $syncHistoryEntity);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadSyncHistoryFixtures
     */
    public function testGetByIdReturnsInstanceOfHistoryInterface()
    {
        $this->setUpPhp5();

        $product = $this->getProduct('klevu_simple_1');
        $store = $this->getStore('klevu_test_store_1');

        $historyModel = $this->objectManager->get(History::class);
        $historyResourceModel = $this->objectManager->get(HistoryResourceModel::class);
        $historyResourceModel->load($historyModel, $product->getId(), History::FIELD_PRODUCT_ID);

        $historyRepository = $this->instantiateHistoryRepository();
        $history = $historyRepository->getById($historyModel->getId());

        $this->assertSame((int)$product->getId(), (int)$history->getProductId());
        $this->assertSame((int)$store->getId(), (int)$history->getStoreId());
    }

    public function testGetByIdThrowsExceptionsWhenIdDoesNotExist()
    {
        $this->setUpPhp5();
        $id = 123;

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('Requested ID ' . $id . ' does not exist.');

        $historyRepository = $this->instantiateHistoryRepository();
        $historyRepository->getById($id);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     */
    public function testSaveThrowsExceptionIfEntityAlreadyExists()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $exceptionMessage = 'Already Exists';
        $exception = new AlreadyExistsException(__($exceptionMessage));
        $mockSyncHistoryResourceModel = $this->getMockBuilder(HistoryResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSyncHistoryResourceModel->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $historyRepository = $this->objectManager->create(HistoryRepositoryInterface::class, [
            'historyResourceModel' => $mockSyncHistoryResourceModel
        ]);

        $historyEntity = $historyRepository->create();
        $historyEntity->setProductId($product->getId());
        $historyEntity->setParentId(0);
        $historyEntity->setStoreId($store->getId());
        $historyEntity->setAction(NextAction::ACTION_VALUE_UPDATE);
        $historyEntity->setSuccess(false);
        $historyEntity->setMessage('API Request Failed');

        $this->expectException(AlreadyExistsException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $historyRepository->save($historyEntity);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     */
    public function testSaveThrowsExceptionIfEntityDoesNotSave()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $exceptionMessage = 'Some Message';
        $exception = new \Exception(__($exceptionMessage));
        $mockSyncHistoryResourceModel = $this->getMockBuilder(HistoryResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSyncHistoryResourceModel->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $historyRepository = $this->objectManager->create(HistoryRepositoryInterface::class, [
            'historyResourceModel' => $mockSyncHistoryResourceModel
        ]);

        $historyEntity = $historyRepository->create();
        $historyEntity->setProductId($product->getId());
        $historyEntity->setParentId(0);
        $historyEntity->setStoreId($store->getId());
        $historyEntity->setAction(NextAction::ACTION_VALUE_UPDATE);
        $historyEntity->setSuccess(false);
        $historyEntity->setMessage('API Request Failed');

        $this->expectException(CouldNotSaveHistoryException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $historyRepository->save($historyEntity);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @dataProvider missingRequiredParamsDataProvider
     */
    public function testSaveThrowsExceptionIfMissingParams($missingParam)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches(
                '/ is a required field and is not set or is an invalid type./'
            );
        }

        $historyRepository = $this->instantiateHistoryRepository();

        $historyEntity = $historyRepository->create();
        $historyEntity->setProductId($product->getId());
        $historyEntity->setParentId(0);
        $historyEntity->setStoreId($store->getId());
        $historyEntity->setAction(NextAction::ACTION_VALUE_UPDATE);
        $historyEntity->setSuccess(false);
        $historyEntity->setMessage('API Request Failed');

        $historyEntity->setData($missingParam, null);

        $historyRepository->save($historyEntity);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     */
    public function testSaveReturnsInstanceOfSyncHistoryInterface()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $historyRepository = $this->instantiateHistoryRepository();

        $historyEntity = $historyRepository->create();
        $historyEntity->setProductId($product->getId());
        $historyEntity->setParentId(0);
        $historyEntity->setStoreId($store->getId());
        $historyEntity->setAction(NextAction::ACTION_VALUE_UPDATE);
        $historyEntity->setSuccess(false);
        $historyEntity->setMessage('API Request Failed');

        $savedEntity = $historyRepository->save($historyEntity);

        $this->assertInstanceOf(HistoryInterface::class, $savedEntity);
        $this->assertSame((int)$product->getId(), $savedEntity->getProductId());
        $this->assertSame(0, $savedEntity->getParentId());
        $this->assertSame((int)$store->getId(), $savedEntity->getStoreId());
        $this->assertFalse($savedEntity->getSuccess());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @dataProvider missingRequiredParamsDataProvider
     */
    public function testInsertThrowsExceptionIfRequiredDataMissing($missingParam)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches(
                '/Exception .*: .* is required, but missing from the request./'
            );
        }

        $historyRepository = $this->instantiateHistoryRepository();

        $history = [];
        $history[History::FIELD_PRODUCT_ID] = $product->getId();
        $history[History::FIELD_PARENT_ID] = 0;
        $history[History::FIELD_STORE_ID] = $store->getId();
        $history[History::FIELD_ACTION] = NextAction::ACTION_VALUE_UPDATE;
        $history[History::FIELD_SUCCESS] = true;
        $history[History::FIELD_MESSAGE] = '1 records are marked for UPDATE';

        unset($history[$missingParam]);

        $historyRepository->insert([$history]);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @dataProvider missingRequiredParamsDataProvider
     */
    public function testInsertThrowsExceptionIfDataIncorrectType($missingParam)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches(
                '/Exception .*: .* is invalid. .* provided, .* expected./'
            );
        }

        $historyRepository = $this->instantiateHistoryRepository();

        $history = [];
        $history[History::FIELD_PRODUCT_ID] = $product->getId();
        $history[History::FIELD_PARENT_ID] = 0;
        $history[History::FIELD_STORE_ID] = $store->getId();
        $history[History::FIELD_ACTION] = NextAction::ACTION_VALUE_UPDATE;
        $history[History::FIELD_SUCCESS] = true;
        $history[History::FIELD_MESSAGE] = '1 records are marked for UPDATE';

        $history[$missingParam] = 'some invalid thing';

        $historyRepository->insert([$history]);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadConfigurableProductFixtures
     */
    public function testInsertSavesDataAndReturnsNumberOfRecordsSaved()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $simple = $this->getProduct('klevu_simple_1');
        $configParent = $this->getProduct('klevu_configurable_1');
        $configChild1 = $this->getProduct('klevu_simple_child_1');
        $configChild2 = $this->getProduct('klevu_simple_child_2');

        $historyRepository = $this->instantiateHistoryRepository();

        $records = [];
        $simpleHistory = [];
        $simpleHistory[History::FIELD_PRODUCT_ID] = $simple->getId();
        $simpleHistory[History::FIELD_PARENT_ID] = 0;
        $simpleHistory[History::FIELD_STORE_ID] = $store->getId();
        $simpleHistory[History::FIELD_ACTION] = NextAction::ACTION_VALUE_UPDATE;
        $simpleHistory[History::FIELD_SUCCESS] = true;
        $simpleHistory[History::FIELD_MESSAGE] = '1 records are marked for UPDATE';
        $records[] = $simpleHistory;

        $configHistory1 = [];
        $configHistory1[History::FIELD_PRODUCT_ID] = $configChild1->getId();
        $configHistory1[History::FIELD_PARENT_ID] = $configParent->getId();
        $configHistory1[History::FIELD_STORE_ID] = $store->getId();
        $configHistory1[History::FIELD_ACTION] = NextAction::ACTION_VALUE_DELETE;
        $configHistory1[History::FIELD_SUCCESS] = true;
        $configHistory1[History::FIELD_MESSAGE] = '1 records are marked for DELETE';
        $records[] = $configHistory1;

        $configHistory2 = [];
        $configHistory2[History::FIELD_PRODUCT_ID] = $configChild2->getId();
        $configHistory2[History::FIELD_PARENT_ID] = $configParent->getId();
        $configHistory2[History::FIELD_STORE_ID] = $store->getId();
        $configHistory2[History::FIELD_ACTION] = NextAction::ACTION_VALUE_ADD;
        $configHistory2[History::FIELD_SUCCESS] = true;
        $configHistory2[History::FIELD_MESSAGE] = '1 records are marked for ADD';
        $records[] = $configHistory2;

        $simpleHistory2 = [];
        $simpleHistory2[History::FIELD_PRODUCT_ID] = $configChild1->getId();
        $simpleHistory2[History::FIELD_PARENT_ID] = 0;
        $simpleHistory2[History::FIELD_STORE_ID] = $store->getId();
        $simpleHistory2[History::FIELD_ACTION] = NextAction::ACTION_VALUE_UPDATE;
        $simpleHistory2[History::FIELD_SUCCESS] = true;
        $simpleHistory2[History::FIELD_MESSAGE] = '1 records are marked for UPDATE';
        $records[] = $simpleHistory2;

        $simpleHistory3 = [];
        $simpleHistory3[History::FIELD_PRODUCT_ID] = $configChild2->getId();
        $simpleHistory3[History::FIELD_PARENT_ID] = 0;
        $simpleHistory3[History::FIELD_STORE_ID] = $store->getId();
        $simpleHistory3[History::FIELD_ACTION] = NextAction::ACTION_VALUE_UPDATE;
        $simpleHistory3[History::FIELD_SUCCESS] = true;
        $simpleHistory3[History::FIELD_MESSAGE] = '1 records are marked for UPDATE';
        $records[] = $simpleHistory3;

        $insertCount = $historyRepository->insert($records);

        $this->assertSame(5, $insertCount);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     */
    public function testDeleteThrowsExceptionWhenEntityDoesNotExist()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $exception = new \Exception('Some error');
        $mockHistoryResourceModel = $this->getMockBuilder(HistoryResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockHistoryResourceModel->expects($this->once())
            ->method('delete')
            ->willThrowException($exception);

        $historyRepository = $this->objectManager->create(HistoryRepositoryInterface::class, [
            'historyResourceModel' => $mockHistoryResourceModel
        ]);
        $historyEntity = $historyRepository->create();
        $historyEntity->setProductId($product->getId());
        $historyEntity->setParentId(0);
        $historyEntity->setStoreId($store->getId());
        $historyEntity->setAction(NextAction::ACTION_VALUE_DELETE);
        $historyEntity->setSuccess(true);
        $historyEntity->setMessage('API Request Success');

        $this->expectException(CouldNotDeleteHistoryException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Could not delete sync history/');
        }

        $historyRepository->delete($historyEntity);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     */
    public function testDeleteRemovesEntity()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $historyRepository = $this->instantiateHistoryRepository();

        $historyEntity = $historyRepository->create();
        $historyEntity->setProductId($product->getId());
        $historyEntity->setParentId(0);
        $historyEntity->setStoreId($store->getId());
        $historyEntity->setAction(NextAction::ACTION_VALUE_UPDATE);
        $historyEntity->setSuccess(false);
        $historyEntity->setMessage('API Request Failed');

        $savedEntity = $historyRepository->save($historyEntity);

        $this->assertInstanceOf(HistoryInterface::class, $savedEntity);
        $this->assertSame((int)$product->getId(), $savedEntity->getProductId());

        $historyRepository->delete($savedEntity);

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage(
            'Requested ID ' . $savedEntity->getId() . ' does not exist.'
        );

        $historyRepository->getbyId($savedEntity->getId());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadSyncHistoryFixtures
     */
    public function testGetListReturnsSyncEntitySearchResultsInterface()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter(History::FIELD_PRODUCT_ID, $product->getId(), 'eq');
        $searchCriteriaBuilder->addFilter(History::FIELD_STORE_ID, $store->getId(), 'eq');
        $searchCriteria = $searchCriteriaBuilder->create();

        $syncRepository = $this->instantiateHistoryRepository();
        $result = $syncRepository->getList($searchCriteria);

        $this->assertInstanceOf(SearchResultsInterface::class, $result);
        $this->assertSame(5, $result->getTotalCount());
        $this->assertSame($searchCriteria, $result->getSearchCriteria());
        $items = $result->getItems();
        $keys = array_keys($items);
        $this->assertArrayHasKey($keys[0], $items);
        $item = $items[$keys[0]];
        $this->assertInstanceOf(HistoryInterface::class, $item);
        $this->assertSame((int)$product->getId(), (int)$item->getProductId());
        $this->assertSame((int)$store->getId(), (int)$item->getStoreId());
        $this->assertSame(0, (int)$item->getParentId());
    }

    /**
     * @return array[]
     */
    public function missingRequiredParamsDataProvider()
    {
        return [
            [History::FIELD_PRODUCT_ID],
            [History::FIELD_STORE_ID],
            [History::FIELD_ACTION]
        ];
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadSyncHistoryFixtures
     */
    public function testGetList_IgnoresIncorrectlySet_SortByValues()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter(History::FIELD_PRODUCT_ID, $product->getId(), 'eq');
        $searchCriteriaBuilder->addFilter(History::FIELD_STORE_ID, $store->getId(), 'eq');
        $searchCriteria = $searchCriteriaBuilder->create();

        $searchCriteria->setPageSize(1);

        $sortOrderBuilder = $this->objectManager->get(SortOrderBuilder::class);
        $sortOrderBuilder->setField(HistoryResourceModel::ENTITY_ID);
        $sortOrderBuilder->setDirection(SortOrder::SORT_DESC);
        $sortOrder = $sortOrderBuilder->create();
        $searchCriteria->setSortOrders([
            $sortOrder,
            'string'
        ]);

        $syncRepository = $this->instantiateHistoryRepository();
        $result = $syncRepository->getList($searchCriteria);

        $this->assertInstanceOf(SearchResultsInterface::class, $result);
        $this->assertSame(5, $result->getTotalCount());
        $this->assertSame($searchCriteria, $result->getSearchCriteria());
        $items = $result->getItems();
        $keys = array_keys($items);
        $this->assertArrayHasKey($keys[0], $items);
        $item = $items[$keys[0]];
        $this->assertInstanceOf(HistoryInterface::class, $item);
        $this->assertSame((int)$product->getId(), (int)$item->getProductId());
        $this->assertSame((int)$store->getId(), (int)$item->getStoreId());
        $this->assertSame(0, (int)$item->getParentId());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadSyncHistoryFixtures
     */
    public function testGetList_IgnoresIncorrectlySet_Filters()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter(History::FIELD_PRODUCT_ID, $product->getId(), 'eq');
        $searchCriteriaBuilder->addFilter(History::FIELD_STORE_ID, $store->getId(), 'eq');
        $searchCriteriaBuilder->addFilters([
            'string'
        ]);
        $searchCriteria = $searchCriteriaBuilder->create();

        $syncRepository = $this->instantiateHistoryRepository();
        $result = $syncRepository->getList($searchCriteria);

        $this->assertInstanceOf(SearchResultsInterface::class, $result);
        $this->assertSame(5, $result->getTotalCount());
        $this->assertSame($searchCriteria, $result->getSearchCriteria());
        $items = $result->getItems();
        $keys = array_keys($items);
        $this->assertArrayHasKey($keys[0], $items);
        $item = $items[$keys[0]];
        $this->assertInstanceOf(HistoryInterface::class, $item);
        $this->assertSame((int)$product->getId(), (int)$item->getProductId());
        $this->assertSame((int)$store->getId(), (int)$item->getStoreId());
        $this->assertSame(0, (int)$item->getParentId());
    }

    /*
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @return HistoryRepositoryInterface
     */
    private function instantiateHistoryRepository()
    {
        return $this->objectManager->get(HistoryRepositoryInterface::class);
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
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../_files/websiteFixtures_rollback.php';
    }

    /**
     * Loads configurable product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadConfigurableProductFixtures()
    {
        include __DIR__ . '/../_files/productFixtures_configurableProduct.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadConfigurableProductFixturesRollback()
    {
        include __DIR__ . '/../_files/productFixtures_configurableProduct_rollback.php';
    }

    /**
     * Loads simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSimpleProductFixtures()
    {
        include __DIR__ . '/../_files/productFixtures.php';
    }

    /**
     * Rolls back simple product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSimpleProductFixturesRollback()
    {
        include __DIR__ . '/../_files/productFixtures_rollback.php';
    }

    /**
     * Loads sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryFixtures()
    {
        include __DIR__ . '/../_files/syncHistoryFixtures.php';
    }

    /**
     * Rolls back sync history creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadSyncHistoryFixturesRollback()
    {
        include __DIR__ . '/../_files/syncHistoryFixtures_rollback.php';
    }
}
