<?php

namespace Klevu\Search\Repository;

use InvalidArgumentException;
use Klevu\Search\Api\Data\KlevuSyncEntityInterface;
use Klevu\Search\Api\Data\SyncEntitySearchResultsInterface;
use Klevu\Search\Api\KlevuSyncRepositoryInterface;
use Klevu\Search\Exception\Sync\Product\DeleteSyncDataException;
use Klevu\Search\Exception\Sync\Product\SaveSyncDataException;
use Klevu\Search\Model\Klevu\Klevu;
use Klevu\Search\Model\Klevu\KlevuFactory;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuResourceModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\CollectionFactory as KlevuSyncCollectionFactory;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\Collection as KlevuSyncCollection;
use Klevu\Search\Model\Klevu\SyncEntitySearchResults;
use Klevu\Search\Model\Klevu\SyncEntitySearchResultsFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
    /**
     * @var KlevuFactory
     */
    private $klevuFactory;
    /**
     * @var SyncEntitySearchResultsFactory
     */
    private $syncEntitySearchResultsFactory;

    /**
     * @param KlevuSyncCollectionFactory $KlevuSyncCollectionFactory
     * @param KlevuResourceModel $klevuResourceModel
     * @param LoggerInterface $logger
     * @param KlevuFactory|null $klevuFactory
     * @param SyncEntitySearchResultsFactory|null $syncEntitySearchResultsFactory
     */
    public function __construct(
        KlevuSyncCollectionFactory $KlevuSyncCollectionFactory,
        KlevuResourceModel $klevuResourceModel,
        LoggerInterface $logger,
        KlevuFactory $klevuFactory = null,
        SyncEntitySearchResultsFactory $syncEntitySearchResultsFactory = null
    ) {
        $this->KlevuSyncCollectionFactory = $KlevuSyncCollectionFactory;
        $this->klevuResourceModel = $klevuResourceModel;
        $this->logger = $logger;
        $objectManager = ObjectManager::getInstance();
        $this->klevuFactory = $klevuFactory ?:
            $objectManager->get(KlevuFactory::class);
        $this->syncEntitySearchResultsFactory = $syncEntitySearchResultsFactory ?:
            $objectManager->get(SyncEntitySearchResultsFactory::class);
    }

    /**
     * @param int $rowId
     *
     * @return KlevuSyncEntityInterface|DataObject
     * @throws NoSuchEntityException
     */
    public function get($rowId)
    {
        $syncModel = $this->create();
        $this->klevuResourceModel->load($syncModel, $rowId);
        if (!$syncModel || !$syncModel->getId()) {
            throw new NoSuchEntityException(
                __(
                    'No entity found with ID: %1',
                    $rowId
                )
            );
        }

        return $syncModel;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return SyncEntitySearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->getFilteredCollection($searchCriteria);
        $collection->load();

        /** @var SyncEntitySearchResults $searchResult */
        $searchResult = $this->syncEntitySearchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }

    /**
     * @return KlevuSyncEntityInterface
     */
    public function create()
    {
        return $this->klevuFactory->create();
    }

    /**
     * @param KlevuSyncEntityInterface $klevu
     *
     * @return KlevuSyncEntityInterface
     * @throws AlreadyExistsException|LocalizedException
     * @throws NoSuchEntityException
     * @throws SaveSyncDataException
     */
    public function save(KlevuSyncEntityInterface $klevu)
    {
        $this->validateEntityData($klevu);
        try {
            $this->klevuResourceModel->save($klevu);
        } catch (LocalizedException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->logger->error(__('%s: %s', __METHOD__, $exception->getMessage()));
            throw new SaveSyncDataException(
                __('The Klevu Sync Entity could not be saved. %1', $exception->getMessage())
            );
        }

        return $this->get((int)$klevu->getId());
    }

    /**
     * @param KlevuSyncEntityInterface $klevu
     *
     * @return void
     * @throws DeleteSyncDataException
     */
    public function delete(KlevuSyncEntityInterface $klevu)
    {
        try {
            $this->klevuResourceModel->delete($klevu);
        } catch (\Exception $exception) {
            throw new DeleteSyncDataException(
                __('The %1 Klevu Sync Entity could not be removed. %2', $klevu->getId(), $exception->getMessage())
            );
        }
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
            $klevuProductIds = $this->klevuResourceModel->getBatchDataForCollection(
                $klevuCollection,
                $store,
                $productIds,
                $lastEntityId
            );
        } catch (InvalidArgumentException $exception) {
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
        $klevuCollection->addFieldToFilter(Klevu::FIELD_STORE_ID, $store->getId());
        $klevuCollection->setOrder(Klevu::FIELD_ENTITY_ID, KlevuResourceModel\Collection::SORT_ORDER_DESC);
        $klevuCollection->setPageSize(1);

        $firstItem = $klevuCollection->getFirstItem();

        return $firstItem ? (int)$firstItem->getId() : 0;
    }

    /**
     * @param string|null $type
     *
     * @return void
     */
    public function clearKlevuCollection($type = Klevu::OBJECT_TYPE_PRODUCT)
    {
        $this->klevuCollection[$type] = null;
    }

    /**
     * @param StoreInterface $store
     * @param string|null $type
     *
     * @return KlevuSyncCollection
     * @throws InvalidArgumentException
     */
    private function initKlevuSyncCatalogCollection(
        StoreInterface $store,
        $type = Klevu::OBJECT_TYPE_PRODUCT
    ) {
        if (!isset($this->klevuCollection[$type])) {
            /** @var KlevuSyncCollection $klevuCollection */
            $klevuCollection = $this->KlevuSyncCollectionFactory->create();
            $klevuCollection->initCollectionByType($store, $type);
            $this->klevuCollection[$type] = $klevuCollection;
        }

        return $this->klevuCollection[$type];
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return KlevuSyncCollection
     */
    private function getFilteredCollection(SearchCriteriaInterface $searchCriteria)
    {
        /** @var KlevuSyncCollection $collection */
        $collection = $this->KlevuSyncCollectionFactory->create();

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
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());

        return $collection;
    }

    /**
     * @param KlevuSyncEntityInterface $klevu
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateEntityData(KlevuSyncEntityInterface $klevu)
    {
        $productId = $klevu->getProductId();
        if (!$productId || !is_numeric($productId) || !(int)$productId) {
            throw new InvalidArgumentException(
                __(
                    'Product ID is a required field and is not set or is an invalid type. %1 provided',
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    is_object($productId) ? get_class($productId) : gettype($productId)
                )
            );
        }
        $storeId = $klevu->getStoreId();
        if (!$storeId || !is_numeric($storeId) || !(int)$storeId) {
            throw new InvalidArgumentException(
                __(
                    'Store ID is a required field and is not set or is an invalid type. %1 provided',
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    is_object($storeId) ? get_class($storeId) : gettype($storeId)
                )
            );
        }
    }
}
