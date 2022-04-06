<?php
/**
 * Class \Klevu\Search\Model\Product\Sync
 * @method \Magento\Framework\Db\Adapter\Interface getConnection()
 * @method \Magento\Store\Model\Store getStore()
 * @method string getKlevuSessionId()
 */

namespace Klevu\Search\Model\Product;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Model\Product\MagentoProductActionsInterface as Klevu_MagentoProductActions;
use Klevu\Search\Model\Product\KlevuProductActionsInterface as Klevu_KlevuProductActions;
use Klevu\Search\Model\Category\MagentoCategoryActions as Klevu_MagentoCategoryActions;
use Klevu\Search\Model\Category\KlevuCategoryActions as Klevu_KlevuCategoryActions;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Model\AbstractModel;
use \Magento\Cron\Model\Schedule;
use \Psr\Log\LoggerInterface;
use \Klevu\Search\Model\Sync as KlevuSync;

class Sync extends AbstractModel
{
    /**
     * @var \Magento\Framework\Model\Resource
     */
    protected $_frameworkModelResource;
    /**
     * @var \Klevu\Search\Model\Session
     */
    protected $_searchModelSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_psrLogLoggerInterface;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    protected $_entity_value;
    protected $_klevuSyncModel;

    public function __construct(
        \Klevu\Search\Model\Klevu\KlevuFactory $klevuFactory,
        Klevu_MagentoProductActions $magentoProductActions,
        Klevu_KlevuProductActions $klevuProductActions,
        Klevu_MagentoCategoryActions $klevuMagentoCategoryAction,
        Klevu_KlevuCategoryActions $klevuCategoryActions,
        \Magento\Framework\App\ResourceConnection $frameworkModelResource,
        \Magento\Backend\Model\Session $searchModelSession,
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Klevu\Search\Model\Klevu\HelperManager $klevuHelperManager,
        \Magento\Framework\App\RequestInterface $frameworkAppRequestInterface,
        \Magento\Framework\App\ProductMetadataInterface $productMetadataInterface,
        KlevuSync $klevuSyncModel,
        // abstract parent
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
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
     * It has been determined during development that Product Sync uses around
     * 120kB of memory for each product it syncs, or around 10MB of memory for
     * each 100 product page.
     */
    const RECORDS_PER_PAGE = 100;
    const NOTIFICATION_GLOBAL_TYPE = "product_sync";
    const NOTIFICATION_STORE_TYPE_PREFIX = "product_sync_store_";

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
            //$this->_magentoProductActions->markProductForUpdate();

            // Sync Data only for selected store from config wizard
            $firstSync = $this->_searchModelSession->getFirstSync();
            $this->_searchModelSession->setKlevuFailedFlag(0);

            if (!empty($firstSync)) {
                /** @var \Magento\Store\Model\Store $store */
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
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_CRIT,
                sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            throw $e;
        }
    }

    /**
     * Sync store view data.
     *
     * @param \Magento\Store\Model\Store|int $store If passed, will only update products for the given store.
     *
     * @return $this
     */
    public function syncStoreView($store)
    {
        if (!$this->_klevuProductActions->setupSession($store)) {
            return;
        }

        $this->syncData($store);
        $this->runCategory($store);
        $this->reset();
        $records_count['numberOfRecord_add'] = $this->_klevuSyncModel->getRegistry()->registry("numberOfRecord_add");
        $records_count['numberOfRecord_update'] = $this->_klevuSyncModel->getRegistry()->registry("numberOfRecord_update");
        $records_count['numberOfRecord_delete'] = $this->_klevuSyncModel->getRegistry()->registry("numberOfRecord_delete");

        return $records_count;
    }

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
                /** @var \Magento\Store\Model\Store $store */
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
                $this->log(LoggerConstants::ZEND_LOG_INFO, "Stopping because another copy is already running.");

