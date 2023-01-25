<?php

namespace Klevu\Search\Api;

use Klevu\Search\Api\Data\HistoryInterface;
use Klevu\Search\Api\Data\HistoryInterface as SyncHistoryInterface;
use Klevu\Search\Exception\Sync\Product\CouldNotDeleteHistoryException;
use Klevu\Search\Exception\Sync\Product\CouldNotSaveHistoryException;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;

interface KlevuProductSyncHistoryRepositoryInterface
{
    /**
     * @param int $id
     *
     * @return HistoryInterface
     * @throws NoSuchEntityException
     */
    public function getById($id);

    /**
     * @param SearchCriteriaInterface $criteria
     *
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @return HistoryInterface
     */
    public function create();

    /**
     * @param HistoryInterface $syncHistory
     *
     * @return HistoryInterface
     * @throws CouldNotSaveHistoryException
     */
    public function save(HistoryInterface $syncHistory);

    /**
     * @param SyncHistoryInterface[] $records
     *
     * @return int The number of affected rows.
     * @throws CouldNotSaveHistoryException
     */
    public function insert(array $records);

    /**
     * @param HistoryInterface $syncHistory
     *
     * @return bool
     * @throws CouldNotDeleteHistoryException
     */
    public function delete(HistoryInterface $syncHistory);

    /**
     * @param int $id
     *
     * @return bool
     * @throws CouldNotDeleteHistoryException
     */
    public function deleteById($id);
}
