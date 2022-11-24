<?php

namespace Klevu\Search\Model\Product;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Model\Category\MagentoCategoryActions as Klevu_MagentoCategoryActions;
use Klevu\Search\Model\Category\KlevuCategoryActions as Klevu_KlevuCategoryActions;
use Klevu\Search\Model\Klevu\HelperManager as KlevuHelperManager;
use Klevu\Search\Model\Klevu\KlevuFactory;
use Klevu\Search\Model\Product\MagentoProductActionsInterface as Klevu_MagentoProductActions;
use Klevu\Search\Model\Product\KlevuProductActionsInterface as Klevu_KlevuProductActions;
use Klevu\Search\Model\Sync as KlevuSync;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\App\Area as AppArea;
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
     */
    const RECORDS_PER_PAGE = 100;
    const NOTIFICATION_GLOBAL_TYPE = "product_sync";
    const NOTIFICATION_STORE_TYPE_PREFIX = "product_sync_store_";

    /**
     * @var \Magento\Framework\Model\Resource
     */
    protected $_frameworkModelResource;
    /**
     * @var \Klevu\Search\Model\Session
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
        // abstract parent
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
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
     */
    public function run()
    {
        try {
            /* mark for update special price product */
            // Sync Data only for selected store from config wizard
            $firstSync = $this->_searchModelSession->getFirstSync();
            $this->_searchModelSession->setKlevuFailedFlag(0);

            if (!empty($firstSync)) {
                /** @var Store $store */
                $onestore = $this->_storeModelStoreManagerInterface->getStore($firstSync);
                if (!$this->_klevuProductActions->setupSession($onestore)) {
                    return;
                }

                $this->syncData($onestore);
                $this->runCategory($onestore);
                $this->reset();

                return;
            }

            if ($this->isRunning(2)) {
                // Stop if another copy is already running
                $this->log(LoggerConstants::ZEND_LOG_INFO, "Stopping because another copy is already running.");

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
        } catch (\Exception $e) {
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
     * @param Store|int $store If passed, will only update products for the given store.
     *
     * @return array
     */
    public function syncStoreView($store)
    {
        if (!$this->_klevuProductActions->setupSession($store)) {
            return;
        }

        $this->syncData($store);
        $this->runCategory($store);
        $this->reset();
        $registry = $this->_klevuSyncModel->getRegistry();
        $records_count['numberOfRecord_add'] = $registry->registry("numberOfRecord_add");
        $records_count['numberOfRecord_update'] = $registry->registry("numberOfRecord_update");
        $records_count['numberOfRecord_delete'] = $registry->registry("numberOfRecord_delete");

        return $records_count;
    }

    /**
     * @param StoreInterface $store
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
        } catch (\Exception $e) {
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
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function runCron()
    {
        try {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, "Sync action performed through Magento Cron");
            /* mark for update special price product */
            //$this->_magentoProductActions->markProductForUpdate();

            // Sync Data only for selected store from config wizard
            $firstSync = $this->_searchModelSession->getFirstSync();
            $this->_searchModelSession->setKlevuFailedFlag(0);

            if (!empty($firstSync)) {
                /** @var Store $store */
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
                if (!in_array($storeCode, $websiteList[$websiteId], true)) {
                    $websiteList[$websiteId][] = $storeCode;
                }
            }

            foreach ($websiteList as $storeList) {
                $this->_klevuSyncModel->executeSubProcess(
                    'klevu:syncstore:storecode ' . implode(",", $storeList)
                );
            }
            // update rating flag after all store view sync
        } catch (\Exception $e) {
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
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function syncData($store)
    {
        $currentAreaCode = $this->_appState->getAreaCode();
        switch ($currentAreaCode) {
            case AppArea::AREA_FRONTEND:
                break;

            case '':
                $this->_appState->setAreaCode('frontend');
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

        $config = $this->_searchHelperConfig;
        if (!$config->isProductSyncEnabled($store->getId())) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_INFO,
                sprintf("Product Sync found disabled for %s (%s).", $store->getWebsite()->getName(), $store->getName())
            );

            return;
        }
        $firstSync = $this->_searchModelSession->getFirstSync();
        if ($this->_searchHelperConfig->isRatingSyncEnable($store->getId())) {
            try {
                $rating_upgrade_flag = $this->_searchHelperConfig->getRatingUpgradeFlag($store);
                if ($rating_upgrade_flag == 0) {
                    $this->_magentoProductActions->updateProductsRating($store);
                    // update rating flag after all store view sync
                    $this->_searchHelperConfig->saveRatingUpgradeFlag(1, $store);
                }
            } catch (\Exception $e) {
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
            sprintf("Starting sync for %s (%s).", $store->getWebsite()->getName(), $store->getName())
        );

        $actions = ['delete', 'update', 'add'];
        $errors = 0;

        foreach ($actions as $action) {
            if ($this->rescheduleIfOutOfMemory()) {
                return;
            }
            $productIds = $this->getProductsIds($action, $store);
            $method = $action . "Products";
            $products = array_values($productIds);  //resetting key index
            $total = count($productIds);
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_INFO,
                sprintf("Found %d products to %s.", $total, $action)
            );
            $pages = ceil($total / static::RECORDS_PER_PAGE);
            for ($page = 1; $page <= $pages; $page++) {
                if ($this->rescheduleIfOutOfMemory()) {
                    return;
                }
                $offset = ($page - 1) * static::RECORDS_PER_PAGE;
                $result = $this->_magentoProductActions->$method(
                    array_slice($products, $offset, static::RECORDS_PER_PAGE)
                );
                if ($result !== true) {
                    $errors++;
                    $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, sprintf(
                        "Errors occurred while attempting to %s products %d - %d: %s",
                        $action,
                        $offset + 1,
                        ($offset + static::RECORDS_PER_PAGE <= $total) ? $offset + static::RECORDS_PER_PAGE : $total,
                        $result
                    ));
                }
            }
        }
        $this->_searchHelperData->log(
            LoggerConstants::ZEND_LOG_INFO,
            sprintf("Finished sync for %s (%s).", $store->getWebsite()->getName(), $store->getName())
        );

        // Enable Klevu Search after the first sync
        if (!$config->isExtensionEnabled($store) && !empty($firstSync)) {
            $config->setExtensionEnabledFlag(true, $store);
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, sprintf(
                "Automatically enabled Klevu Search on Frontend for %s (%s).",
                $store->getWebsite()->getName(),
                $store->getName()
            ));
        }
    }

    /**
     * @param string $action
     * @param StoreInterface|null $store
     *
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getProductsIds($action = null, $store = null)
    {
        $productIds = [];
        if (null === $action) {
            return $productIds;
        }

        $currentStore = $this->_storeModelStoreManagerInterface->getStore();
        if (null === $store || null === $store->getId()) {
            $store = $currentStore;
        } elseif ($store->getId() !== $currentStore->getId()) {
            $this->_storeModelStoreManagerInterface->setCurrentStore($store->getId());
        }

        try {
            switch ($action) {
                case "delete":
                    $productIds = $this->_magentoProductActions->deleteProductCollection($store);
                    break;
                case "update":
                    $productIds = $this->_magentoProductActions->updateProductCollection($store);
                    break;
                case "add":
                    $productIds = $this->_magentoProductActions->addProductCollection($store);
                    break;
            }
        } catch (\Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf(
                    "Error in collecting product ids for action %s - %s",
                    $action,
                    $e->getMessage()
                )
            );
        }

        return $productIds;
    }

    /**
     * Run the product sync manually, creating a cron schedule entry
     * to prevent other syncs from running.
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
        } catch (\Exception $e) {
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
     * @param Store|null $store
     * @return string|null
     */
    public function runCategory($store)
    {
        $website = $store->getWebsite();
        if (!$this->_searchHelperConfig->getCategorySyncEnabledFlag($store->getId())) {
            $msg = sprintf(
                "Category Sync option found disabled for %s (%s).",
                $website->getName(),
                $store->getName()
            );
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, $msg);

            return $msg;
        }
        $this->_searchHelperData->log(
            LoggerConstants::ZEND_LOG_INFO,
            sprintf(
                "Starting sync for category %s (%s).",
                $website->getName(),
                $store->getName()
            )
        );

        // replace below code
        //$actions = $this->_klevuMagentoCategoryAction->getCategorySyncDataActions($store);
        $actions = ['delete', 'update', 'add'];

        $errors = 0;
        foreach ($actions as $key => $action) {
            if ($this->rescheduleIfOutOfMemory()) {
                return null;
            }

            $method = $action . "Category";
            $category_pages = $this->_klevuMagentoCategoryAction->getCategorySyncDataActions($store, $action);
            $total = count($category_pages);
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_INFO,
                sprintf(
                    "Found %d category Pages to %s.",
                    $total,
                    $action
                )
            );
            $pages = ceil($total / static ::RECORDS_PER_PAGE);
            for ($page = 1; $page <= $pages; $page++) {
                if ($this->rescheduleIfOutOfMemory()) {
                    return null;
                }
                $offset = ($page - 1) * static ::RECORDS_PER_PAGE;
                $result = $this->_klevuMagentoCategoryAction->$method(
                    array_slice($category_pages, $offset, static ::RECORDS_PER_PAGE)
                );
                if ($result !== true) {
                    $errors++;
                    $this->_searchHelperData->log(
                        LoggerConstants::ZEND_LOG_ERR,
                        sprintf(
                            "Errors occurred while attempting to %s categories pages %d - %d: %s",
                            $action,
                            $offset + 1,
                            ($offset + static ::RECORDS_PER_PAGE <= $total)
                                ? $offset + static ::RECORDS_PER_PAGE
                                : $total,
                            $result
                        )
                    );
                }
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
     * @return KlevuSync
     */
    public function schedule($time = "now")
    {
        return $this->_klevuSyncModel->schedule();
    }

    /**
     * @param int $copies
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
     * @param string $level
     * @param string $message
     * @return KlevuSync
     */
    public function log($level, $message)
    {
        return $this->_klevuSyncModel->log($level, $message);
    }

    /**
     * @param int $store_id
     * @return mixed
     */
    public function isExtensionConfigured($store_id)
    {
        return $this->_searchHelperConfig->isExtensionConfigured($store_id);
    }
}
