<?php

namespace Klevu\Search\Service\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Data\HistoryInterface;
use Klevu\Search\Api\Service\Sync\Product\DeleteHistoryInterface;
use Klevu\Search\Api\Service\Sync\Product\GetHistoryLengthInterface;
use Klevu\Search\Api\KlevuProductSyncHistoryRepositoryInterface as ProductSyncHistoryRepository;
use Klevu\Search\Exception\Sync\Product\CouldNotDeleteHistoryException;
use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Model\Product\Sync\ResourceModel\History as HistoryResourceModel;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Psr\Log\LoggerInterface;

class DeleteHistory implements DeleteHistoryInterface
{
    const DELETE_PARAM_PRODUCT_ID = 'product_id';
    const DELETE_PARAM_PARENT_ID = 'parent_id';
    const DELETE_PARAM_STORE_ID = 'store_id';
    const PAGE_TO_DELETE = 2;
    const LOOP_BREAKER_LIMIT = 1000;

    /**
     * @var ProductSyncHistoryRepository
     */
    private $syncHistoryRepository;
    /**
     * @var GetHistoryLengthInterface
     */
    private $getHistoryLength;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProductSyncHistoryRepository $syncHistoryRepository
     * @param GetHistoryLengthInterface $getHistoryLength
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductSyncHistoryRepository $syncHistoryRepository,
        GetHistoryLengthInterface $getHistoryLength,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        LoggerInterface $logger
    ) {
        $this->syncHistoryRepository = $syncHistoryRepository;
        $this->getHistoryLength = $getHistoryLength;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->logger = $logger;
    }

    /**
     * @param array[] $records
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws CouldNotDeleteHistoryException
     */
    public function execute(array $records)
    {
        $count = 0;
        foreach ($records as $record) {
            $this->validateData($record);

            while (true) {
                $searchCriteria = $this->getSearchCriteria($record);
                $searchResults = $this->syncHistoryRepository->getList($searchCriteria);
                if (!count($searchResults->getItems())) {
                    break;
                }
                /** @var HistoryInterface $historyItem */
                foreach ($searchResults->getItems() as $historyItem) {
                    $this->syncHistoryRepository->delete($historyItem);
                }
                $count++;
                if ($count > static::LOOP_BREAKER_LIMIT) {
                    break;
                }
            }
        }
    }

    /**
     * @param array $deleteData
     *
     * @return SearchCriteriaInterface
     */
    private function getSearchCriteria(array $deleteData)
    {
        $this->searchCriteriaBuilder->addFilter(
            History::FIELD_PRODUCT_ID,
            $deleteData[static::DELETE_PARAM_PRODUCT_ID],
            'eq'
        );
        $this->searchCriteriaBuilder->addFilter(
            History::FIELD_PARENT_ID,
            $deleteData[static::DELETE_PARAM_PARENT_ID],
            'eq'
        );
        $this->searchCriteriaBuilder->addFilter(
            History::FIELD_STORE_ID,
            $deleteData[static::DELETE_PARAM_STORE_ID],
            'eq'
        );
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchCriteria->setPageSize(
            $this->getHistoryLength->execute((int)$deleteData[static::DELETE_PARAM_STORE_ID])
        );
        $searchCriteria->setCurrentPage(static::PAGE_TO_DELETE);

        $this->sortOrderBuilder->setField(HistoryResourceModel::ENTITY_ID);
        $this->sortOrderBuilder->setDirection(SortOrder::SORT_DESC);
        $sortOrder = $this->sortOrderBuilder->create();
        $searchCriteria->setSortOrders([
            $sortOrder
        ]);

        return $searchCriteria;
    }

    /**
     * @param array $deleteData
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateData(array $deleteData)
    {
        if (!isset($deleteData[static::DELETE_PARAM_PRODUCT_ID])) {
            throw new InvalidArgumentException(
                __(
                    'Exception %1: Product ID is missing from the sync data to be deleted',
                    __METHOD__
                )
            );
        }
        if (!isset($deleteData[static::DELETE_PARAM_STORE_ID])) {
            throw new InvalidArgumentException(
                __(
                    'Exception %1: Store ID is missing from the sync data to be deleted',
                    __METHOD__
                )
            );
        }
        if (!isset($deleteData[static::DELETE_PARAM_PARENT_ID])) {
            throw new InvalidArgumentException(
                __(
                    'Exception %1: Parent Product ID is missing from the sync data to be deleted',
                    __METHOD__
                )
            );
        }
    }
}
