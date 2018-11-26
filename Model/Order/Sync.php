<?php
/**
 * Class \Klevu\Search\Model\Order\Sync
 * @method \Magento\Framework\Db\Adapter\Interface getConnection()
 */
namespace Klevu\Search\Model\Order;

use \Klevu\Search\Model\Sync as KlevuSync;
use \Magento\Framework\Model\AbstractModel;
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
    
    const NOTIFICATION_TYPE = "order_sync";

    protected $_klevuSyncModel;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $frameworkModelResource,
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        \Magento\Sales\Model\Order\Item $modelOrderItem,
        \Klevu\Search\Helper\Data $searchHelperData,
        \Klevu\Search\Model\Api\Action\Producttracking $apiActionProducttracking,
        \Magento\Framework\Stdlib\DateTime\DateTime $frameworkModelDate,
        KlevuSync $klevuSyncModel,
         // abstract parent
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_klevuSyncModel = $klevuSyncModel;
        $this->_klevuSyncModel->setJobCode($this->getJobCode());

        $this->_frameworkModelResource = $frameworkModelResource;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_modelOrderItem = $modelOrderItem;
        $this->_searchHelperData = $searchHelperData;
        $this->_apiActionProducttracking = $apiActionProducttracking;
        $this->_frameworkModelDate = $frameworkModelDate;
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
     * @param bool                   $force Skip enabled check
     *
     * @return $this
     */
    public function addOrderToQueue(\Magento\Sales\Model\Order $order)
    {
        
        $items = [];
        $order_date = date_create("now")->format("Y-m-d H:i");
        $session_id = md5(session_id());
        $ip_address = $this->_searchHelperData->getIp();
        if ($order->getCustomerId()) {
            $order_email = $order->getCustomerEmail(); //logged in customer
        } else {
            $order_email = $order->getBillingAddress()->getEmail(); //not logged in customer
        }
        foreach ($order->getAllVisibleItems() as $item) {
            // For configurable products add children items only, for all other products add parents
            if ($item->getProductType() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                foreach ($item->getChildrenItems() as $child) {
                    if ($child->getId()!=null) {
                        if ($this->checkItemId($child->getId()) !== true) {
                            $items[] = [$child->getId(),$session_id,$ip_address,$order_date,$order_email];
                        }
                    }
                }
            } else {
                if ($item->getId()!=null) {
                    if ($this->checkItemId($item->getId()) !== true) {
                        $items[] = [$item->getId(),$session_id,$ip_address,$order_date,$order_email];
                    }
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
                    $this->log(\Zend\Log\Logger::INFO, "Starting sync.");
                    $items_synced = 0;
                    $errors = 0;
                    $item = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Sales\Model\Order\Item');
                    $stmt = $this->_frameworkModelResource->getConnection()->query($this->getSyncQueueSelect());
                    $itemsToSend = $stmt->fetchAll();
                foreach ($itemsToSend as $key => $value) {
                    if ($this->rescheduleIfOutOfMemory()) {
                        return;
                    }
                    $item->setData([]);
                    $item->load($value['order_item_id']);
                    if ($item->getId()) {
                        if ($this->getApiKey($item->getStoreId())) {
                                    $result = $this->sync($item, $value['klevu_session_id'], $value['ip_address'], $value['date'], $value['idcode']);
                            if ($result === true) {
                                $this->removeItemFromQueue($value['klevu_session_id']);
                                $items_synced++;
                            } else {
                                $this->log(\Zend\Log\Logger::INFO, sprintf("Skipped order item %d: %s", $value['order_item_id'], $result));
                                $errors++;
                            }
                        } else {
                            $this->log(\Zend\Log\Logger::ERR, sprintf("Skipped item %d: Order Sync is not enabled for this store.", $value['order_item_id']));
                            $this->removeItemFromQueue($value['order_item_id']);
                        }
                    } else {
                        $this->log(\Zend\Log\Logger::ERR, sprintf("Order item %d does not exist: Removed from sync!", $item_id));
                        $this->removeItemFromQueue($value['order_item_id']);
                        $errors++;
                    }
                }
                    $this->log(\Zend\Log\Logger::INFO, sprintf("Sync finished. %d items synced.", $items_synced));
            }
        } catch (\Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            $this->_searchHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            throw $e;
        }
    }
    
    /**
     * Sync the given order item to Klevu. Returns true on successful sync and
     * the error message otherwise.
     *
     * @param \Magento\Sales\Model\Order\Item $item
     *
     * @return bool|string
     */
    protected function sync($item, $sess_id, $ip_address, $order_date, $order_email)
    {
        if (!$this->getApiKey($item->getStoreId())) {
            return "Klevu Search is not configured for this store.";
        }
        $parent = null;
        if ($item->getParentItemId()) {
            $parent =  \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Sales\Model\Order\Item')->load($item->getParentItemId());
        }
		if($item->getProductType() == "grouped")
		{
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$groupProductObject = $objectManager->create('Magento\GroupedProduct\Model\Product\Type\Grouped');
			$groupProduct = $groupProductObject->getParentIdsByChild($item->getProductId());
			$itemId = $groupProduct[0];
		}
		else
		{
			$itemId = $item->getProductId();
		}
		$order_date_miliseconds = strtotime($order_date) * 1000;	
        $response = $this->_apiActionProducttracking
            ->setStore($this->_storeModelStoreManagerInterface->getStore($item->getStoreId()))
            ->execute([
            "klevu_apiKey"    => $this->getApiKey($item->getStoreId()),
            "klevu_type"      => "checkout",
            "klevu_productId" => $this->_searchHelperData->getKlevuProductId($itemId, ($parent) ? $parent->getProductId() : 0),
            "klevu_unit"      => $item->getQtyOrdered() ? $item->getQtyOrdered() : ($parent ? $parent->getQtyOrdered() : null),
            "klevu_salePrice" => $item->getPriceInclTax() ? $item->getPriceInclTax() : ($parent ? $parent->getPriceInclTax() : null),
            "klevu_currency"  => $this->getStoreCurrency($item->getStoreId()),
            "klevu_shopperIP" => $this->getOrderIP($item->getOrderId()),
            "Klevu_sessionId" => $sess_id,
            "klevu_orderDate" => date_format(date_create($order_date),"Y-m-d"),
            "klevu_emailId" => $order_email,
            "klevu_storeTimezone" => $this->_searchHelperData->getStoreTimeZone($item->getStoreId()),
            "Klevu_clientIp" => $ip_address,
			"klevu_checkoutDate" => $order_date_miliseconds
            ]);
        if ($response->isSuccess()) {
            return true;
        } else {
            return $response->getMessage();
        }
    }
    
    /**
     * Check if Order Sync is enabled for the given store.
     *
     * @param $store_id
     *
     * @return bool
     */
    protected function isEnabled($store_id)
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
            ->from($this->_frameworkModelResource->getTableName("klevu_order_sync"));
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
            ["order_item_id","klevu_session_id","ip_address","date","idcode"],
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
        return $this->_frameworkModelResource->getConnection()->delete(
            $this->_frameworkModelResource->getTableName("klevu_order_sync"),
            ["order_item_id" => $order_item_id]
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
    public function schedule($time = "now"){
       return $this->_klevuSyncModel->schedule();
    }
    public function isRunning($copies = 1){
        return $this->_klevuSyncModel->isRunning($copies);
    }
    public function rescheduleIfOutOfMemory(){
        return $this->_klevuSyncModel->rescheduleIfOutOfMemory();
    }
    public function log($level, $message){
        return $this->_klevuSyncModel->log($level, $message);
    }
}
