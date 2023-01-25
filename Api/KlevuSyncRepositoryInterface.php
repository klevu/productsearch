<?php

namespace Klevu\Search\Api;

use Klevu\Search\Api\Data\KlevuSyncEntityInterface;
use Klevu\Search\Exception\Sync\Product\DeleteSyncDataException;
use Klevu\Search\Exception\Sync\Product\SaveSyncDataException;
use Klevu\Search\Model\Klevu\Klevu as KlevuSync;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Store\Api\Data\StoreInterface;

interface KlevuSyncRepositoryInterface
{
    /**
     * @param int $rowId
     *
     * @return KlevuSyncEntityInterface
     */
    public function get($rowId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @return KlevuSyncEntityInterface
     */
    public function create();

    /**
     * @param KlevuSyncEntityInterface $klevu
     *
     * @return KlevuSyncEntityInterface
     * @throws NoSuchEntityException
     * @throws SaveSyncDataException
     */
    public function save(KlevuSyncEntityInterface $klevu);

    /**
     * @param KlevuSyncEntityInterface $klevu
     *
     * @return void
     * @throws DeleteSyncDataException
     */
    public function delete(KlevuSyncEntityInterface $klevu);

    /**
     * @param StoreInterface $store
     * @param array|null $productIds
     * @param int|null $lastEntityId
     * @param bool|null $isUpdate
     *
     * @return array
     */
    public function getProductIds(StoreInterface $store, $productIds = [], $lastEntityId = null, $isUpdate = false);

    /**
     * @param StoreInterface $store
     * @param array|null $productIds
     * @param int|null $lastEntityId
     *
     * @return array
     */
    public function getProductIdsForUpdate(StoreInterface $store, $productIds = [], $lastEntityId = null);

    /**
     * @param StoreInterface $store
     *
     * @return mixed
     */
    public function getMaxSyncId(StoreInterface $store);

    /**
     * @param string|null $type
     *
     * @return void
     */
    public function clearKlevuCollection($type = KlevuSync::OBJECT_TYPE_PRODUCT);
}
