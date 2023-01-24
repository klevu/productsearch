<?php

namespace Klevu\Search\Service\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Account\IntegrationStatusInterface;
use Klevu\Search\Api\Service\Sync\SyncNowInterface;
use Klevu\Search\Exception\MissingSyncEntityIds;
use Klevu\Search\Exception\StoreNotIntegratedException;
use Klevu\Search\Exception\StoreSyncDisabledException;
use Klevu\Search\Exception\SyncRequestFailedException;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Product\Sync;
use Klevu\Search\Model\Product\SyncFactory as ProductSyncFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class SyncNow implements SyncNowInterface
{
    /**
     * @var string[]
     */
    private static $EXCLUDED_PRODUCT_TYPES = [
        Configurable::TYPE_CODE,
    ];
    /**
     * @var ProductSyncFactory
     */
    private $syncFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var IntegrationStatusInterface
     */
    private $integrationStatus;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param ProductSyncFactory $syncFactory
     * @param StoreManagerInterface $storeManager
     * @param IntegrationStatusInterface $integrationStatus
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductSyncFactory $syncFactory,
        StoreManagerInterface $storeManager,
        IntegrationStatusInterface $integrationStatus,
        ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository
    ) {
        $this->syncFactory = $syncFactory;
        $this->storeManager = $storeManager;
        $this->integrationStatus = $integrationStatus;
        $this->scopeConfig = $scopeConfig;
        $this->productRepository = $productRepository;
    }

    /**
     * @param array $entityIds
     * @param int $storeId
     *
     * @return void
     * @throws MissingSyncEntityIds
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     * @throws StoreNotIntegratedException
     * @throws StoreSyncDisabledException
     * @throws SyncRequestFailedException
     */
    public function execute(array $entityIds, $storeId)
    {
        if (!$storeId || !is_int($storeId)) {
            throw new InvalidArgumentException(
                __('Invalid Store ID Provided.')
            );
        }
        $store = $this->storeManager->getStore($storeId);
        $this->validateStore($store);

        $entityIds = $this->getValidEntities($entityIds);
        /** @var Sync $sync */
        $sync = $this->syncFactory->create();
        $success = $sync->runProductSync($store, $entityIds);
        if (!$success) {
            throw new SyncRequestFailedException(
                __('Sync request failed. Please check error logs for more details.')
            );
        }
    }

    /**
     * @param StoreInterface $store
     *
     * @return void
     * @throws StoreNotIntegratedException
     * @throws StoreSyncDisabledException
     */
    private function validateStore(StoreInterface $store)
    {
        if (!$this->integrationStatus->isIntegrated($store)) {
            throw new StoreNotIntegratedException(
                __(
                    'Requested store %1 is not integrated with Klevu. Sync can not be triggered or scheduled.',
                    $store->getCode()
                )
            );
        }
        if (!$this->isSyncEnabled($store)) {
            throw new StoreSyncDisabledException(
                __('Sync for store is %1 disabled.', $store->getCode())
            );
        }
    }

    /**
     * @param StoreInterface $store
     *
     * @return bool
     */
    private function isSyncEnabled(StoreInterface $store)
    {
        return $this->scopeConfig->isSetFlag(
            ConfigHelper::XML_PATH_PRODUCT_SYNC_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $store->getId()
        );
    }

    /**
     * @param array $entityIds
     *
     * @return array
     * @throws MissingSyncEntityIds
     */
    private function getValidEntities(array $entityIds)
    {
        $entityIds = array_filter($entityIds);
        if (empty($entityIds)) {
            throw new MissingSyncEntityIds(
                __('No entity IDs provided for product sync.')
            );
        }
        $entityIds = array_filter($entityIds, function ($id) {
            if (!is_string($id)) {
                return false;
            }
            $ids = explode('-', $id);

            if (!isset(
                $ids[Sync::SYNC_IDS_PARENT_ID_KEY],
                $ids[Sync::SYNC_IDS_PRODUCT_ID_KEY],
                $ids[Sync::SYNC_IDS_ROW_ID_KEY]
            )) {
                return false;
            }

            try {
                $product = $this->productRepository->getById($ids[Sync::SYNC_IDS_PRODUCT_ID_KEY]);
                $return = !in_array(
                    $product->getTypeId(),
                    static::$EXCLUDED_PRODUCT_TYPES,
                    true
                );
            } catch (NoSuchEntityException $e) {
                $return = true;
            }

            return $return;
        });
        if (empty($entityIds)) {
            throw new MissingSyncEntityIds(
                __('No valid entity IDs provided for product sync.')
            );
        }

        return $entityIds;
    }
}
