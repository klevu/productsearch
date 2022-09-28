<?php
/**
 * Class \Klevu\Search\Model\Order\Sync
 * @method \Magento\Framework\Db\Adapter\Interface getConnection()
 */

namespace Klevu\Search\Model\Order;

use Klevu\Logger\Api\StoreScopeResolverInterface;
use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Api\Service\Sync\GetOrderSelectMaxLimitInterface;
use Klevu\Search\Api\Provider\Customer\CustomerIdProviderInterface;
use Klevu\Search\Api\Provider\Customer\SessionIdProviderInterface;
use Klevu\Search\Model\Sync as KlevuSync;
use Klevu\Search\Model\System\Config\Source\Order\Ip as OrderIP;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProduct;
use Klevu\Search\Helper\Price as Klevu_Helper_Price;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class Sync
 * @package Klevu\Search\Model\Order
 */
class Sync extends AbstractModel
{
    const LOCK_FILE = 'klevu_running_order_sync.lock';
    const LOCK_FILE_TTL = 3600;
    const SYNC_QUEUE_ITEM_WAITING = 0;
    const SYNC_QUEUE_ITEM_SUCCESS = 1;
    const SYNC_QUEUE_ITEM_FAILED = 2;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_frameworkModelResource;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;
    /**
     * @var \Magento\Sales\Model\Order\Item
     */
    protected $_modelOrderItem;
    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;
    /**
     * @var \Klevu\Search\Model\Api\Action\Producttracking
     */
    protected $_apiActionProducttracking;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_frameworkModelDate;
    /**
     * @var GroupedProduct
     */
    protected $_groupedProduct;

    const NOTIFICATION_TYPE = "order_sync";

    /**
     * @var \Klevu\Search\Model\Sync
     */
    protected $_klevuSyncModel;
    protected $_priceHelper;

