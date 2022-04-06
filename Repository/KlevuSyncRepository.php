<?php

namespace Klevu\Search\Repository;

use Klevu\Search\Api\KlevuSyncRepositoryInterface;
use Klevu\Search\Model\Klevu\Klevu;
use Klevu\Search\Model\Klevu\Klevu as KlevuSync;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuResourceModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\CollectionFactory as KlevuSyncCollectionFactory;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\Collection as KlevuSyncCollection;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;

class KlevuSyncRepository implements KlevuSyncRepositoryInterface
{
    const MAX_ITERATIONS = 100000;

    /**
     * @var KlevuSyncCollectionFactory
     */
    private $KlevuSyncCollectionFactory;
    /**
     * @var KlevuResourceModel
     */
    private $klevuResourceModel;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var KlevuSyncCollection[]
     */
    private $klevuCollection = [];

    public function __construct(
        KlevuSyncCollectionFactory $KlevuSyncCollectionFactory,
        KlevuResourceModel $klevuResourceModel,
        LoggerInterface $logger
    ) {
        $this->KlevuSyncCollectionFactory = $KlevuSyncCollectionFactory;
        $this->klevuResourceModel = $klevuResourceModel;
        $this->logger = $logger;
    }

    /**
     * @param StoreInterface $store
     * @param array|null $productIds
     * @param int|null $lastEntityId
     *
     * @return array
     */
    public function getProductIdsForUpdate(StoreInterface $store, $productIds = [], $lastEntityId = null)
    {
        return $this->getProductIds($store, $productIds, $lastEntityId, true);
    }

    /**
     * @param StoreInterface $store
     * @param array|null $productIds
     * @param int|null $lastEntityId
     * @param bool|null $isUpdate
     *
     * @return array
     */
    public function getProductIds(StoreInterface $store, $productIds = [], $lastEntityId = null, $isUpdate = false)
    {
        $klevuProductIds = [];
        try {
            $klevuCollection = $this->initKlevuSyncCatalogCollection($store);
            if ($isUpdate) {
                $klevuCollection->filterProductsToUpdate();
            }

            $klevuProductIds = $this->klevuResourceModel->getBatchDataForCollection($klevuCollection, $store, $productIds, $lastEntityId);
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $klevuProductIds;
    }

    /**
     * @param StoreInterface $store
     *
     * @return int
     */
    public function getMaxSyncId(StoreInterface $store)
    {
        /** @var KlevuResourceModel\Collection $klevuCollection */
        $klevuCollection = $this->KlevuSyncCollectionFactory->create();
        $klevuCollection->addFieldToFilter(Klevu::FIELD_TYPE, Klevu::OBJECT_TYPE_PRODUCT);
        $klevuCollection->addFieldToFilter(KlevuSync::FIELD_STORE_ID, $store->getId());
        $klevuCollection->setOrder(Klevu::FIELD_ENTITY_ID, KlevuResourceModel\Collection::SORT_ORDER_DESC);
        $klevuCollection->setPageSize(1);

        $firstItem = $klevuCollection->getFirstItem();

        return $firstItem ? (int)$firstItem->getId(): 0;
    }

    /**
     * @param string|null $type
     *
     * @return void
     */
    public function clearKlevuCollection($type = KlevuSync::OBJECT_TYPE_PRODUCT)
    {
        $this->klevuCollection[$type] = null;
    }

    /**
     * @param StoreInterface $store
     * @param string|null $type
     *
     * @return KlevuSyncCollection
     * @throws \InvalidArgumentException
     */
    private function initKlevuSyncCatalogCollection(
        StoreInterface $store,
        $type = KlevuSync::OBJECT_TYPE_PRODUCT
    ) {
        if (!isset($this->klevuCollection[$type])) {
            /** @var KlevuSyncCollection $klevuCollection */
            $klevuCollection = $this->KlevuSyncCollectionFactory->create();
            $klevuCollection->initCollectionByType($store, $type);
            $this->klevuCollection[$type] = $klevuCollection;
        }

        return $this->klevuCollection[$type];
    }
}
