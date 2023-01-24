<?php

namespace Klevu\Search\Repository;

use InvalidArgumentException;
use Klevu\Search\Api\KlevuProductSyncHistoryRepositoryInterface;
use Klevu\Search\Api\Data\HistoryInterface as SyncHistoryInterface;
use Klevu\Search\Exception\Sync\Product\CouldNotDeleteHistoryException;
use Klevu\Search\Exception\Sync\Product\CouldNotSaveHistoryException;
use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Model\Product\Sync\HistoryFactory as SyncHistoryFactory;
use Klevu\Search\Model\Product\Sync\ResourceModel\History as SyncHistoryResourceModel;
use Klevu\Search\Model\Product\Sync\ResourceModel\History\Collection as SyncHistoryCollection;
use Klevu\Search\Model\Product\Sync\ResourceModel\History\CollectionFactory as SyncHistoryCollectionFactory;
use Klevu\Search\Model\Source\NextAction;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class KlevuProductSyncHistoryRepository implements KlevuProductSyncHistoryRepositoryInterface
{
    /**
     * @var SyncHistoryFactory
     */
    private $syncHistoryFactory;
    /**
     * @var SyncHistoryResourceModel
     */
    private $historyResourceModel;
    /**
     * @var SearchResultsFactory
     */
    private $searchResultsFactory;
    /**
     * @var SyncHistoryCollectionFactory
     */
    private $syncHistoryCollectionFactory;
    /**
     * @var NextAction
     */
    private $nextAction;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SyncHistoryFactory $syncHistoryFactory
     * @param SyncHistoryResourceModel $historyResourceModel
     * @param SearchResultsFactory $searchResultsFactory
     * @param SyncHistoryCollectionFactory $syncHistoryCollectionFactory
     * @param NextAction $nextAction
     * @param LoggerInterface $logger
     */
    public function __construct(
        SyncHistoryFactory $syncHistoryFactory,
        SyncHistoryResourceModel $historyResourceModel,
        SearchResultsFactory $searchResultsFactory,
        SyncHistoryCollectionFactory $syncHistoryCollectionFactory,
        NextAction $nextAction,
        LoggerInterface $logger
    ) {
        $this->syncHistoryFactory = $syncHistoryFactory;
        $this->historyResourceModel = $historyResourceModel;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->syncHistoryCollectionFactory = $syncHistoryCollectionFactory;
        $this->nextAction = $nextAction;
        $this->logger = $logger;
    }

    /**
     * @param int $id
     *
     * @return SyncHistoryInterface
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $history = $this->create();
        $this->historyResourceModel->load($history, $id);
        if (!$history->getId()) {
            throw new NoSuchEntityException(__('Requested ID %1 does not exist.', $id));
        }

        return $history;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->getFilteredCollection($searchCriteria);
        $collection->load();

        list($items, $size) = $this->getSearchResultData($collection, $searchCriteria);

        /** @var SearchResultsInterface $searchResult */
        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($items);
        $searchResult->setTotalCount($size);

        return $searchResult;
    }

    /**
     * @return SyncHistoryInterface
     */
    public function create()
    {
        return $this->syncHistoryFactory->create();
    }

    /**
     * @param SyncHistoryInterface $syncHistory
     *
     * @return SyncHistoryInterface
     * @throws AlreadyExistsException|LocalizedException
     * @throws CouldNotSaveHistoryException
     * @throws NoSuchEntityException
     */
    public function save(SyncHistoryInterface $syncHistory)
    {
        $this->validateEntityData($syncHistory);
        try {
            $this->historyResourceModel->save($syncHistory);
        } catch (LocalizedException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->logger->error(__('%s: %s', __METHOD__, $exception->getMessage()));
            throw new CouldNotSaveHistoryException(
                __('Could not save sync history: %1', $exception->getMessage())
            );
        }

        return $this->getById((int)$syncHistory->getId());
    }

    /**
     * Save multiple records
     *
     * @param array[] $records
     *
     * @return int The number of affected rows.
     * @throws CouldNotSaveHistoryException
     */
    public function insert(array $records)
    {
        $this->validateInsertData($records);

        $connection = $this->historyResourceModel->getConnection();
        $table = $this->historyResourceModel->getTable(SyncHistoryResourceModel::TABLE);

        try {
            return $connection->insertMultiple($table, $records);
        } catch (\Zend_Db_Exception $exception) {
            throw new CouldNotSaveHistoryException(
                __('Could not save sync history: %1', $exception->getMessage())
            );
        }
    }

    /**
     * @param SyncHistoryInterface $syncHistory
     *
     * @return bool
     * @throws CouldNotDeleteHistoryException
     */
    public function delete(SyncHistoryInterface $syncHistory)
    {
        try {
            $this->historyResourceModel->delete($syncHistory);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteHistoryException(
                __('Could not delete sync history: %1', $exception->getMessage())
            );
        }

        return true;
    }

    /**
     * @param int $id
     *
     * @return bool
     * @throws CouldNotDeleteHistoryException
     * @throws NoSuchEntityException
     */
    public function deleteById($id)
    {
        return $this->delete(
            $this->getById($id)
        );
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return SyncHistoryCollection
     */
    private function getFilteredCollection(SearchCriteriaInterface $searchCriteria)
    {
        /** @var SyncHistoryCollection $collection */
        $collection = $this->syncHistoryCollectionFactory->create();

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $conditionType = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$conditionType => $filter->getValue()]);
            }
        }
        /** @var SortOrder $sortOrder */
        foreach ((array)$searchCriteria->getSortOrders() as $sortOrder) {
            $field = $sortOrder->getField();
            $direction = ($sortOrder->getDirection() === SortOrder::SORT_ASC)
                ? SortOrder::SORT_ASC
                : SortOrder::SORT_DESC;
            $collection->addOrder($field, $direction);
        }

        $currentPage = $searchCriteria->getCurrentPage();
        $pageSize = $searchCriteria->getPageSize();
        if ($currentPage && $pageSize) {
            $collection->setCurPage($currentPage);
            $collection->setPageSize($pageSize);
        }

        return $collection;
    }

    /**
     * @param SyncHistoryCollection $collection
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return array
     */
    private function getSearchResultData(
        SyncHistoryCollection $collection,
        SearchCriteriaInterface $searchCriteria
    ) {
        $lastPageNumber = $collection->getLastPageNumber();
        $currentPage = $searchCriteria->getCurrentPage();
        $pageSize = $searchCriteria->getPageSize();
        /*
         * If a collection page is requested that does not exist, Magento reverts to get the first page
         * of that collection using this plugin \Magento\Theme\Plugin\Data\Collection::afterGetCurPage.
         * We do not want that behaviour here, return empty result instead.
         * Only do this where currentPage and pageSize are set in searchCriteria
         */
        $invalidPage = $currentPage && $pageSize && $lastPageNumber < $currentPage;

        return [
            $invalidPage ? [] : $collection->getItems(),
            $invalidPage ? 0 : $collection->getSize()
        ];
    }

    /**
     * @param SyncHistoryInterface $syncHistory
     *
     * @return void
     */
    private function validateEntityData(SyncHistoryInterface $syncHistory)
    {
        $productId = $syncHistory->getProductId();
        if (!$productId || !is_numeric($productId) || !(int)$productId) {
            throw new InvalidArgumentException(
                __(
                    'Product ID is a required field and is not set or is an invalid type. %1 provided',
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    is_object($productId) ? get_class($productId) : gettype($productId)
                )
            );
        }
        $storeId = $syncHistory->getStoreId();
        if (!$storeId || !is_numeric($storeId) || !(int)$storeId) {
            throw new InvalidArgumentException(
                __(
                    'Store ID is a required field and is not set or is an invalid type. %1 provided',
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    is_object($storeId) ? get_class($storeId) : gettype($storeId)
                )
            );
        }
        $action = $syncHistory->getAction();
        if (!$action || !is_numeric($action) || !array_key_exists($action, $this->nextAction->getActions())) {
            throw new InvalidArgumentException(
                __(
                    'Action is a required field and is not set or is an invalid type. %1 provided',
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    is_object($action) ? get_class($action) : gettype($action)
                )
            );
        }
    }

    /**
     * @param array[] $records
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateInsertData(array $records)
    {
        foreach ($records as $record) {
            if (!isset($record[History::FIELD_PRODUCT_ID])) {
                throw new InvalidArgumentException(
                    __(
                        'Exception %1: Product ID is required, but missing from the request.',
                        __METHOD__
                    )
                );
            }
            if (!is_numeric($record[History::FIELD_PRODUCT_ID])) {
                throw new InvalidArgumentException(
                    __(
                        'Exception %1: Product ID is invalid. %2 provided, int expected.',
                        __METHOD__,
                        is_object($record[History::FIELD_PRODUCT_ID])
                            ? get_class($record[History::FIELD_PRODUCT_ID])
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                            : gettype($record[History::FIELD_PRODUCT_ID])
                    )
                );
            }
            if (!isset($record[History::FIELD_STORE_ID])) {
                throw new InvalidArgumentException(
                    __(
                        'Exception %1: Store Id is required, but missing from the request.',
                        __METHOD__
                    )
                );
            }
            if (!is_numeric($record[History::FIELD_STORE_ID])) {
                throw new InvalidArgumentException(
                    __(
                        'Exception %1: Store Id is invalid. %2 provided, int expected.',
                        __METHOD__,
                        is_object($record[History::FIELD_STORE_ID])
                            ? get_class($record[History::FIELD_STORE_ID])
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                            : gettype($record[History::FIELD_STORE_ID])
                    )
                );
            }
            if (!isset($record[History::FIELD_ACTION])) {
                throw new InvalidArgumentException(
                    __(
                        'Exception %1: Api Action is required, but missing from the request.',
                        __METHOD__
                    )
                );
            }
            $nextActions = $this->nextAction->getActions();
            if (!array_key_exists($record[History::FIELD_ACTION], $nextActions)) {
                throw new InvalidArgumentException(
                    __(
                        'Exception %1: Api Action is invalid. %2 provided, one of %3 expected.',
                        __METHOD__,
                        is_object($record[History::FIELD_ACTION])
                            ? get_class($record[History::FIELD_ACTION])
                            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                            : gettype($record[History::FIELD_ACTION]),
                        implode(',', array_keys($nextActions))
                    )
                );
            }
            if (isset($record[History::FIELD_MESSAGE]) && !is_string($record[History::FIELD_MESSAGE])) {
                throw new InvalidArgumentException(
                    __(
                        'Exception %1: Message in sync data to be recorded must be a string.',
                        __METHOD__
                    )
                );
            }
        }
    }
}
