<?php

namespace Klevu\Search\Service\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Account\IntegrationStatusInterface;
use Klevu\Search\Api\Service\Sync\Product\DeleteInNextCronInterface;
use Klevu\Search\Api\Service\Sync\Product\UpdateInNextCronInterface;
use Klevu\Search\Api\Service\Sync\ScheduleSyncInterface;
use Klevu\Search\Exception\MissingSyncEntityIds;
use Klevu\Search\Exception\StoreNotIntegratedException;
use Klevu\Search\Exception\StoreSyncDisabledException;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Product\Sync;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Schedule implements ScheduleSyncInterface
{
    /**
     * @var string[]
     */
    private static $EXCLUDED_PRODUCT_TYPES = [
        Configurable::TYPE_CODE,
    ];
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var UpdateInNextCronInterface
     */
    private $updateInNextCron;
    /**
     * @var DeleteInNextCronInterface
     */
    private $deleteInNextCron;
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
     * @param StoreManagerInterface $storeManager
     * @param UpdateInNextCronInterface $updateInNextCron
     * @param DeleteInNextCronInterface $deleteInNextCron
     * @param IntegrationStatusInterface $integrationStatus
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        UpdateInNextCronInterface $updateInNextCron,
        DeleteInNextCronInterface $deleteInNextCron,
        IntegrationStatusInterface $integrationStatus,
        ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository
    ) {
        $this->storeManager = $storeManager;
        $this->updateInNextCron = $updateInNextCron;
        $this->deleteInNextCron = $deleteInNextCron;
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
     * @throws StoreNotIntegratedException
     * @throws StoreSyncDisabledException
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

        $entityIds = $this->validate($entityIds);
        $throwException = false;

        try {
            $idsToUpdate = $this->getRowIdsToUpdate($entityIds);
            $this->updateInNextCron->execute($idsToUpdate, [$store->getId()]);
        } catch (\InvalidArgumentException $e) {
            $throwException = true;
        }
        try {
            $idsToDelete = $this->getIdsToDelete($entityIds);
            $this->deleteInNextCron->execute($idsToDelete, [$store->getId()]);
        } catch (\InvalidArgumentException $e) {
            if ($throwException) {
                // only throw the exception if both actions fail
                throw new MissingSyncEntityIds(
                    __('No sync actions occurred.')
                );
            }
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
    private function validate(array $entityIds)
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

    /**
     * @param string[] $productIds
     *
     * @return array
     */
    private function getRowIdsToUpdate(array $productIds)
    {
        $rowIds = array_map(static function ($productId) {
            $ids = explode('-', $productId);

            return isset($ids[Sync::SYNC_IDS_ROW_ID_KEY]) ? $ids[Sync::SYNC_IDS_ROW_ID_KEY] : '0';
        }, $productIds);

        return array_filter($rowIds, static function ($rowId) {
            return (int)$rowId !== 0;
        });
    }

    /**
     * @param string[] $productIds
     *
     * @return array
     */
    private function getIdsToDelete(array $productIds)
    {
        $filteredIds = array_filter($productIds, static function ($rowId) {
            $ids = explode('-', $rowId);
            if (!isset($ids[Sync::SYNC_IDS_ROW_ID_KEY])) {
                return true;
            }
            return (int)$ids[Sync::SYNC_IDS_ROW_ID_KEY] === 0;
        });

        $idsToDelete = array_map(static function ($productId) {
            $ids = explode('-', $productId);
            if (!isset($ids[Sync::SYNC_IDS_PARENT_ID_KEY], $ids[Sync::SYNC_IDS_PRODUCT_ID_KEY])) {
                return [];
            }

            return [
                $ids[Sync::SYNC_IDS_PARENT_ID_KEY] . '-' . $ids[Sync::SYNC_IDS_PRODUCT_ID_KEY] => [
                    'parent_id' => $ids[Sync::SYNC_IDS_PARENT_ID_KEY],
                    'product_id' => $ids[Sync::SYNC_IDS_PRODUCT_ID_KEY],
                ]
            ];
        }, $filteredIds);

        return array_merge([], ...$idsToDelete);
    }
}
