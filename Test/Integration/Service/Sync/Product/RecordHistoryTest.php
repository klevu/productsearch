<?php

namespace Klevu\Search\Test\Integration\Service\Sync\Product;

use Klevu\Search\Api\Data\HistoryInterface;
use Klevu\Search\Api\KlevuProductSyncHistoryRepositoryInterface as SyncHistoryRepository;
use Klevu\Search\Api\Service\Sync\Product\RecordHistoryInterface;
use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Model\Product\Sync\ResourceModel\History as HistoryResourceModel;
use Klevu\Search\Model\Source\NextAction;
use Klevu\Search\Service\Sync\Product\RecordHistory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Event\Manager as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RecordHistoryTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var EventManager|MockObject
     */
    private $mockEventManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $recordHistory = $this->instantiateRecordHistory();

        $this->assertInstanceOf(RecordHistory::class, $recordHistory);
    }

    /**
     * @dataProvider invalidRecordDataProvider
     */
    public function testEventNotDispatchedWhenInvalidDataProvided($record)
    {
        $this->setUpPhp5();

        $this->mockEventManager->expects($this->never())
            ->method('dispatch');

        $recordHistory = $this->instantiateRecordHistory();
        $recordHistory->execute([$record]);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @dataProvider recordDataProvider
     */
    public function testEventIsDispatchedAfterSuccessfulSave($record)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $record['store_id'] = $store->getId();

        $this->mockEventManager->expects($this->once())
            ->method('dispatch');

        $recordHistory = $this->instantiateRecordHistory();
        $savedHistoryCount = $recordHistory->execute([$record]);

        $this->assertSame(1, $savedHistoryCount);
        $savedHistoryResult = $this->getSyncHistory(
            $record['product_id'],
            $record['parent_id'],
            $record['store_id']
        );

        $savedHistoryItems = $savedHistoryResult->getItems();
        $keys = array_keys($savedHistoryItems);
        /** @var HistoryInterface $savedHistory */
        $savedHistory = $savedHistoryItems[$keys[0]];

        $this->assertNotNull($savedHistory->getId());
        $this->assertSame((int)$record['product_id'], $savedHistory->getProductId(), 'Product ID');
        $this->assertSame((int)$record['parent_id'], $savedHistory->getParentId(), 'Parent ID');
        $this->assertSame((int)$record['store_id'], $savedHistory->getStoreId(), 'Store ID ');
        if (null !== $record['success']) {
            $this->assertSame((bool)$record['success'], $savedHistory->getSuccess(), 'Success');
        } else {
            $this->assertFalse($savedHistory->getSuccess(), 'Failure when null provided');
        }
        $action = (int)$record['action'];
        if (!$action) {
            $action = (int)$this->mapAction($record['action']);
        }
        $this->assertSame($action, $savedHistory->getAction(), 'Next Action');
        $this->assertSame((string)$record['message'], $savedHistory->getMessage(), 'Message');
        $this->assertnotNull($savedHistory->getSyncedAt());
    }

    /**
     * @return array
     */
    public function invalidRecordDataProvider()
    {
        return [
            [
                [
                    'store_id' => 1,
                    'action' => 2
                ]
            ],
            [
                [
                    'product_id' => 1,
                    'action' => 2
                ]
            ],
            [
                [
                    'store_id' => 1,
                    'action' => 2
                ]
            ],
            [
                [
                    'product_id' => 4,
                    'store_id' => 5,
                    'action' => 6,
                    'message' => true
                ]
            ],
            [
                [
                    'product_id' => 7,
                    'store_id' => 8,
                    'action' => 9,
                    'message' => false
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function recordDataProvider()
    {
        return [
            [
                [
                    'product_id' => 1,
                    'parent_id' => 0,
                    'action' => NextAction::ACTION_VALUE_ADD,
                    'success' => true,
                    'message' => 'success'
                ]
            ],
            [
                [
                    'product_id' => 3,
                    'parent_id' => 4,
                    'action' => NextAction::ACTION_VALUE_DELETE,
                    'success' => false,
                    'message' => 'Api Call Failed: Some reason'
                ]
            ],
            [
                [
                    'product_id' => '50',
                    'parent_id' => '65',
                    'action' => (string)NextAction::ACTION_VALUE_DELETE,
                    'success' => '1',
                    'message' => null
                ]
            ],
            [
                [
                    'product_id' => '50',
                    'parent_id' => '0',
                    'action' => NextAction::ACTION_UPDATE,
                    'success' => null,
                    'message' => 'true'
                ]
            ]
        ];
    }

    /**
     * @param string $action
     *
     * @return int
     */
    private function mapAction($action)
    {
        if (is_numeric($action)) {
            return (int)$action;
        }
        if ($action === NextAction::ACTION_ADD) {
            return NextAction::ACTION_VALUE_ADD;
        }
        if ($action === NextAction::ACTION_DELETE) {
            return NextAction::ACTION_VALUE_DELETE;
        }
        if ($action === NextAction::ACTION_UPDATE) {
            return NextAction::ACTION_VALUE_UPDATE;
        }
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->mockEventManager = $this->getMockBuilder(EventManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return SyncHistoryRepository
     */
    private function instantiateHistoryRepository()
    {
        return $this->objectManager->create(SyncHistoryRepository::class);
    }

    /**
     * @return RecordHistoryInterface
     */
    private function instantiateRecordHistory()
    {
        return $this->objectManager->create(RecordHistoryInterface::class, [
            'eventManager' => $this->mockEventManager
        ]);
    }

    /**
     * @param int $productId
     * @param int $parentId
     * @param int $storeId
     *
     * @return SearchResultsInterface
     */
    private function getSyncHistory($productId, $parentId, $storeId)
    {
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter(History::FIELD_PRODUCT_ID, $productId, 'eq');
        $searchCriteriaBuilder->addFilter(History::FIELD_PARENT_ID, $parentId, 'eq');
        $searchCriteriaBuilder->addFilter(History::FIELD_STORE_ID, $storeId, 'eq');
        $searchCriteria = $searchCriteriaBuilder->create();

        $sortOrderBuilder = $this->objectManager->get(SortOrderBuilder::class);
        $sortOrderBuilder->setField(HistoryResourceModel::ENTITY_ID);
        $sortOrderBuilder->setDirection(SortOrder::SORT_DESC);
        $sortOrder = $sortOrderBuilder->create();

        $searchCriteria->setSortOrders([
            $sortOrder
        ]);

        $syncHistoryRepository = $this->instantiateHistoryRepository();

        return $syncHistoryRepository->getList($searchCriteria);
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