    /**
     * @var StoreScopeResolverInterface
     */
    private $storeScopeResolver;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var string[]
     */
    private $storeCodesToRun = [];
    /**
     * @var SessionIdProviderInterface
     */
    private $sessionIdProvider;
    /**
     * @var CustomerIdProviderInterface
     */
    private $customerIdProvider;
    /**
     * @var GetOrderSelectMaxLimitInterface
     */
    private $getOrderSelectMaxLimit;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $frameworkModelResource,
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        \Magento\Sales\Model\Order\Item $modelOrderItem,
        \Magento\Sales\Model\Order\ItemFactory $modelOrderItemFactory,
        \Klevu\Search\Helper\Data $searchHelperData,
        \Klevu\Search\Model\Api\Action\Producttracking $apiActionProducttracking,
        \Magento\Framework\Stdlib\DateTime\DateTime $frameworkModelDate,
        GroupedProduct $groupedProduct,
        KlevuSync $klevuSyncModel,
        // abstract parent
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        Klevu_Helper_Price $klevuPriceHelper = null,
        array $data = [],
        StoreScopeResolverInterface $storeScopeResolver = null,
        DirectoryList $directoryList = null,
        SessionIdProviderInterface $sessionIdProvider = null,
        CustomerIdProviderInterface $customerIdProvider = null,
        GetOrderSelectMaxLimitInterface $getOrderSelectMaxLimit = null
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_klevuSyncModel = $klevuSyncModel;
        $this->_klevuSyncModel->setJobCode($this->getJobCode());
        $this->_frameworkModelResource = $frameworkModelResource;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_modelOrderItem = $modelOrderItem;
        $this->_modelOrderItemFactory = $modelOrderItemFactory;
        $this->_searchHelperData = $searchHelperData;
        $this->_apiActionProducttracking = $apiActionProducttracking;
        $this->_frameworkModelDate = $frameworkModelDate;
        $this->_groupedProduct = $groupedProduct;
        $this->_priceHelper = $klevuPriceHelper ?: ObjectManager::getInstance()->get(Klevu_Helper_Price::class);
        $this->storeScopeResolver = $storeScopeResolver
            ?: ObjectManager::getInstance()->get(StoreScopeResolverInterface::class);
        $this->directoryList = $directoryList ?: ObjectManager::getInstance()->get(DirectoryList::class);
        $this->sessionIdProvider = $sessionIdProvider ?: ObjectManager::getInstance()->get(SessionIdProviderInterface::class);
        $this->customerIdProvider = $customerIdProvider ?: ObjectManager::getInstance()->get(CustomerIdProviderInterface::class);
        $this->getOrderSelectMaxLimit = $getOrderSelectMaxLimit ?: ObjectManager::getInstance()->get(GetOrderSelectMaxLimitInterface::class);
    }

    /**
     * @return string
     */
    public function getJobCode()
    {
        return "klevu_search_order_sync";
    }

    /**
     * @return string[]
     */
    public function getStoreCodesToRun()
    {
        return $this->storeCodesToRun;
    }

    /**
     * @param array $storeCodesToRun
     * @return $this
     */
    public function setStoreCodesToRun($storeCodesToRun)
    {
        if (null === $storeCodesToRun) {
            $storeCodesToRun = [];
        }
        if (!is_array($storeCodesToRun)) {
            throw new \InvalidArgumentException(sprintf(
                'Store Codes argument must be an array or null; "%s" encountered',
                is_object($storeCodesToRun) ? get_class($storeCodesToRun) : gettype($storeCodesToRun)
            ));
        }

        $this->storeCodesToRun = array_map('trim', $storeCodesToRun);

        return $this;
    }

    /**
     * Add the items from the given order to the Order Sync queue. Does nothing if
     * Order Sync is disabled for the store that the order was placed in.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param bool $force Skip enabled check
     *
     * @return $this
     */
    public function addOrderToQueue(\Magento\Sales\Model\Order $order)
    {
        $groupItemUniqueIds = array();
        $items = [];
        $order_date = date_create("now")->format("Y-m-d H:i");
        $checkout_date = round(microtime(true) * 1000);
        $session_id = $this->sessionIdProvider->execute();
        $ip_address = $this->_searchHelperData->getIp();
        if ($order->getCustomerId()) {
            //logged in customer
            $order_email = $this->customerIdProvider->execute($order->getCustomerEmail());
        } else {
            //not logged in customer
            $billingAddress = $order->getBillingAddress();
            $order_email = $this->customerIdProvider->execute($billingAddress ? $billingAddress->getEmail() : '');
        }
        foreach ($order->getAllVisibleItems() as $item) {
            // For configurable products add children items only, for all other products add parents
            if ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                foreach ($item->getChildrenItems() as $child) {
                    if ($child->getId() != null) {
                        if ($this->checkItemId($child->getId()) !== true) {
                            $items[] = [$child->getId(), $session_id, $ip_address, $order_date, $order_email, $checkout_date, 0];
                        }
                    }
                }
                //For group product, only one item will be sync with id of Group Product
            } elseif ($item->getProductType() == GroupedProduct::TYPE_CODE) {
                if ($item->getId() != null && $this->checkItemId($item->getId()) !== true) {
                    $group_product_data = $item->getProductOptions();
                    if(isset($group_product_data["super_product_config"]["product_id"])) {
                        $idOfGroupProduct = $group_product_data["super_product_config"]["product_id"];
                        if (!empty($idOfGroupProduct)) {
                            if (!in_array($idOfGroupProduct, $groupItemUniqueIds)) {
                                $groupItemUniqueIds[$item->getId()] = (int)$idOfGroupProduct;
                            }
                        }
                    }
                }

            } else {
                if ($item->getId() != null) {
                    if ($this->checkItemId($item->getId()) !== true) {
                        $items[] = [$item->getId(), $session_id, $ip_address, $order_date, $order_email, $checkout_date, 0];
                    }
                }
            }
        }

        if (!empty($groupItemUniqueIds)) {
            //Only uniqueGroupItems
            foreach ($groupItemUniqueIds as $orderItemFirstId => $groupProductUniqueId) {
                if (!empty($groupProductUniqueId) && !empty($orderItemFirstId)) {
                    $items[] = [$orderItemFirstId, $session_id, $ip_address, $order_date, $order_email, $checkout_date, 0];
                }
            }
        }

        // in case of multiple addresses used for shipping
        // its possible that items object here is empty
        // if so, we do not add to the item.
        if (!empty($items)) {
            $this->addItemsToQueue($items);
        }
        return $this;
    }

    /**
     * Clear the Order Sync queue for the given store. If no store is given, clears
     * the queue for all stores.
     *
     * @param \Magento\Framework\Model\Store|int|null $store
     *
     * @return int
     */
    public function clearQueue($store = null)
    {
        $select = $this->_frameworkModelResource->getConnection()
            ->select()
            ->from(["k" => $this->_frameworkModelResource->getConnection()->getTableName("klevu_order_sync")]);
        if ($store) {
            $store = $this->_storeModelStoreManagerInterface->getStore($store);
            $select
                ->join(
                    ["i" => $this->_frameworkModelResource->getTableName("sales_order_item")],
                    "k.order_item_id = i.item_id",
                    ""
                )
                ->where("i.store_id = ?", $store->getId());
        }
        $result = $this->_frameworkModelResource->query($select->deleteFromSelect("k"));
        return $result->rowCount();
    }

    /**
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    public function run()
    {
        try {
            $currentStoreScopeStore = $this->storeScopeResolver->getCurrentStore();
            $currentStoreScopeStoreId = (int)$currentStoreScopeStore->getId();
        } catch (NoSuchEntityException $e) {
            $currentStoreScopeStoreId = 0;
        }

        try {
            if ($this->isRunning(2)) {
                // Stop if another copy is already running
                $this->log(LoggerConstants::ZEND_LOG_INFO, "Another copy is already running. Stopped.");
                return;
            }
            $this->createLockFile();

            $connection = $this->_frameworkModelResource->getConnection();
            $stores = $this->_storeModelStoreManagerInterface->getStores();
            if ($this->getStoreCodesToRun()) {
                $stores = array_filter($stores, function (StoreInterface $store) {
                    return in_array($store->getCode(), $this->getStoreCodesToRun(), true);
                });
            }

            foreach ($stores as $store) {
                if (!$this->isOrderSyncSendOptionEnabled($store->getId())) {
                    continue;
                }

                $this->storeScopeResolver->setCurrentStore($store);

                //Skip it if no API keys found
                if (!$this->getApiKey($store->getId())) {
                    $this->log(LoggerConstants::ZEND_LOG_INFO, sprintf("Order Sync :: No API Key found for Store Name:(%s), Website:(%s).", $store->getName(), $store->getWebsite()->getName()));
                    continue;
                }
                $this->log(LoggerConstants::ZEND_LOG_INFO, sprintf("Starting Order Sync for Store Name:(%s), Website:(%s).", $store->getName(), $store->getWebsite()->getName()));

                $maxBatchSize = (int)$this->_searchHelperConfig->getOrderSyncMaxBatchSize((int)$store->getId());
                $items_processed = 0;
                $items_synced = 0;
                $errors = 0;

                $select = $this->getSyncQueueSelect((int)$store->getId());
                $maxSelectLimit = $this->getOrderSelectMaxLimit->execute($store);
                do {
                    $selectLimit = ($maxBatchSize && $maxBatchSize < $maxSelectLimit)
                        ? $maxBatchSize
                        : $maxSelectLimit;
                    $select->limit($selectLimit, $errors);

                    $stmt = $connection->query($select);
                    $itemsToSend = $stmt->fetchAll();
                    if (!$itemsToSend) {
                        break;
                    }

                    $this->log(LoggerConstants::ZEND_LOG_DEBUG, sprintf(
                        'Processing next %s unsynced order items for Store Name:(%s), Website:(%s)',
                        $selectLimit,
                        $store->getName(),
                        $store->getWebsite()->getName()
                    ));

                    $this->processItemsToSync($itemsToSend, $store, $items_synced, $errors);
                    $items_processed += count($itemsToSend);
                } while (0 === $maxBatchSize || $items_processed < $maxBatchSize);

                $this->log(LoggerConstants::ZEND_LOG_INFO, sprintf("Order Sync finished for Store Name:(%s), Website:(%s) and %d Items synced.", $store->getName(), $store->getWebsite()->getName(), $items_synced));
            }
            $this->storeScopeResolver->setCurrentStoreById($currentStoreScopeStoreId);
        } catch (\Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            $this->log(LoggerConstants::ZEND_LOG_CRIT, sprintf("Order Sync:: Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            $this->storeScopeResolver->setCurrentStoreById($currentStoreScopeStoreId);
            throw $e;
        } finally {
            $this->clearLockFile();
        }
    }

    /**
     * Processes an array of sync items and hands each off to the sync method
     *
     * @param array[] $items
     * @param StoreInterface $store
     * @param int $items_synced Running total of successful sync calls
     * @param int $errors Running total of unsuccessful sync calls
     */
    private function processItemsToSync(
        array $items,
        StoreInterface $store,
        &$items_synced,
        &$errors
    ) {
        foreach ($items as $key => $value) {
            if ($this->rescheduleIfOutOfMemory()) {
                return;
            }

            $item = $this->_modelOrderItem;
            $item->setData([]);
            $item->load($value['order_item_id']);
            if (!$item->getId()) {
                $this->log(LoggerConstants::ZEND_LOG_ERR, sprintf("Order Item %d does not exist: Removed from sync!", $value['order_item_id']));
                $this->setItemFailedInQueue($value['order_item_id']);
                $errors++;

                continue;
            }

            //Instead of checking API keys, comparing the Store IDs
            //if ($this->getApiKey($item->getStoreId())) {
            if ($item->getStoreId() != $store->getStoreId()) {
                $this->log(LoggerConstants::ZEND_LOG_INFO, sprintf(
                    "Skipped Order Item %d: Item Store Id (%s) is different to current store id (%s)",
                    $value['order_item_id'],
                    $item->getStoreId(),
                    $store->getStoreId()
                ));
                $errors++;

                continue;
            }

            $result = $this->sync($item, $value['klevu_session_id'], $value['ip_address'], $value['date'], $value['idcode'], $value['checkoutdate']);
            if ($result === true) {
                $this->removeItemFromQueue($value['order_item_id']);
                $items_synced++;
            } else {
                $this->log(LoggerConstants::ZEND_LOG_WARN, sprintf('Skipped Order Item %d: %s', $value['order_item_id'], $result));
                $this->setItemFailedInQueue($value['order_item_id']);
            }
        }
    }

    /**
     * Sync the given order item to Klevu. Returns true on successful sync and
     * the error message otherwise.
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @param string $sess_id
     * @param string $ip_address
     * @param date $order_date
     * @param string $order_email
     * @param timestamp $checkout_date
     *
     * @return bool|string
     */
    protected function sync($item, $sess_id, $ip_address, $order_date, $order_email, $checkout_date)
    {
        try {
            if (!$this->getApiKey($item->getStoreId())) {
                return "Klevu Search is not configured for this store.";
            }
            $parent = null;
            if ($item->getParentItemId()) {
                //$parent = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Sales\Model\Order\Item')->load($item->getParentItemId());
                $parent = $this->_modelOrderItemFactory->create()->load($item->getParentItemId());
            }
            if ($item->getProductType() == GroupedProduct::TYPE_CODE) {
                $group_product_data = $item->getProductOptions();
                if(isset($group_product_data["super_product_config"]["product_id"])) {
                    $klevu_productId = $group_product_data["super_product_config"]["product_id"];
                } else {
                    return "Group product can not be loaded.";
                }
            }  else {
                $klevu_productId = $this->_searchHelperData->getKlevuProductId($item->getProductId(), ($parent) ? $parent->getProductId() : 0);
            }
            /**
             * if klevu_productId has value 11-7 then
             * klevu_productGroupId will be 11 and
             * klevu_productVariantId will be 7
             */
            $klevu_productGroupId = ($parent) ? $parent->getProductId() :  $klevu_productId;
            $klevu_productVariantId = ($parent) ? $item->getProductId() : $klevu_productId;

            // multiple currency store processing
            $store = $this->_storeModelStoreManagerInterface->getStore($item->getStoreId());

            $parentPrice = $parent ? $parent->getBasePriceInclTax() : null;
            $klevu_salePrice = $item->getBasePriceInclTax() ?: $parentPrice;

            $parentQuantity = $parent ? $parent->getQtyOrdered() : null;
            $quantity = $item->getQtyOrdered() ?: $parentQuantity;

            $klevu_current_store_currency_salePrice = $this->_priceHelper->convertPrice($klevu_salePrice,$store);
            $klevu_current_store_currency_salePrice_round =  $this->_priceHelper->roundPrice($klevu_current_store_currency_salePrice);

            $parameters = [
                "klevu_apiKey" => $this->getApiKey($item->getStoreId()),
                "klevu_type" => "checkout",
                "klevu_productId" => $klevu_productId,
                "klevu_unit" => $quantity,
                "klevu_salePrice" => $klevu_current_store_currency_salePrice_round,
                "klevu_currency" => $this->getStoreCurrency($item->getStoreId()),
                "klevu_shopperIP" => $this->processOrderIp($item->getOrderId(),$item->getStoreId()),
                "Klevu_sessionId" => $sess_id,
                "klevu_orderDate" => date_format(date_create($order_date), "Y-m-d"),
                "klevu_emailId" => $order_email,
                "klevu_storeTimezone" => $this->_searchHelperData->getStoreTimeZone($item->getStoreId()),
                "Klevu_clientIp" => $ip_address,
                "klevu_checkoutDate" => $checkout_date,
                "klevu_productPosition" => "1",
                "klevu_productGroupId" => $klevu_productGroupId,
                "klevu_productVariantId"=> $klevu_productVariantId
            ];
            $eventData = new DataObject([
                'order_item' => $item,
                'parameters' => $parameters,
            ]);
            $this->_eventManager->dispatch(
                'klevu_search_order_sync_send_before',
                ['event_data' => $eventData]
            );

            $this->_apiActionProducttracking->setStore($this->_storeModelStoreManagerInterface->getStore($item->getStoreId()));
            $response = $this->_apiActionProducttracking->execute($eventData->getData('parameters'));
            if ($response->isSuccess()) {
                return true;
            }
            return $response->getMessage();
        } catch (\Exception $e) {
            $this->log(LoggerConstants::ZEND_LOG_CRIT, sprintf("Order Sync:: Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            $this->log(LoggerConstants::ZEND_LOG_INFO, sprintf("Order Itemid %s skipped for Klevu Order Sync ", $item->getOrderId()));
        }
    }

    /**
     * Check if Order Sync Send is enabled for the given store to process Order Items.
     *
     * @param $store_id
     *
     * @return bool
     */
    protected function isOrderSyncSendOptionEnabled($store_id)
    {
        $is_enabled = $this->getData("is_enabled");
        if (!is_array($is_enabled)) {
            $is_enabled = [];
        }
        if (!isset($is_enabled[$store_id])) {
            $is_enabled[$store_id] = $this->_searchHelperConfig->isOrderSyncEnabled($store_id);
            $this->setData("is_enabled", $is_enabled);
        }
        return $is_enabled[$store_id];
    }

    /**
     * Return the JS API key for the given store.
     *
     * @param $store_id
     *
     * @return string|null
     */
    protected function getApiKey($store_id)
    {
        $api_keys = $this->getData("api_keys");
        if (!is_array($api_keys)) {
            $api_keys = [];
        }
        if (!isset($api_keys[$store_id])) {
            $api_keys[$store_id] = $this->_searchHelperConfig->getJsApiKey($store_id);
            $this->setData("api_keys", $api_keys);
        }
        return $api_keys[$store_id];
    }

    /**
     * Get the currency code for the given store.
     *
     * @param $store_id
     *
     * @return string
     */
    protected function getStoreCurrency($store_id)
    {
        $currencies = $this->getData("currencies");
        if (!is_array($currencies)) {
            $currencies = [];
        }
        if (!isset($currencies[$store_id])) {
            $currencies[$store_id] = $this->_storeModelStoreManagerInterface->getStore($store_id)->getDefaultCurrencyCode();
            $this->setData("currencies", $currencies);
        }
        return $currencies[$store_id];
    }

    /**
     * Return the customer IP for the given order.
     *
     * @param $order_id
     *
     * @param $store_id
     *
     * @return string
     */

    protected function getOrderIP($order_id,$store_id)
    {
        $configuredOrderIP = $this->_searchHelperConfig->getConfiguredOrderIP($store_id);
        if($configuredOrderIP === OrderIP::ORDER_X_FORWARDED_FOR) {
            $ip_col = OrderIP::ORDER_X_FORWARDED_FOR;
        } else {
            $ip_col = OrderIP::ORDER_REMOTE_IP;
        }
        $order_ips = $this->getData("order_ips");
        if (!is_array($order_ips)) {
            $order_ips = [];
        }
        if (!isset($order_ips[$order_id])) {
            $order_ips[$order_id] = $this->_frameworkModelResource->getConnection()->fetchOne(
                $this->_frameworkModelResource->getConnection()
                    ->select()
                    ->from(["order" => $this->_frameworkModelResource->getTableName("sales_order")], $ip_col)
                    ->where("order.entity_id = ?", $order_id)
            );
            $this->setData("order_ips", $order_ips);
        }
        return $order_ips[$order_id];
    }

    /**
     * Return the single customer IP for the multiple ips .
     *
     * @param $order_id
     *
     * @param $store_id
     *
     * @return string
     */
    private function processOrderIp($order_id,$store_id)
    {
		$ips = $this->getOrderIP($order_id,$store_id);
        $configuredOrderIP = $this->_searchHelperConfig->getConfiguredOrderIP($store_id);
        if (empty($ips)) {
            $this->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf("Shopper IP address [%s] value found NULL for Order ItemId [%s] , Store ID [%s]",
                    $configuredOrderIP, $order_id, $store_id
                ));
            return null;
        }

        if(strpos($ips, ",") !== false){
            $ips_array = explode(",",$ips);
            $trim_ip_value = array_map('trim',$ips_array);
            return array_shift($trim_ip_value);
        } else {
            return  $ips;
        }
    }

    /**
     * Return Order ItemId Already exits or not.
     *
     * @param $order_id
     *
     * @return boolean
     */
    protected function checkItemId($order_item_id)
    {
        if (!empty($order_item_id)) {
            $orderid = $this->_frameworkModelResource->getConnection()->fetchAll(
                $this->_frameworkModelResource->getConnection()
                    ->select()->from([
                        'order' => $this->_frameworkModelResource->getTableName("klevu_order_sync")
                    ])->where("order.order_item_id = ?", $order_item_id)
            );
            if (count($orderid) == 1) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Return a select statement for getting all items in the sync queue.
     *
     * @return Select
     */
    protected function getSyncQueueSelect($storeId = null)
    {
        $connection = $this->_frameworkModelResource->getConnection();
        $select = $connection->select();
        $select->from(
            ['main_table' => $this->_frameworkModelResource->getTableName('klevu_order_sync')]
        );

        if (null !== $storeId) {
            $select->join(
                ['order_item' => $this->_frameworkModelResource->getTableName('sales_order_item')],
                'order_item.item_id = main_table.order_item_id',
                []
            );
            $select->where('order_item.store_id = ?', $storeId);
        }

        $select->where('send = ?', static::SYNC_QUEUE_ITEM_WAITING);
        $select->order('main_table.order_item_id ASC');

        return $select;
    }

    /**
     * Add the given order item IDs to the sync queue.
     *
     * @param $order_item_ids
     *
     * @return int
     * @todo Move addItemsToQueue to ResourceModel
     */
    protected function addItemsToQueue($order_item_ids)
    {
        if (!is_array($order_item_ids)) {
            $order_item_ids = [$order_item_ids];
        }
        return $this->_frameworkModelResource->getConnection()->insertArray(
            $this->_frameworkModelResource->getTableName("klevu_order_sync"),
            ["order_item_id", "klevu_session_id", "ip_address", "date", "idcode", "checkoutdate", "send"],
            $order_item_ids
        );
    }

    /**
     * Remove the given item from the sync queue.
     *
     * @param $order_item_id
     *
     * @return bool
     * @todo Move removeItemFromQueue to ResourceModel
     */
    protected function removeItemFromQueue($order_item_id)
    {
        $where = sprintf("(order_item_id = %s)", $order_item_id);
        return $this->_frameworkModelResource->getConnection()->update(
                $this->_frameworkModelResource->getTableName("klevu_order_sync"),
                ["send" => static::SYNC_QUEUE_ITEM_SUCCESS],
                $where
            ) === 1;
    }

    /**
     * @param $order_item_id
     *
     * @return bool
     * @todo Move setItemFailedInQueue to ResourceModel
     */
    private function setItemFailedInQueue($order_item_id)
    {
        $where = sprintf("(order_item_id = %s)", $order_item_id);

        return $this->_frameworkModelResource->getConnection()->update(
                $this->_frameworkModelResource->getTableName("klevu_order_sync"),
                ["send" => self::SYNC_QUEUE_ITEM_FAILED],
                $where
            ) === 1;
    }

    /**
     * Delete Adminhtml notifications for Order Sync.
     *
     * @return $this
     */
    protected function deleteNotifications()
    {
        $this->_frameworkModelResource->getConnection()->delete(
            $this->_frameworkModelResource->getTableName('klevu_search_notification'),
            ["type" => static::NOTIFICATION_TYPE]
        );
        return $this;
    }

    //compatibility
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
        if ($this->lockFileExists()) {
            if (!$this->lockFileExpired()) {
                return true;
            }

            $this->clearLockFile();
        }

        return $this->_klevuSyncModel->isRunning($copies);
    }

    /**
     * Tests whether lock file for this process exists
     *
     * @return bool
     * @throws FileSystemException
     */
    private function lockFileExists()
    {
        return file_exists($this->getLockFilePath());
    }

    /**
     * Checks whether existing lock file is older than TTL
     * Note: will return false if file does not exist
     *
     * @return bool
     * @throws FileSystemException
     */
    private function lockFileExpired()
    {
        if (!$this->lockFileExists()) {
            return false;
        }

        return (time() - static::LOCK_FILE_TTL) > filemtime($this->getLockFilePath());
    }

    /**
     * Creates or updates filemtime of lock file for this process
     *
     * @throws FileSystemException
     */
    public function createLockFile()
    {
        $lockFilePath = $this->getLockFilePath();

        if (!touch($lockFilePath)) {
            throw new FileSystemException(__('Could not create lock file at "%1"', $lockFilePath));
        }
    }

    /**
     * Removes lock file for process if exists
     *
     * @throws FileSystemException
     */
    public function clearLockFile()
    {
        if (!$this->lockFileExists()) {
            return;
        }

        $lockFilePath = $this->getLockFilePath();
        if (!unlink($lockFilePath)) {
            throw new FileSystemException(__('Could not clear lock file at "%1"', $lockFilePath));
        }
    }

    /**
     * Returns absolute location of lock file path for process
     *
     * @return string
     * @throws FileSystemException
     */
    private function getLockFilePath()
    {
        return $this->directoryList->getPath(DirectoryList::VAR_DIR)
            . DIRECTORY_SEPARATOR
            . static::LOCK_FILE;
    }

    /**
     * @return bool
     */
    public function rescheduleIfOutOfMemory()
    {
        return $this->_klevuSyncModel->rescheduleIfOutOfMemory();
    }

    /**
     * @param $level
     * @param $message
     * @return KlevuSync
     */
    public function log($level, $message)
    {
        return $this->_klevuSyncModel->log($level, $message);
    }
}
