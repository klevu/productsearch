<?php

namespace Klevu\Search\Test\Integration\Observer\Sync\Product;

use Klevu\Search\Api\Data\HistoryInterface;
use Klevu\Search\Api\KlevuProductSyncHistoryRepositoryInterface as SyncHistoryRepository;
use Klevu\Search\Model\Api\Action\Addrecords;
use Klevu\Search\Model\Api\Action\Deleterecords;
use Klevu\Search\Model\Api\Action\Updaterecords;
use Klevu\Search\Model\Api\Request as KlevuApiRequest;
use Klevu\Search\Model\Api\Response as KlevuApiResponse;
use Klevu\Search\Model\Product\Sync\History;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SyncRecordsAfterObserverTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var KlevuApiRequest|MockObject
     */
    private $mockRequest;
    /**
     * @var KlevuApiResponse|MockObject
     */
    private $mockResponse;
    /**
     * @var SyncHistoryRepository
     */
    private $syncHistoryRepository;

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @dataProvider actionDataProvider
     */
    public function testSimpleRecordIsAddedToDatabase($action)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $productId = random_int(1, 99999999);
        $parentId = 0;
        $success = true;
        $message = 'API Call Successful';

        $recordToSync = [
            [
                'id' => $productId
            ]
        ];

        $this->mockResponse->expects($this->once())
            ->method('isSuccess')
            ->willReturn($success);
        $this->mockResponse->expects($this->once())
            ->method('getMessage')
            ->willReturn($message);

        $eventManager = $this->objectManager->get(EventManager::class);
        $eventManager->dispatch(
            'klevu_api_send_' . strtolower($action) . '_records_after',
            [
                'recordsToSync' => $recordToSync,
                'request' => $this->mockRequest,
                'response' => $this->mockResponse,
                'action' => $action,
                'store' => $store->getId()
            ]
        );

        $searchResult = $this->getSavedHistory($productId, $parentId, $store->getId());
        $historyItems = $searchResult->getItems();

        $this->assertcount(1, $historyItems, 'Count Items');

        $keys = array_keys($historyItems);
        $this->assertArrayHasKey($keys[0], $historyItems);
        $item = $historyItems[$keys[0]];
        $this->assertInstanceOf(HistoryInterface::class, $item);
        $this->assertSame($productId, (int)$item->getProductId());
        $this->assertSame($parentId, (int)$item->getParentId());
        $this->assertSame((int)$store->getId(), (int)$item->getStoreId());
        $this->assertSame($success, $item->getSuccess());
        $this->assertSame($message, $item->getMessage());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @dataProvider actionDataProvider
     */
    public function testComplexRecordIsAddedToDatabase($action)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $productId = random_int(1, 99999999);
        $parentId = random_int(1, 99999999);
        $success = true;
        $message = 'API Call Successful';

        $recordToSync = [
            [
                'id' => $parentId . '-' . $productId,
                'itemGroupId' => $parentId
            ]
        ];

        $this->mockResponse->expects($this->once())
            ->method('isSuccess')
            ->willReturn($success);
        $this->mockResponse->expects($this->once())
            ->method('getMessage')
            ->willReturn($message);

        $eventManager = $this->objectManager->get(EventManager::class);
        $eventManager->dispatch(
            'klevu_api_send_' . strtolower($action) . '_records_after',
            [
                'recordsToSync' => $recordToSync,
                'request' => $this->mockRequest,
                'response' => $this->mockResponse,
                'action' => $action,
                'store' => $store->getId()
            ]
        );

        $searchResult = $this->getSavedHistory($productId, $parentId, $store->getId());
        $historyItems = $searchResult->getItems();

        $this->assertSame(1, $searchResult->getTotalCount());
        $this->assertcount(1, $historyItems);

        $keys = array_keys($historyItems);
        $this->assertArrayHasKey($keys[0], $historyItems);
        $item = $historyItems[$keys[0]];
        $this->assertInstanceOf(HistoryInterface::class, $item);
        $this->assertSame($productId, (int)$item->getProductId());
        $this->assertSame($parentId, (int)$item->getParentId());
        $this->assertSame((int)$store->getId(), (int)$item->getStoreId());
        $this->assertSame($success, $item->getSuccess());
        $this->assertSame($message, $item->getMessage());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @dataProvider actionDataProvider
     */
    public function testRecordIsNotAddedToDatabaseWhenActionIsNotPresentInEventData($action)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $productId = random_int(1, 99999999);
        $parentId = random_int(1, 99999999);
        $success = true;
        $message = 'API Call Successful';

        $recordToSync = [
            [
                'id' => $productId,
                'itemGroupId' => $parentId
            ]
        ];

        $this->mockResponse->expects($this->never())
            ->method('isSuccess');
        $this->mockResponse->expects($this->never())
            ->method('getMessage');

        $eventManager = $this->objectManager->get(EventManager::class);
        $eventManager->dispatch(
            'klevu_api_send_' . strtolower($action) . '_records_after',
            [
                'recordsToSync' => $recordToSync,
                'request' => $this->mockRequest,
                'response' => $this->mockResponse,
                'store' => $store->getId()
            ]
        );

        $searchResult = $this->getSavedHistory($productId, $parentId, $store->getId());
        $historyItems = $searchResult->getItems();

        $this->assertSame(0, $searchResult->getTotalCount());
        $this->assertcount(0, $historyItems);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @dataProvider actionDataProvider
     */
    public function testRecordIsNotAddedToDatabaseWhenStoreIdIsNotPresentInEventData($action)
    {
        $this->setUpPhp5();

        $productId = random_int(1, 99999999);
        $parentId = random_int(1, 99999999);
        $success = true;
        $message = 'API Call Successful';

        $recordToSync = [
            [
                'id' => $productId,
                'itemGroupId' => $parentId
            ]
        ];

        $this->mockResponse->expects($this->never())
            ->method('isSuccess');
        $this->mockResponse->expects($this->never())
            ->method('getMessage');

        $eventManager = $this->objectManager->get(EventManager::class);
        $eventManager->dispatch(
            'klevu_api_send_' . strtolower($action) . '_records_after',
            [
                'recordsToSync' => $recordToSync,
                'request' => $this->mockRequest,
                'response' => $this->mockResponse
            ]
        );

        $searchResult = $this->getSavedHistory($productId, $parentId);
        $historyItems = $searchResult->getItems();

        $this->assertSame(0, $searchResult->getTotalCount());
        $this->assertcount(0, $historyItems);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @dataProvider actionDataProvider
     */
    public function testRecordIsNotAddedToDatabaseWhenProductIdIsNotPresentInEventData($action)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $parentId = random_int(1, 99999999);
        $success = true;
        $message = 'API Call Successful';

        $recordToSync = [
            [
                'parent_id' => $parentId,
                'store_id' => $store->getId()
            ]
        ];

        $this->mockResponse->expects($this->never())
            ->method('isSuccess');
        $this->mockResponse->expects($this->never())
            ->method('getMessage');

        $eventManager = $this->objectManager->get(EventManager::class);
        $eventManager->dispatch(
            'klevu_api_send_' . strtolower($action) . '_records_after',
            [
                'recordsToSync' => $recordToSync,
                'request' => $this->mockRequest,
                'response' => $this->mockResponse,
                'store' => $store->getId()
            ]
        );

        $searchResult = $this->getSavedHistory(null, $parentId, $store->getId());
        $historyItems = $searchResult->getItems();

        $this->assertSame(0, $searchResult->getTotalCount());
        $this->assertcount(0, $historyItems);
    }

    /**
     * @return array
     */
    public function actionDataProvider()
    {
        return [
            [Addrecords::ACTION],
            [Deleterecords::ACTION],
            [Updaterecords::ACTION]
        ];
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->syncHistoryRepository = $this->objectManager->get(SyncHistoryRepository::class);
        $this->mockRequest = $this->getMockBuilder(KlevuApiRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockResponse = $this->getMockBuilder(KlevuApiResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param int|null $productId
     * @param int|null $parentId
     * @param int|null $store
     *
     * @return SearchResultsInterface
     */
    private function getSavedHistory($productId = null, $parentId = null, $storeId = null)
    {
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        if ($productId) {
            $searchCriteriaBuilder->addFilter(History::FIELD_PRODUCT_ID, $productId, 'eq');
        }
        if ($parentId) {
            $searchCriteriaBuilder->addFilter(History::FIELD_PARENT_ID, $parentId, 'eq');
        }
        if ($storeId) {
            $searchCriteriaBuilder->addFilter(History::FIELD_STORE_ID, $storeId, 'eq');
        }
        $searchCriteria = $searchCriteriaBuilder->create();

        return $this->syncHistoryRepository->getList($searchCriteria);
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
