<?php

namespace Klevu\Search\Model\Product;

use Exception;
use Klevu\Logger\Api\StoreScopeResolverInterface;
use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Api\Service\Sync\Product\GetRecordsPerPageInterface;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Category\MagentoCategoryActions as Klevu_MagentoCategoryActions;
use Klevu\Search\Model\Category\KlevuCategoryActions as Klevu_KlevuCategoryActions;
use Klevu\Search\Model\Klevu\HelperManager as KlevuHelperManager;
use Klevu\Search\Model\Klevu\KlevuFactory;
use Klevu\Search\Model\Product\MagentoProductActionsInterface as Klevu_MagentoProductActions;
use Klevu\Search\Model\Product\KlevuProductActionsInterface as Klevu_KlevuProductActions;
use Klevu\Search\Model\Session;
use Klevu\Search\Model\Source\NextAction;
use Klevu\Search\Model\Sync as KlevuSync;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Sync extends AbstractModel
{
    /**
     * It has been determined during development that Product Sync uses around
     * 120kB of memory for each product it syncs, or around 10MB of memory for
     * each 100 product page.
     * @deprecated in favour of a dedicated service to get the admin setting for this records per page.
     * @see \Klevu\Search\Service\Sync\Product\GetRecordsPerPage
     */
    const RECORDS_PER_PAGE = 100;
    const NOTIFICATION_GLOBAL_TYPE = "product_sync";
    const NOTIFICATION_STORE_TYPE_PREFIX = "product_sync_store_";
    const SYNC_IDS_PARENT_ID_KEY = 0;
    const SYNC_IDS_PRODUCT_ID_KEY = 1;
    const SYNC_IDS_ROW_ID_KEY = 2;

    /**
     * @var ResourceConnection
     */
    protected $_frameworkModelResource;
    /**
     * @var BackendSession
     */
    protected $_searchModelSession;
    /**
     * @var LoggerInterface
     */
    protected $_psrLogLoggerInterface;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * Not used: retained for backward compatibility
     * @var mixed
     */
    protected $_entity_value;
    /**
     * @var KlevuSync
     */
    protected $_klevuSyncModel;
    /**
     * @var ProductMetadataInterface
     */
    protected $_ProductMetadataInterface;
    /**
     * @var KlevuFactory
     */
    protected $_klevuFactory;
    /**
     * @var MagentoProductActionsInterface
     */
    protected $_magentoProductActions;
    /**
     * @var KlevuProductActionsInterface
     */
    protected $_klevuProductActions;
    /**
     * @var Klevu_MagentoCategoryActions
     */
    protected $_klevuMagentoCategoryAction;
    /**
     * @var Klevu_KlevuCategoryActions
     */
    protected $_klevuCategoryActions;
    /**
     * @var KlevuHelperManager
     */
    protected $_klevuHelperManager;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;
    /**
     * @var SearchHelepr
     */
    protected $_searchHelperData;
    /**
     * @var array
     */
    private $recordsPerPage = [];
    /**
     * @var GetRecordsPerPageInterface
     */
    private $getRecordsPerPage;
    /**
     * @var StoreScopeResolverInterface
     */
    private $storeScopeResolver;

    /**
     * @param KlevuFactory $klevuFactory
     * @param MagentoProductActionsInterface $magentoProductActions
     * @param KlevuProductActionsInterface $klevuProductActions
     * @param Klevu_MagentoCategoryActions $klevuMagentoCategoryAction
     * @param Klevu_KlevuCategoryActions $klevuCategoryActions
     * @param ResourceConnection $frameworkModelResource
     * @param BackendSession $searchModelSession
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param KlevuHelperManager $klevuHelperManager
     * @param RequestInterface $frameworkAppRequestInterface
     * @param ProductMetadataInterface $productMetadataInterface
     * @param KlevuSync $klevuSyncModel
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param GetRecordsPerPageInterface $getRecordsPerPage
     * @param StoreScopeResolverInterface|null $storeScopeResolver
     */
    public function __construct(
        KlevuFactory $klevuFactory,
        Klevu_MagentoProductActions $magentoProductActions,
        Klevu_KlevuProductActions $klevuProductActions,
        Klevu_MagentoCategoryActions $klevuMagentoCategoryAction,
        Klevu_KlevuCategoryActions $klevuCategoryActions,
        ResourceConnection $frameworkModelResource,
        BackendSession $searchModelSession,
        StoreManagerInterface $storeModelStoreManagerInterface,
        KlevuHelperManager $klevuHelperManager,
        RequestInterface $frameworkAppRequestInterface,
        ProductMetadataInterface $productMetadataInterface,
        KlevuSync $klevuSyncModel,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        GetRecordsPerPageInterface $getRecordsPerPage = null,
        StoreScopeResolverInterface $storeScopeResolver = null
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_magentoProductActions = $magentoProductActions;
        $this->_klevuProductActions = $klevuProductActions;
        $this->_klevuMagentoCategoryAction = $klevuMagentoCategoryAction;
        $this->_klevuCategoryActions = $klevuCategoryActions;
        $this->_klevuSyncModel = $klevuSyncModel;
        $this->_klevuSyncModel->setJobCode($this->getJobCode());
        $this->_frameworkModelResource = $frameworkModelResource;
        $this->_klevuHelperManager = $klevuHelperManager;
        $this->_searchHelperConfig = $this->_klevuHelperManager->getConfigHelper();
        $this->_searchHelperData = $this->_klevuHelperManager->getDataHelper();
        $this->_searchModelSession = $searchModelSession;
        $this->_psrLogLoggerInterface = $context->getLogger();
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_ProductMetadataInterface = $productMetadataInterface;
        $this->_klevuFactory = $klevuFactory;
        $this->getRecordsPerPage = $getRecordsPerPage
            ?: ObjectManager::getInstance()->get(GetRecordsPerPageInterface::class);
        $this->storeScopeResolver = $storeScopeResolver ?: ObjectManager::getInstance()->get(
            StoreScopeResolverInterface::class
        );
    }

    /**
     * @return string
     */
    public function getJobCode()
    {
        return "klevu_search_product_sync";
    }

    /**
     * Perform Product Sync on any configured stores, adding new products, updating modified and
     * deleting removed products since last sync.
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function run()
    {
        try {
            // Sync Data only for selected store from config wizard
            $firstSync = $this->_searchModelSession->getFirstSync();
            $this->_searchModelSession->setKlevuFailedFlag(0);

            if (!empty($firstSync)) {
                $oneStore = $this->_storeModelStoreManagerInterface->getStore($firstSync);
                if (!$this->_klevuProductActions->setupSession($oneStore)) {
                    return;
                }
                $this->syncData($oneStore);
                $this->runCategory($oneStore);
                $this->reset();

                return;
            }
            if ($this->isRunning(2)) {
                // Stop if another copy is already running
                $this->log(
                    LoggerConstants::ZEND_LOG_INFO,
                    "Stopping because another copy is already running."
                );

                return;
            }
            $stores = $this->_storeModelStoreManagerInterface->getStores();
            foreach ($stores as $store) {
                if (!$this->_klevuProductActions->setupSession($store)) {
                    continue;
                }
                $this->syncData($store);
                $this->runCategory($store);
                $this->reset();
            }
        } catch (Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf(
                    "Exception thrown in %s::%s - %s",
                    __CLASS__,
                    __METHOD__,
                    $e->getMessage()
                )
            );
            throw $e;
        }
    }

    /**
     * Sync store view data.
     *
     * @param StoreInterface|int $store If passed, will only update products for the given store.
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function syncStoreView($store)
    {
        $recordsCount = [];
        if (!$this->_klevuProductActions->setupSession($store)) {
            return $recordsCount;
        }
        $this->syncData($store);
        $this->runCategory($store);
        $this->reset();
        $registry = $this->_klevuSyncModel->getRegistry();
        $recordsCount['numberOfRecord_add'] = $registry->registry("numberOfRecord_add");
        $recordsCount['numberOfRecord_update'] = $registry->registry("numberOfRecord_update");
        $recordsCount['numberOfRecord_delete'] = $registry->registry("numberOfRecord_delete");

        return $recordsCount;
    }

    /**
     * @param StoreInterface $store
     *
     * @return bool
     */
    public function runStore($store)
    {
        try {
            if (!$this->_klevuProductActions->setupSession($store)) {
                return false;
            }
            $this->syncData($store);
            $this->runCategory($store);
            $this->reset();

            return true;
        } catch (Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf(
                    "Exception thrown in %s::%s - %s",
                    __CLASS__,
                    __METHOD__,
                    $e->getMessage()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function runCron()
    {
        try {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_INFO,
                "Sync action performed through Magento Cron"
            );

            // Sync Data only for selected store from config wizard
            $firstSync = $this->_searchModelSession->getFirstSync();
            $this->_searchModelSession->setKlevuFailedFlag(0);

            if (!empty($firstSync)) {
                $oneStore = $this->_storeModelStoreManagerInterface->getStore($firstSync);
                if (!$this->_klevuProductActions->setupSession($oneStore)) {
                    return;
                }
                $this->syncData($oneStore);
                $this->runCategory($oneStore);
                $this->reset();

                return;
            }
            if ($this->isRunning(2)) {
                // Stop if another copy is already running
                $this->log(
                    LoggerConstants::ZEND_LOG_INFO,
                    "Stopping because another copy is already running."
                );

                return;
            }
            $storeList = $this->_storeModelStoreManagerInterface->getStores();
            $websiteList = [];
            foreach ($storeList as $store) {
                $websiteId = $store->getWebsiteId();
                if (!isset($websiteList[$websiteId])) {
                    $websiteList[$websiteId] = [];
                }
                $storeCode = $store->getCode();
                if (in_array($storeCode, $websiteList[$websiteId], true)) {
                    continue;
                }
                $websiteList[$websiteId][] = $storeCode;
            }

            foreach ($websiteList as $storeList) {
                $this->_klevuSyncModel->executeSubProcess(
                    'klevu:syncstore:storecode ' . implode(",", $storeList)
                );
            }
        } catch (Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf(
                    "Exception thrown in %s::%s - %s",
                    __CLASS__,
                    __METHOD__,
                    $e->getMessage()
                )
            );
            throw $e;
        }
    }

    /**
     * @param StoreInterface $store
     * @param array $productIds
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function runProductSync(StoreInterface $store, array $productIds)
    {
        $self = $this;
        try {
            return $this->_appState->emulateAreaCode(
                AppArea::AREA_FRONTEND,
                function () use ($self, $store, $productIds) {
                    $originalStore = $this->storeScopeResolver->getCurrentStore();
                    $this->storeScopeResolver->setCurrentStore($store);

                    if (!$self->_klevuProductActions->setupSession($store)) {
                        $this->storeScopeResolver->setCurrentStore($originalStore);
                        return false;
                    }
                    $self->syncData($store, $productIds);
                    $self->reset();

                    $this->storeScopeResolver->setCurrentStore($originalStore);
                    return true;
                }
            );
        } catch (NoSuchEntityException $exception) {
            throw $exception;
        } catch (LocalizedException $exception) {
            $this->_logger->error(
                sprintf('Error in %s::%s - %s', __CLASS__, __METHOD__, $exception->getMessage())
            );
        }

        return false;
    }

    /**
     * @param StoreInterface $store
     * @param array|null $filterProductIds
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function syncData($store, $filterProductIds = [])
    {
        $currentAreaCode = $this->_appState->getAreaCode();
        switch ($currentAreaCode) {
            case AppArea::AREA_FRONTEND:
                break;

            case '':
                $this->_appState->setAreaCode(AppArea::AREA_FRONTEND);
                break;

            default:
                throw new \LogicException(sprintf(
                    'Product sync requires the Magento area to be "%s"; application already set to "%s"',
                    AppArea::AREA_FRONTEND,
                    $currentAreaCode
                ));
                break; // phpcs:ignore Squiz.PHP.NonExecutableCode.Unreachable
        }

        if ($this->rescheduleIfOutOfMemory()) {
            return;
        }
        $website = $store->getWebsite();
        if (!$this->_searchHelperConfig->isProductSyncEnabled($store->getId())) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_INFO,
                sprintf(
                    "Product Sync found disabled for %s (%s).",
                    $website->getName(),
                    $store->getName()
                )
            );

            return;
        }
        $firstSync = $this->_searchModelSession->getFirstSync();
        if ($this->_searchHelperConfig->isRatingSyncEnable($store->getId())) {
            try {
                if ((int)($this->_searchHelperConfig->getRatingUpgradeFlag($store)) === 0) {
                    $this->_magentoProductActions->updateProductsRating($store);
                    // update rating flag after all store view sync
                    $this->_searchHelperConfig->saveRatingUpgradeFlag(1, $store);
                }
            } catch (Exception $e) {
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_WARN,
                    sprintf("Unable to update rating attribute %s", $e->getMessage())
                );
            }
        }
        //set current store so will get proper bundle price
        $this->_storeModelStoreManagerInterface->setCurrentStore($store->getId());
        $this->_searchHelperData->log(
            LoggerConstants::ZEND_LOG_INFO,
            sprintf("Starting sync for %s (%s).", $website->getName(), $store->getName())
        );

        $isProductFilterApplied = (bool)count($filterProductIds);
        $trackProductIds = [];
        $actions = [
            NextAction::ACTION_DELETE,
            NextAction::ACTION_UPDATE,
            NextAction::ACTION_ADD
        ];
        foreach ($actions as $action) {
            if ($this->rescheduleIfOutOfMemory()) {
                return;
            }
            $productIds = $this->getProductsIds($action, $store, $filterProductIds);
            $this->syncProductsForAction($productIds, $action, $store);
            if ($isProductFilterApplied) {
                $trackProductIds[$action] = $productIds;
            }
        }
        if ($isProductFilterApplied) {
            $this->triggerSyncForProductsWithoutNextAction($filterProductIds, $trackProductIds, $store);
        }

        $this->_searchHelperData->log(
            LoggerConstants::ZEND_LOG_INFO,
            sprintf("Finished sync for %s (%s).", $website->getName(), $store->getName())
        );

        // Enable Klevu Search after the first sync
        if (!empty($firstSync) && !$this->_searchHelperConfig->isExtensionEnabled($store)) {
            $this->_searchHelperConfig->setExtensionEnabledFlag(true, $store);
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, sprintf(
                "Automatically enabled Klevu Search on Frontend for %s (%s).",
                $website->getName(),
                $store->getName()
            ));
        }
    }

    /**
     * @param string|null $action
     * @param StoreInterface|null $store
     * @param array|null $filterProductIds
     *
     * @return array
     */
    protected function getProductsIds($action = null, $store = null, $filterProductIds = [])
    {
        $return = [];
        $allowedActions = [NextAction::ACTION_ADD, NextAction::ACTION_DELETE, NextAction::ACTION_UPDATE];
        if (!in_array($action, $allowedActions, true)) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                __('$action argument is not one of the allowed values: %1', implode('-', $allowedActions))
            );
            return $return;
        }
        if (!($store instanceof StoreInterface)) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                __('$store argument must be instance of StoreInterface')
            );
            return $return;
        }

        try {
            $currentStore = $this->_storeModelStoreManagerInterface->getStore();
            if (null === $store || null === $store->getId()) {
                $store = $currentStore;
            } elseif ((int)$currentStore->getId() !== (int)$store->getId()) {
                $this->_storeModelStoreManagerInterface->setCurrentStore($store->getId());
            }
        } catch (NoSuchEntityException $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                $e->getMessage()
            );
            return $return;
        }
        // get product_ids if from the table row_id string which consists of parent_id, product_id, sync_id
        $productIds = $this->getFilteredProductIds($filterProductIds);
        try {
            switch ($action) {
                case NextAction::ACTION_DELETE:
                    $return = $this->_magentoProductActions->deleteProductCollection($store, $productIds);
                    break;
                case NextAction::ACTION_UPDATE:
                    $return = $this->_magentoProductActions->updateProductCollection($store, $productIds);
                    break;
                case NextAction::ACTION_ADD:
                    $return = $this->_magentoProductActions->addProductCollection($store, $productIds);
                    break;
            }
        } catch (Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf("Error in collecting product ids for action %s - %s", $action, $e->getMessage())
            );
        }
        if (count($filterProductIds)) {
            // filter out any extra products returned by passing just the product_id to the collection methods above.
            $idsToFilterReturn = $this->mapIdsToProductIds($filterProductIds);

            $return = array_filter($return, static function ($productId) use ($idsToFilterReturn) {
                return in_array($productId, $idsToFilterReturn, true);
            }, ARRAY_FILTER_USE_KEY);
        }

        return $return;
    }

    /**
     * @param array $filterProductIds
     *
     * @return array|null[]|string[]
     */
    private function getFilteredProductIds(array $filterProductIds)
    {
        return array_map(static function ($productIds) {
            return (strpos($productIds, '-') !== false) ?
                explode('-', $productIds)[self::SYNC_IDS_PRODUCT_ID_KEY] :
                null;
        }, $filterProductIds);
    }

    /**
     * @param string[] $uniqueIds
     *
     * @return string[]
     */
    private function mapIdsToProductIds(array $uniqueIds)
    {
        return array_map(static function ($id) {
            $ids = explode('-', $id);

            return $ids[self::SYNC_IDS_PARENT_ID_KEY] . '-' . $ids[self::SYNC_IDS_PRODUCT_ID_KEY];
        }, $uniqueIds);
    }

    /**
     * @param array $productIds
     * @param string $action
     * @param StoreInterface|string|int|null $store
     *
     * @return void
     */
    private function syncProductsForAction(array $productIds, $action, $store)
    {
        $total = count($productIds);
        $this->_searchHelperData->log(
            LoggerConstants::ZEND_LOG_INFO,
            sprintf("Found %d products to %s.", $total, $action)
        );
        if (!$total) {
            return;
        }
        $method = $action . "Products";
        $products = array_values($productIds); //resetting key index
        $recordsPerPage = $this->getRecordsPerPage($store);
        $pages = ceil($total / $recordsPerPage);
        for ($page = 1; $page <= $pages; $page++) {
            if ($this->rescheduleIfOutOfMemory()) {
                return;
            }
            $offset = ($page - 1) * $recordsPerPage;
            $result = $this->_magentoProductActions->$method(
                array_slice($products, $offset, $recordsPerPage)
            );
            if ($result !== true) {
                $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, sprintf(
                    "Errors occurred while attempting to %s products %d - %d: %s",
                    $action,
                    $offset + 1,
                    ($offset + $recordsPerPage <= $total) ? $offset + $recordsPerPage : $total,
                    $result
                ));
            }
        }
    }

    /**
     * @param array $filterProductIds
     * @param array $trackProductIds
     * @param StoreInterface|string|int|null $store
     *
     * @return void
     */
    private function triggerSyncForProductsWithoutNextAction(array $filterProductIds, array $trackProductIds, $store)
    {
        $productIdsToSync = $this->filterOutSyncedProducts($trackProductIds, $filterProductIds);

        $productsToDelete = $this->filterProductsNotInSyncTable($productIdsToSync);
        if (count($productsToDelete)) {
            $productsToDelete = $this->formatProductIds($productsToDelete);
            $this->syncProductsForAction($productsToDelete, NextAction::ACTION_DELETE, $store);
        }

        $productsToUpdate = $this->filterProductsInSyncTable($productIdsToSync);
        if (count($productsToUpdate)) {
            $productsToUpdate = $this->formatProductIds($productsToUpdate);
            $this->syncProductsForAction($productsToUpdate, NextAction::ACTION_UPDATE, $store);
        }
    }

    /**
     * @param array $syncedProductIds
     * @param array $filterProductIds
     *
     * @return array
     */
    private function filterOutSyncedProducts(array $syncedProductIds, array $filterProductIds)
    {
        $add = isset($syncedProductIds[NextAction::ACTION_ADD]) ?
            $syncedProductIds[NextAction::ACTION_ADD] :
            [];
        $delete = isset($syncedProductIds[NextAction::ACTION_DELETE]) ?
            $syncedProductIds[NextAction::ACTION_DELETE] :
            [];
        $update = isset($syncedProductIds[NextAction::ACTION_UPDATE]) ?
            $syncedProductIds[NextAction::ACTION_UPDATE] :
            [];
        $syncedProducts = array_merge([], $add, $delete, $update);

        return array_filter($filterProductIds, static function ($productId) use ($syncedProducts) {
            if (!is_string($productId)) {
                return false;
            }
            $ids = explode('-', $productId);
            $productId = $ids[self::SYNC_IDS_PARENT_ID_KEY] . '-' . $ids[self::SYNC_IDS_PRODUCT_ID_KEY];

            return !array_key_exists($productId, $syncedProducts);
        });
    }

    /**
     * @param array $productIdsToSync
     *
     * @return array
     */
    private function filterProductsInSyncTable(array $productIdsToSync)
    {
        return array_filter($productIdsToSync, static function ($productId) {
            if (!is_string($productId)) {
                return false;
            }
            $ids = explode('-', $productId);

            return $ids[self::SYNC_IDS_ROW_ID_KEY] !== '0';
        });
    }

    /**
     * @param array $productIdsToSync
     *
     * @return array
     */
    private function filterProductsNotInSyncTable(array $productIdsToSync)
    {
        return array_filter($productIdsToSync, static function ($productId) {
            if (!is_string($productId)) {
                return false;
            }
            $ids = explode('-', $productId);

            return $ids[self::SYNC_IDS_ROW_ID_KEY] === '0';
        });
    }

    /**
     * @param array $productIds
     *
     * @return array
     */
    private function formatProductIds(array $productIds)
    {
        $return = array_map(static function ($productId) {
            if (!is_string($productId)) {
                return [];
            }
            $ids = explode('-', $productId);

            return [
                $ids[self::SYNC_IDS_PARENT_ID_KEY] . '-' . $ids[self::SYNC_IDS_PRODUCT_ID_KEY] => [
                    'parent_id' => $ids[self::SYNC_IDS_PARENT_ID_KEY],
                    'product_id' => $ids[self::SYNC_IDS_PRODUCT_ID_KEY]
                ]
            ];
        }, $productIds);

        return array_merge([], ...$return);
    }

    /**
     * Run the product sync manually, creating a cron schedule entry
     * to prevent other syncs from running.
     *
     * @return void
     *
     */
    public function runManually()
    {
        $scheduler = $this->_klevuSyncModel->getScheduler();
        $operations = [
            "setJobCode" => $this->getJobCode(),
            "setStatus" => $scheduler->getStatusByCode('running'),
            "setExecutedAt" => $scheduler->getSchedulerTimeMysql()
        ];
        $schedule = $scheduler->manageSchedule($operations);

        try {
            $this->run();
        } catch (Exception $e) {
            $this->_psrLogLoggerInterface->error($e);
            $operations = [
                "setMessages" => $e->getMessage(),
                "setStatus" => $scheduler->getStatusByCode('error')
            ];
            $scheduler->manageSchedule($operations, $schedule);

            return;
        }
        $operations = [
            "setFinishedAt" => $scheduler->getSchedulerTimeMysql(),
            "setStatus" => $scheduler->getStatusByCode('success')
        ];
        $scheduler->manageSchedule($operations, $schedule);
    }

    /**
     * Remove any session specific data.
     *
     * @return $this
     */
    public function reset()
    {
        $this->unsetData('session_id');
        $this->unsetData('store');
        $this->unsetData('attribute_map');
        $this->unsetData('placeholder_image');
        $this->unsetData('category_paths');
        $this->unsetData('attribute_data');

        return $this;
    }

    /**
     * Perform Category Sync on any configured stores, adding new categories, updating modified and
     * deleting removed category since last sync.
     *
     * @param StoreInterface|null $store
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function runCategory($store)
    {
        $website = $store->getWebsite();
        if (!$this->_searchHelperConfig->getCategorySyncEnabledFlag($store->getId())) {
            $message = sprintf(
                "Category Sync option found disabled for %s (%s).",
                $website->getName(),
                $store->getName()
            );
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, $message);

            return $message;
        }
        $this->_searchHelperData->log(
            LoggerConstants::ZEND_LOG_INFO,
            sprintf(
                "Starting sync for category %s (%s).",
                $website->getName(),
                $store->getName()
            )
        );

        $actions = [
            NextAction::ACTION_DELETE,
            NextAction::ACTION_UPDATE,
            NextAction::ACTION_ADD
        ];

        $recordsPerPage = $this->getRecordsPerPage($store);
        foreach ($actions as $action) {
            if ($this->rescheduleIfOutOfMemory()) {
                return null;
            }
            $method = $action . "Category";
            $categoryPages = $this->_klevuMagentoCategoryAction->getCategorySyncDataActions($store, $action);
            $total = count($categoryPages);
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_INFO,
                sprintf("Found %d category Pages to %s.", $total, $action)
            );
            $pages = ceil($total / $recordsPerPage);
            for ($page = 1; $page <= $pages; $page++) {
                if ($this->rescheduleIfOutOfMemory()) {
                    return null;
                }
                $offset = ($page - 1) * $recordsPerPage;
                $result = $this->_klevuMagentoCategoryAction->$method(
                    array_slice($categoryPages, $offset, $recordsPerPage)
                );
                if ($result === true) {
                    continue;
                }
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_ERR,
                    sprintf(
                        "Errors occurred while attempting to %s categories pages %d - %d: %s",
                        $action,
                        $offset + 1,
                        ($offset + $recordsPerPage <= $total)
                            ? $offset + $recordsPerPage
                            : $total,
                        $result
                    )
                );
            }
        }
        $this->_searchHelperData->log(
            LoggerConstants::ZEND_LOG_INFO,
            sprintf(
                "Finished category page sync for %s (%s).",
                $website->getName(),
                $store->getName()
            )
        );

        return null;
    }

    /**
     * @param string $time
     *
     * @return KlevuSync
     */
    public function schedule($time = "now")
    {
        return $this->_klevuSyncModel->schedule();
    }

    /**
     * @param int $copies
     *
     * @return bool
     */
    public function isRunning($copies = 1)
    {
        return $this->_klevuSyncModel->isRunning($copies);
    }

    /**
     * @return bool
     */
    public function rescheduleIfOutOfMemory()
    {
        return $this->_klevuSyncModel->rescheduleIfOutOfMemory();
    }

    /**
     * @param int $level
     * @param string $message
     *
     * @return KlevuSync
     */
    public function log($level, $message)
    {
        return $this->_klevuSyncModel->log($level, $message);
    }

    /**
     * @param StoreInterface|string|int|null $storeId
     *
     * @return bool
     */
    public function isExtensionConfigured($storeId)
    {
        return $this->_searchHelperConfig->isExtensionConfigured($storeId);
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    private function getRecordsPerPage($store = null)
    {
        if ((is_object($store) && !($store instanceof StoreInterface))) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_WARN,
                sprintf('Object provided must be instance of StoreInterface, %s given', get_class($store))
            );
            $store = null;
        }
        $key = is_object($store) ? $store->getId() : $store;
        $key = $key ? (string)$key : (string)Store::DEFAULT_STORE_ID;
        if (empty($this->recordsPerPage[$key])) {
            $this->recordsPerPage[$key] = $this->getRecordsPerPage->execute($store);
        }

        return $this->recordsPerPage[$key];
    }
}