                return;
            }

            $config = $this->_searchHelperConfig;

            $storeList = $this->_storeModelStoreManagerInterface->getStores();
            $websiteList = array();
            foreach ($storeList as $store) {
                if (!isset($websiteList[$store->getWebsiteId()])) {
                    $websiteList[$store->getWebsiteId()] = array();
                }
                $websiteList[$store->getWebsiteId()] = array_unique(
                    array_merge($websiteList[$store->getWebsiteId()], array($store->getCode()))
                );
            }

            foreach ($websiteList as $storeList) {
                $this->_klevuSyncModel->executeSubProcess('klevu:syncstore:storecode ' . implode(",", $storeList));
            }
            // update rating flag after all store view sync

        } catch (\Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage())
            );
            throw $e;
        }
    }

    public function syncData($store)
    {
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

        $actions = array('delete', 'update', 'add');
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
                $result = $this->_magentoProductActions->$method(array_slice($products, $offset,
                    static::RECORDS_PER_PAGE));
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
     * @param $action
     * @param $store
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getProductsIds($action = null, $store = null)
    {
        $productIds = [];
        if (is_null($action)) {
            return $productIds;
        }
        $storeId = $store->getId();
        if (is_null($storeId)) {
            $storeId = $this->_storeModelStoreManagerInterface->getStore()->getId();
        } elseif ($this->_storeModelStoreManagerInterface->getStore()->getId() != $storeId) {
            $this->_storeModelStoreManagerInterface->setCurrentStore($storeId);
        }

        try {
            switch ($action) {
                case "delete" :
                    $productIds = $this->_magentoProductActions->deleteProductCollection($store);
                    break;
                case "update" :
                    $productIds = $this->_magentoProductActions->updateProductCollection($store);
                    break;
                case "add" :
                    $productIds = $this->_magentoProductActions->addProductCollection($store);
                    break;
            }
        } catch (\Exception $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR,
                sprintf("Error in collecting product ids for action %s - %s", $action, $e->getMessage()));
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

        $operations = array(
            "setJobCode" => $this->getJobCode(),
            "setStatus" => $scheduler->getStatusByCode('running'),
            "setExecutedAt" => $scheduler->getSchedulerTimeMysql()
        );
        $schedule = $scheduler->manageSchedule($operations);

        try {
            $this->run();
        } catch (\Exception $e) {
            $this->_psrLogLoggerInterface->error($e);
            $operations = array(
                "setMessages" => $e->getMessage(),
                "setStatus" => $scheduler->getStatusByCode('error')
            );
            $scheduler->manageSchedule($operations, $schedule);

            return;
        }
        $operations = array(
            "setFinishedAt" => $scheduler->getSchedulerTimeMysql(),
            "setStatus" => $scheduler->getStatusByCode('success')
        );
        $scheduler->manageSchedule($operations, $schedule);

        return;
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
     * @param \Magento\Store\Model\Store|null $store
     */
    public function runCategory($store)
    {
        if (!$this->_searchHelperConfig->getCategorySyncEnabledFlag($store->getId())) {
            $msg = sprintf("Category Sync option found disabled for %s (%s).", $store->getWebsite()->getName(),
                $store->getName());
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, $msg);

            return $msg;
        }
        $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO,
            sprintf("Starting sync for category %s (%s).", $store->getWebsite()->getName(), $store->getName()));

        // replace below code
        //$actions = $this->_klevuMagentoCategoryAction->getCategorySyncDataActions($store);
        $actions = array('delete', 'update', 'add');

        $errors = 0;
        foreach ($actions as $key => $action) {
            if ($this->rescheduleIfOutOfMemory()) {
                return;
            }

            $method = $action . "Category";
            $category_pages = $this->_klevuMagentoCategoryAction->getCategorySyncDataActions($store, $action);
            $total = count($category_pages);
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO,
                sprintf("Found %d category Pages to %s.", $total, $action));
            $pages = ceil($total / static ::RECORDS_PER_PAGE);
            for ($page = 1; $page <= $pages; $page++) {
                if ($this->rescheduleIfOutOfMemory()) {
                    return;
                }
                $offset = ($page - 1) * static ::RECORDS_PER_PAGE;
                $result = $this->_klevuMagentoCategoryAction->$method(array_slice($category_pages, $offset,
                    static ::RECORDS_PER_PAGE));
                if ($result !== true) {
                    $errors++;
                    $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR,
                        sprintf("Errors occurred while attempting to %s categories pages %d - %d: %s", $action,
                            $offset + 1,
                            ($offset + static ::RECORDS_PER_PAGE <= $total) ? $offset + static ::RECORDS_PER_PAGE : $total,
                            $result));
                }
            }
        }
        $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO,
            sprintf("Finished category page sync for %s (%s).", $store->getWebsite()->getName(), $store->getName()));
    }

    //compatibility
    public function schedule($time = "now")
    {
        return $this->_klevuSyncModel->schedule();
    }

    public function isRunning($copies = 1)
    {
        return $this->_klevuSyncModel->isRunning($copies);
    }

    public function rescheduleIfOutOfMemory()
    {
        return $this->_klevuSyncModel->rescheduleIfOutOfMemory();
    }

    public function log($level, $message)
    {
        return $this->_klevuSyncModel->log($level, $message);
    }

    public function isExtensionConfigured($store_id)
    {
        return $this->_searchHelperConfig->isExtensionConfigured($store_id);
    }
}
