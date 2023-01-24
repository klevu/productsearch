<?php

namespace Klevu\Search\Test\Integration\Repository;

use InvalidArgumentException;
use Klevu\Search\Api\Data\KlevuSyncEntityInterface;
use Klevu\Search\Api\Data\SyncEntitySearchResultsInterface;
use Klevu\Search\Api\KlevuSyncRepositoryInterface;
use Klevu\Search\Exception\Sync\Product\DeleteSyncDataException;
use Klevu\Search\Exception\Sync\Product\SaveSyncDataException;
use Klevu\Search\Model\Klevu\Klevu;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuResourceModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\Collection as KlevuModelCollection;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\CollectionFactory as KlevuModelCollectionFactory;
use Klevu\Search\Repository\KlevuSyncRepository;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class KlevuSyncRepositoryTest extends TestCase
{
    /**
     * @var  ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $syncRepository = $this->instantiateKlevuEntitySyncRepository();

        $this->assertInstanceOf(KlevuSyncRepository::class, $syncRepository);
    }

    public function testCreateReturnsInstanceOfKlevuInterface()
    {
        $this->setUpPhp5();

        $syncRepository = $this->instantiateKlevuEntitySyncRepository();
        $syncEntity = $syncRepository->create();

        $this->assertInstanceOf(KlevuSyncEntityInterface::class, $syncEntity);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadKlevuSimpleSyncFixtures
     */
    public function testGetReturnsInstanceOfKlevuInterface()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');
        $syncEntity = $this->getSyncEntity($product->getId(), $store->getId(), 0);

        $syncRepository = $this->instantiateKlevuEntitySyncRepository();
        $syncEntityFroRepository = $syncRepository->get((int)$syncEntity->getId());

        $this->assertInstanceOf(KlevuSyncEntityInterface::class, $syncEntityFroRepository);

        $this->assertSame($syncEntity->getProductId(), $syncEntityFroRepository->getProductId());
    }

    public function testGetThrowsExceptionIfEntityDoesNotExist()
    {
        $this->setUpPhp5();

        $rowId = 999999999999;

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage(
            'No entity found with ID: ' . $rowId
        );

        $syncRepository = $this->instantiateKlevuEntitySyncRepository();
        $syncRepository->get($rowId);
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
        $mockSyncResourceModel = $this->getMockBuilder(KlevuResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSyncResourceModel->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $syncRepository = $this->objectManager->create(KlevuSyncRepositoryInterface::class, [
            'klevuResourceModel' => $mockSyncResourceModel
        ]);

        $syncEntity = $syncRepository->create();
        $syncEntity->setProductId($product->getId());
        $syncEntity->setParentId(0);
        $syncEntity->setStoreId($store->getId());
        $syncEntity->setLastSyncedAt(0);
        $syncEntity->setType(Klevu::OBJECT_TYPE_PRODUCT);

        $this->expectException(AlreadyExistsException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $syncRepository->save($syncEntity);
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
        $mockSyncResourceModel = $this->getMockBuilder(KlevuResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSyncResourceModel->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $syncRepository = $this->objectManager->create(KlevuSyncRepositoryInterface::class, [
            'klevuResourceModel' => $mockSyncResourceModel
        ]);

        $syncEntity = $syncRepository->create();
        $syncEntity->setProductId($product->getId());
        $syncEntity->setParentId(0);
        $syncEntity->setStoreId($store->getId());
        $syncEntity->setLastSyncedAt(0);
        $syncEntity->setType(Klevu::OBJECT_TYPE_PRODUCT);

        $this->expectException(SaveSyncDataException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessage('The Klevu Sync Entity could not be saved. ' . $exceptionMessage);
        }

        $syncRepository->save($syncEntity);
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

        $syncRepository = $this->objectManager->get(KlevuSyncRepositoryInterface::class);

        $syncEntity = $syncRepository->create();
        $syncEntity->setProductId($product->getId());
        $syncEntity->setParentId(0);
        $syncEntity->setStoreId((int)$store->getId());
        $syncEntity->setType(Klevu::OBJECT_TYPE_PRODUCT);

        $syncEntity->setData($missingParam, null);

        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/ID is a required field and is not set/');
        }

        $entity = $syncRepository->save($syncEntity);

        $this->assertInstanceOf(KlevuSyncEntityInterface::class, $entity);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     */
    public function testSaveReturnsInstanceOfKlevuInterface()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $syncRepository = $this->instantiateKlevuEntitySyncRepository();

        $syncEntity = $syncRepository->create();
        $syncEntity->setProductId($product->getId());
        $syncEntity->setParentId(0);
        $syncEntity->setStoreId($store->getId());
        $syncEntity->setLastSyncedAt(0);
        $syncEntity->setType(Klevu::OBJECT_TYPE_PRODUCT);

        $savedEntity = $syncRepository->save($syncEntity);

        $this->assertInstanceOf(KlevuSyncEntityInterface::class, $savedEntity);
        $this->assertSame((int)$product->getId(), $savedEntity->getProductId());
        $this->assertSame(null, $savedEntity->getParentId());
        $this->assertSame((int)$store->getId(), $savedEntity->getStoreId());
        $this->assertSame(Klevu::OBJECT_TYPE_PRODUCT, $savedEntity->getType());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadKlevuSimpleSyncFixtures
     */
    public function testDeleteThrowsExceptionIfEntityDoesNotDelete()
    {
        $this->setUpPhp5();

        $entityId = 1234;

        $exception = new \Exception('Some error');
        $mockSyncResourceModel = $this->getMockBuilder(KlevuResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSyncResourceModel->expects($this->once())
            ->method('delete')
            ->willThrowException($exception);

        $syncRepository = $this->objectManager->create(KlevuSyncRepositoryInterface::class, [
            'klevuResourceModel' => $mockSyncResourceModel
        ]);

        $syncEntity = $syncRepository->create();
        $syncEntity->setId($entityId);

        $this->expectException(DeleteSyncDataException::class);
        $this->expectExceptionMessage('The ' . $entityId . ' Klevu Sync Entity could not be removed');

        $syncRepository->delete($syncEntity);
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

        $syncRepository = $this->instantiateKlevuEntitySyncRepository();

        $syncEntity = $syncRepository->create();
        $syncEntity->setProductId($product->getId());
        $syncEntity->setParentId(0);
        $syncEntity->setStoreId($store->getId());
        $syncEntity->setLastSyncedAt(0);
        $syncEntity->setType(Klevu::OBJECT_TYPE_PRODUCT);

        $savedEntity = $syncRepository->save($syncEntity);

        $this->assertInstanceOf(KlevuSyncEntityInterface::class, $savedEntity);
        $this->assertSame((int)$product->getId(), (int)$savedEntity->getProductId());
        $this->assertSame((int)$store->getId(), (int)$savedEntity->getStoreId());

        $syncRepository->delete($savedEntity);

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage(
            'No entity found with ID: ' . $savedEntity->getId()
        );

        $syncRepository->get($savedEntity->getId());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadSimpleProductFixtures
     * @magentoDataFixture loadKlevuSimpleSyncFixtures
     */
    public function testGetListReturnsSyncEntitySearchResultsInterface()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter(Klevu::FIELD_PRODUCT_ID, $product->getId(), 'eq');
        $searchCriteriaBuilder->addFilter(Klevu::FIELD_STORE_ID, $store->getId(), 'eq');
        $searchCriteria = $searchCriteriaBuilder->create();

        $syncRepository = $this->instantiateKlevuEntitySyncRepository();
        $result = $syncRepository->getList($searchCriteria);

        $this->assertInstanceOf(SyncEntitySearchResultsInterface::class, $result);
        $this->assertSame(1, $result->getTotalCount());
        $this->assertSame($searchCriteria, $result->getSearchCriteria());
        $items = $result->getItems();
        $keys = array_keys($items);
        $this->assertArrayHasKey($keys[0], $items);
        $item = $items[$keys[0]];
        $this->assertInstanceOf(Klevu::class, $item);
        $this->assertSame((int)$product->getId(), (int)$item->getProductId());
        $this->assertSame((int)$store->getId(), (int)$item->getStoreId());
        $this->assertSame(0, (int)$item->getParentId());
    }

    /**
     * @return \string[][]
     */
    public function missingRequiredParamsDataProvider()
    {
        return [
            ['product_id'],
            ['store_id']
        ];
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @return KlevuSyncRepositoryInterface
     */
    private function instantiateKlevuEntitySyncRepository()
    {
        return $this->objectManager->get(KlevuSyncRepositoryInterface::class);
    }

    /**
     * @param $productId
     * @param $storeId
     * @param $parentId
     *
     * @return DataObject|KlevuSyncEntityInterface
     */
    private function getSyncEntity($productId, $storeId, $parentId)
    {
        /** @var KlevuModelCollectionFactory $klevuSyncModelCollectionFactory */
        $klevuSyncModelCollectionFactory = $this->objectManager->get(KlevuModelCollectionFactory::class);

        /** @var KlevuModelCollection $collection */
        $collection = $klevuSyncModelCollectionFactory->create();
        $collection->addFieldToFilter(Klevu::FIELD_PRODUCT_ID, $productId);
        $collection->addFieldToFilter(Klevu::FIELD_PARENT_ID, $parentId);
        $collection->addFieldToFilter(Klevu::FIELD_STORE_ID, $storeId);
        $collection->addFieldToFilter(Klevu::FIELD_TYPE, Klevu::OBJECT_TYPE_PRODUCT);

        return $collection->getFirstItem();
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
        include __DIR__ . '/../_files/klevuProductSyncFixtures.php';
    }

    /**
     * Rolls back klevu sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuSyncFixturesRollback()
    {
        include __DIR__ . '/../_files/klevuProductSyncFixtures_rollback.php';
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
}
