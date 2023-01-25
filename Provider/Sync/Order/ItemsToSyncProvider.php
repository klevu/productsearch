<?php

namespace Klevu\Search\Provider\Sync\Order;

use Klevu\Search\Api\Provider\Sync\Order\ItemsToSyncProviderInterface;
use Klevu\Search\Model\Order\Sync;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Sales\Api\Data\OrderItemExtension;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Psr\Log\LoggerInterface;

class ItemsToSyncProvider implements ItemsToSyncProviderInterface
{
    const KLEVU_SYNC_TABLE = 'main_table';
    const ORDER_ITEM_TABLE = 'order_item';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var OrderItemExtensionFactory
     */
    private $orderItemExtensionFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderItemExtensionFactory $orderItemExtensionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        OrderItemRepositoryInterface $orderItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderItemExtensionFactory $orderItemExtensionFactory,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->orderItemRepository = $orderItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderItemExtensionFactory = $orderItemExtensionFactory;
        $this->logger = $logger;
    }

    /**
     * @param int|null $storeId
     * @param int|null $orderId
     * @param int|null $status
     *
     * @return OrderItemInterface[]
     */
    public function getItems(
        $storeId = null,
        $orderId = null,
        $status = Sync::SYNC_QUEUE_ITEM_WAITING
    ) {
        $itemsToSend = [];
        $select = $this->getItemSelect($storeId, $orderId, $status);
        $connection = $this->resourceConnection->getConnection();
        $stmt = $connection->query($select);
        try {
            $itemsToSend = $stmt->fetchAll();
        } catch (\Zend_Db_Statement_Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $itemsToSend ? $this->convertOrderItemDataToObjects($itemsToSend) : [];
    }

    /**
     * @param int|null $storeId
     * @param int|null $orderId
     * @param int|null $status
     *
     * @return Select
     */
    public function getItemSelect(
        $storeId = null,
        $orderId = null,
        $status = Sync::SYNC_QUEUE_ITEM_WAITING
    ) {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from(
            [static::KLEVU_SYNC_TABLE => $this->resourceConnection->getTableName('klevu_order_sync')]
        );
        $select->join(
            [static::ORDER_ITEM_TABLE => $this->resourceConnection->getTableName('sales_order_item')],
            static::ORDER_ITEM_TABLE . '.item_id = ' . static::KLEVU_SYNC_TABLE . '.order_item_id',
            []
        );
        if (null !== $storeId) {
            $select->where(static::ORDER_ITEM_TABLE . '.store_id = ?', $storeId);
        }
        if (null !== $orderId) {
            $select->where(static::ORDER_ITEM_TABLE . '.order_id = ?', $orderId);
        }
        if (null !== $status) {
            $select->where(static::KLEVU_SYNC_TABLE . '.send = ?', $status);
        }
        $select->order(static::KLEVU_SYNC_TABLE . '.order_item_id ASC');

        return $select;
    }

    /**
     * @param array $itemsToSend
     *
     * @return OrderItemInterface[]
     */
    public function convertOrderItemDataToObjects(array $itemsToSend)
    {
        $itemIds = array_filter(
            array_map(static function ($row) {
                return isset($row['order_item_id']) ? $row['order_item_id'] : null;
            }, $itemsToSend)
        );

        $this->searchCriteriaBuilder->addFilter('item_id', $itemIds, 'in');
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $results = $this->orderItemRepository->getList($searchCriteria);
        $itemList = $results->getItems();

        return $this->addSyncExtensionAttributes($itemList, $itemsToSend);
    }

    /**
     * @param array $itemList
     * @param array $itemsToSend
     *
     * @return array
     */
    private function addSyncExtensionAttributes(array $itemList, array $itemsToSend)
    {
        array_walk($itemList, function (OrderItemInterface $item) use ($itemsToSend) {
            $filterItemsToAttach = array_filter($itemsToSend, static function ($itemToSend) use ($item) {
                return isset($itemToSend['order_item_id']) &&
                    ($itemToSend['order_item_id'] === $item->getItemId());
            });
            $keys = array_keys($filterItemsToAttach);
            $itemToAttach = isset($keys[0], $filterItemsToAttach[$keys[0]]) ? $filterItemsToAttach[$keys[0]] : null;
            if ($itemToAttach) {
                $extensionAttributes = $item->getExtensionAttributes();
                if (!$extensionAttributes) {
                    /** @var OrderItemExtension $extensionAttribute */
                    $extensionAttributes = $this->orderItemExtensionFactory->create();
                }
                $extensionAttributes->setKlevuOrderSync($itemToAttach);
                $item->setExtensionAttributes($extensionAttributes);
            }
        });

        return $itemList;
    }
}
