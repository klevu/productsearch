<?php
/**
 * Class \Klevu\Search\Model\Order\Sync
 * @method \Magento\Framework\Db\Adapter\Interface getConnection()
 */

namespace Klevu\Search\Model\Order;

use Klevu\Search\Model\Sync as KlevuSync;
use Magento\Framework\Model\AbstractModel;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProduct;

/**
 * Class Sync
 * @package Klevu\Search\Model\Order
 */
class Sync extends AbstractModel
{

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
        array $data = []
    )
    {
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
    }

    public function getJobCode()
    {
        return "klevu_search_order_sync";
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
        $session_id = md5(session_id());
        $ip_address = $this->_searchHelperData->getIp();
        if ($order->getCustomerId()) {
            $order_email = "enc-" . md5($order->getCustomerEmail()); //logged in customer
        } else {
            $order_email = "enc-" . md5($order->getBillingAddress()->getEmail()); //not logged in customer
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

    public function run()
    {
        try {
            if ($this->isRunning(2)) {
                // Stop if another copy is already running
                $this->log(\Zend\Log\Logger::INFO, "Another copy is already running. Stopped.");
                return;
            }

            $stores = $this->_storeModelStoreManagerInterface->getStores();
            foreach ($stores as $store) {
                //Skip it if no API keys found
                if (!$this->getApiKey($store->getId())) {
                    $this->log(\Zend\Log\Logger::INFO, sprintf("Order Sync :: No API Key found for Store Name:(%s), Website:(%s).", $store->getName(), $store->getWebsite()->getName()));
                    continue;
                }
                $this->log(\Zend\Log\Logger::INFO, sprintf("Starting Order Sync for Store Name:(%s), Website:(%s).", $store->getName(), $store->getWebsite()->getName()));
                $items_synced = 0;
                $errors = 0;
                $item = $this->_modelOrderItem;
                $stmt = $this->_frameworkModelResource->getConnection()->query($this->getSyncQueueSelect());
                $itemsToSend = $stmt->fetchAll();
                foreach ($itemsToSend as $key => $value) {
                    if ($this->rescheduleIfOutOfMemory()) {
                        return;
                    }
                    $item->setData([]);
                    $item->load($value['order_item_id']);
                    if ($item->getId()) {
                        //Checking if Option Order Sync Send is enabled or not for such store item
                        if ($this->isOrderSyncSendOptionEnabled($item->getStoreId())) {
                            //Instead of checking API keys, comparing the Store IDs
                            //if ($this->getApiKey($item->getStoreId())) {
                            if ($item->getStoreId() == $store->getStoreId()) {
                                $result = $this->sync($item, $value['klevu_session_id'], $value['ip_address'], $value['date'], $value['idcode'], $value['checkoutdate']);
                                if ($result === true) {
                                    $this->removeItemFromQueue($value['order_item_id']);
                                    $items_synced++;
                                } else {
                                    $this->log(\Zend\Log\Logger::INFO, sprintf("Skipped Order Item %d: %s", $value['order_item_id'], $result));
                                    $errors++;
                                }
                            }
                        } else {
                            $this->log(\Zend\Log\Logger::ERR, sprintf("Skipped Order Item %d: Send Order Items to Klevu Option not enabled for Store Name:(%s), Website:(%s).", $value['order_item_id'], $store->getName(), $store->getWebsite()->getName()));
                            //It should not remove item from queue due to send sync is disabled. It can be enable in future.
                            //$this->removeItemFromQueue($value['order_item_id']);
                        }
                    } else {
                        $this->log(\Zend\Log\Logger::ERR, sprintf("Order Item %d does not exist: Removed from sync!", $value['order_item_id']));
                        $this->removeItemFromQueue($value['order_item_id']);
                        $errors++;
                    }
                }
                $this->log(\Zend\Log\Logger::INFO, sprintf("Order Sync finished for Store Name:(%s), Website:(%s) and %d Items synced.", $store->getName(), $store->getWebsite()->getName(), $items_synced));
            }
        } catch (\Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            $this->log(\Zend\Log\Logger::CRIT, sprintf("Order Sync:: Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            throw $e;
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

            $response = $this->_apiActionProducttracking
                ->setStore($this->_storeModelStoreManagerInterface->getStore($item->getStoreId()))
                ->execute([
                    "klevu_apiKey" => $this->getApiKey($item->getStoreId()),
                    "klevu_type" => "checkout",
                    "klevu_productId" => $klevu_productId,
                    "klevu_unit" => $item->getQtyOrdered() ? $item->getQtyOrdered() : ($parent ? $parent->getQtyOrdered() : null),
                    "klevu_salePrice" => $item->getPriceInclTax() ? $item->getPriceInclTax() : ($parent ? $parent->getPriceInclTax() : null),
                    "klevu_currency" => $this->getStoreCurrency($item->getStoreId()),
                    "klevu_shopperIP" => $this->getOrderIP($item->getOrderId()),
                    "Klevu_sessionId" => $sess_id,
                    "klevu_orderDate" => date_format(date_create($order_date), "Y-m-d"),
                    "klevu_emailId" => $order_email,
                    "klevu_storeTimezone" => $this->_searchHelperData->getStoreTimeZone($item->getStoreId()),
                    "Klevu_clientIp" => $ip_address,
                    "klevu_checkoutDate" => $checkout_date,
                    "klevu_productPosition" => "1"
                ]);
            if ($response->isSuccess()) {
                return true;
            } else {
                return $response->getMessage();
            }
        } catch (\Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            $this->log(\Zend\Log\Logger::INFO, sprintf("Order Itemid %s skipped for Klevu Order Sync ", $item->getOrderId()));
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
     * @return string
     */
    protected function getOrderIP($order_id)
    {
        $order_ips = $this->getData("order_ips");
        if (!is_array($order_ips)) {
            $order_ips = [];
        }
        if (!isset($order_ips[$order_id])) {
            $order_ips[$order_id] = $this->_frameworkModelResource->getConnection()->fetchOne(
                $this->_frameworkModelResource->getConnection()
                    ->select()
                    ->from(["order" => $this->_frameworkModelResource->getTableName("sales_order")], "remote_ip")
                    ->where("order.entity_id = ?", $order_id)
            );
            $this->setData("order_ips", $order_ips);
        }
        return $order_ips[$order_id];
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
     * @return \Zend\Db\Select
     */
    protected function getSyncQueueSelect()
    {
        return $this->_frameworkModelResource->getConnection()
            ->select()
            ->from($this->_frameworkModelResource->getTableName("klevu_order_sync"))
            ->where("send = 0");
    }

    /**
     * Add the given order item IDs to the sync queue.
     *
     * @param $order_item_ids
     *
     * @return int
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
     */
    protected function removeItemFromQueue($order_item_id)
    {
        $where = sprintf("(order_item_id = %s)", $order_item_id);
        return $this->_frameworkModelResource->getConnection()->update(
                $this->_frameworkModelResource->getTableName("klevu_order_sync"),
                ["send" => 1],
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
     * @param $level
     * @param $message
     * @return KlevuSync
     */
    public function log($level, $message)
    {
        return $this->_klevuSyncModel->log($level, $message);
    }
}
