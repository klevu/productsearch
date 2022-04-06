<?php

namespace Klevu\Search\Api;

use Klevu\Search\Model\Klevu\Klevu as KlevuSync;
use Magento\Store\Api\Data\StoreInterface;

interface KlevuSyncRepositoryInterface
{
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
