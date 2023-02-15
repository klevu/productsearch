<?php
/**
 * Class \Klevu\Search\Model\Order\Sync
 * @method \Magento\Framework\Db\Adapter\Interface getConnection()
 */

namespace Klevu\Search\Model\Order;

use InvalidArgumentException;
use Klevu\Logger\Api\StoreScopeResolverInterface;
use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Api\Provider\Sync\Order\Item\DataProviderInterface as OrderItemDataProviderInterface;
use Klevu\Search\Api\Provider\Sync\Order\ItemsToSyncProviderInterface;
use Klevu\Search\Api\Service\Convertor\Sync\Order\ItemDataConvertorInterface;
use Klevu\Search\Api\Service\Sync\GetOrderSelectMaxLimitInterface;
use Klevu\Search\Api\Provider\Customer\CustomerIdProviderInterface;
use Klevu\Search\Api\Provider\Customer\SessionIdProviderInterface;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Api\Action\Producttracking;
use Klevu\Search\Model\Sync as KlevuSync;
use Klevu\Search\Model\System\Config\Source\Order\Ip as OrderIP;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProduct;
use Klevu\Search\Helper\Price as Klevu_Helper_Price;
use Magento\Sales\Api\Data\OrderItemExtension;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\ItemFactory as OrderItemFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class Sync extends AbstractModel
{
    const LOCK_FILE = 'klevu_running_order_sync.lock';
    const LOCK_FILE_TTL = 3600;
    const SYNC_QUEUE_ITEM_WAITING = 0;
    const SYNC_QUEUE_ITEM_SUCCESS = 1;
    const SYNC_QUEUE_ITEM_FAILED = 2;
    const NOTIFICATION_TYPE = "order_sync";

    /**
     * @var ResourceConnection
     */
    protected $_frameworkModelResource;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;
    /**
     * @var OrderItem
     */
    protected $_modelOrderItem;
    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;
    /**
     * @var Producttracking
     */
    protected $_apiActionProducttracking;
    /**
     * @var DateTime
     */
    protected $_frameworkModelDate;
    /**
     * @var GroupedProduct
     */
    protected $_groupedProduct;
    /**
     * @var \Klevu\Search\Model\Sync
     */
    protected $_klevuSyncModel;
    /**
     * @var Klevu_Helper_Price
     */
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
    /**
     * @var ItemsToSyncProviderInterface
     */
    private $itemsToSyncProvider;
    /**
     * @var OrderItemDataProviderInterface
     */
    private $orderItemDataProvider;
    /**
     * @var ItemDataConvertorInterface
     */
    private $orderSyncItemDataConvertor;
    /**
     * @var OrderItemExtensionFactory
     */
    private $orderItemExtensionFactory;
    /**
     * @var DriverInterface
     */
    private $fileDriver;

    /**
     * @param ResourceConnection $frameworkModelResource
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param ConfigHelper $searchHelperConfig
     * @param OrderItem $modelOrderItem
     * @param OrderItemFactory $modelOrderItemFactory
     * @param SearchHelper $searchHelperData
     * @param Producttracking $apiActionProducttracking
     * @param DateTime $frameworkModelDate
     * @param GroupedProduct $groupedProduct
     * @param KlevuSync $klevuSyncModel
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param Klevu_Helper_Price|null $klevuPriceHelper
     * @param array $data
     * @param StoreScopeResolverInterface|null $storeScopeResolver
     * @param DirectoryList|null $directoryList
     * @param SessionIdProviderInterface|null $sessionIdProvider
     * @param CustomerIdProviderInterface|null $customerIdProvider
     * @param GetOrderSelectMaxLimitInterface|null $getOrderSelectMaxLimit
     * @param ItemsToSyncProviderInterface|null $itemsToSyncProvider
     * @param OrderItemDataProviderInterface|null $orderItemDataProvider
     * @param ItemDataConvertorInterface|null $orderSyncItemDataConvertor
     * @param OrderItemExtensionFactory|null $orderItemExtensionFactory
     * @param DriverInterface|null $fileDriver
     */
    public function __construct(
        ResourceConnection $frameworkModelResource,
        StoreManagerInterface $storeModelStoreManagerInterface,
        ConfigHelper $searchHelperConfig,
        OrderItem $modelOrderItem,
        OrderItemFactory $modelOrderItemFactory,
        SearchHelper $searchHelperData,
        Producttracking $apiActionProducttracking,
        DateTime $frameworkModelDate,
        GroupedProduct $groupedProduct,
        KlevuSync $klevuSyncModel,
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        Klevu_Helper_Price $klevuPriceHelper = null,
        array $data = [],
        StoreScopeResolverInterface $storeScopeResolver = null,
        DirectoryList $directoryList = null,
        SessionIdProviderInterface $sessionIdProvider = null,
        CustomerIdProviderInterface $customerIdProvider = null,
        GetOrderSelectMaxLimitInterface $getOrderSelectMaxLimit = null,
        ItemsToSyncProviderInterface $itemsToSyncProvider = null,
        OrderItemDataProviderInterface $orderItemDataProvider = null,
        ItemDataConvertorInterface $orderSyncItemDataConvertor = null,
        OrderItemExtensionFactory $orderItemExtensionFactory = null,
        DriverInterface $fileDriver = null
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
        $objectManager = ObjectManager::getInstance();
        $this->_priceHelper = $klevuPriceHelper
            ?: $objectManager->get(Klevu_Helper_Price::class);
        $this->storeScopeResolver = $storeScopeResolver
            ?: $objectManager->get(StoreScopeResolverInterface::class);
        $this->directoryList = $directoryList
            ?: $objectManager->get(DirectoryList::class);
        $this->sessionIdProvider = $sessionIdProvider
            ?: $objectManager->get(SessionIdProviderInterface::class);
        $this->customerIdProvider = $customerIdProvider
            ?: $objectManager->get(CustomerIdProviderInterface::class);
        $this->getOrderSelectMaxLimit = $getOrderSelectMaxLimit
            ?: $objectManager->get(GetOrderSelectMaxLimitInterface::class);
        $this->itemsToSyncProvider = $itemsToSyncProvider
            ?: $objectManager->get(ItemsToSyncProviderInterface::class);
        $this->orderItemDataProvider = $orderItemDataProvider
            ?: $objectManager->get(OrderItemDataProviderInterface::class);
        $this->orderSyncItemDataConvertor = $orderSyncItemDataConvertor
            ?: $objectManager->get(ItemDataConvertorInterface::class);
        $this->orderItemExtensionFactory = $orderItemExtensionFactory
            ?: $objectManager->get(OrderItemExtensionFactory::class);
        $this->fileDriver = $fileDriver
            ?: $objectManager->get(FileDriver::class);
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
     *
     * @return $this
     */
    public function setStoreCodesToRun($storeCodesToRun)
    {
        if (null === $storeCodesToRun) {
            $storeCodesToRun = [];
        }
        if (!is_array($storeCodesToRun)) {
            throw new InvalidArgumentException(sprintf(
                'Store Codes argument must be an array or null; "%s" encountered',
                // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
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
     * @param Order $order
     *
     * @return $this
     */
    public function addOrderToQueue(Order $order)
    {
        $uniqueGroupedProductIds = [];
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
            /** @var OrderItemInterface $item */
            if ($item->getProductType() === GroupedProduct::TYPE_CODE) {
                if ($item->getId() === null || $this->checkItemId($item->getId())) {
                    continue;
                }
                $groupProductData = $item->getProductOptions();
                if (empty($groupProductData["super_product_config"]["product_id"])) {
                    continue;
                }
                $groupedProductId = $groupProductData["super_product_config"]["product_id"];
                if (!in_array((int)$groupedProductId, $uniqueGroupedProductIds, true)) {
                    $uniqueGroupedProductIds[$item->getId()] = (int)$groupedProductId;
                }
            } elseif (($item->getId() !== null) && !$this->checkItemId($item->getId())) {
                $items[] = [$item->getId(), $session_id, $ip_address, $order_date, $order_email, $checkout_date, 0];
            }
        }
        foreach ($uniqueGroupedProductIds as $orderItemFirstId => $groupProductUniqueId) {
            if (!$groupProductUniqueId || !$orderItemFirstId) {
                continue;
            }
            $items[] = [
                $orderItemFirstId,
                $session_id,
                $ip_address,
                $order_date,
                $order_email,
                $checkout_date,
                0
            ];
        }

        // in case of multiple addresses used for shipping
        // its possible that items object here is empty
        // if so, we do not add to the item.
        if ($items) {
            $this->addItemsToQueue($items);
        }

        return $this;
    }

    /**
     * Clear the Order Sync queue for the given store. If no store is given, clears
     * the queue for all stores.
     *
     * @param StoreInterface|int|null $store
     *
     * @return int
     * @throws NoSuchEntityException
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
     * @throws FileSystemException
     * @throws \Exception
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
                $website = $store->getWebsite();
                //Skip it if no API keys found
                if (!$this->getApiKey($store->getId())) {
                    $this->log(
                        LoggerConstants::ZEND_LOG_INFO,
                        sprintf(
                            "Order Sync :: No API Key found for Store Name:(%s), Website:(%s).",
                            $store->getName(),
                            $website->getName()
                        )
                    );
                    continue;
                }

                $this->log(
                    LoggerConstants::ZEND_LOG_INFO,
                    sprintf(
                        "Starting Order Sync for Store Name:(%s), Website:(%s).",
                        $store->getName(),
                        $website->getName()
                    )
                );

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
                        $website->getName()
                    ));

                    $this->processItemsToSync($itemsToSend, $store, $items_synced, $errors);
                    $items_processed += count($itemsToSend);
                } while (0 === $maxBatchSize || $items_processed < $maxBatchSize);

                $this->log(
                    LoggerConstants::ZEND_LOG_INFO,
                    sprintf(
                        "Order Sync finished for Store Name:(%s), Website:(%s) and %d Items synced.",
                        $store->getName(),
                        $website->getName(),
                        $items_synced
                    )
                );
            }
            $this->storeScopeResolver->setCurrentStoreById($currentStoreScopeStoreId);
        } catch (\Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            $this->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf(
                    "Order Sync:: Exception thrown in %s::%s - %s",
                    __CLASS__,
                    __METHOD__,
                    $e->getMessage()
                )
            );
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
        $orderItemsToSend = $this->itemsToSyncProvider->convertOrderItemDataToObjects($items);

        foreach ($items as $value) {
            if ($this->rescheduleIfOutOfMemory()) {
                return;
            }
            $item = null;
            if (isset($value['order_item_id'], $orderItemsToSend[$value['order_item_id']])) {
                /** @var OrderItemInterface $item */
                $item = $orderItemsToSend[$value['order_item_id']];
            }
            if (!$item || !$item->getId()) {
                $this->log(
                    LoggerConstants::ZEND_LOG_ERR,
                    sprintf(
                        "Order Item %d does not exist: Removed from sync!",
                        $value['order_item_id']
                    )
                );
                $this->setItemFailedInQueue($value['order_item_id']);
                $errors++;
                continue;
            }

            //Instead of checking API keys, comparing the Store IDs
            //if ($this->getApiKey($item->getStoreId())) {
            if ((int)$item->getStoreId() !== (int)$store->getStoreId()) {
                $this->log(LoggerConstants::ZEND_LOG_INFO, sprintf(
                    "Skipped Order Item %d: Item Store Id (%s) is different to current store id (%s)",
                    $value['order_item_id'],
                    $item->getStoreId(),
                    $store->getStoreId()
                ));
                $errors++;
                continue;
            }

            $result = $this->sync(
                $item,
                $value['klevu_session_id'],
                $value['ip_address'],
                $value['date'],
                $value['idcode'],
                $value['checkoutdate']
            );
            if ($result === true) {
                $this->removeItemFromQueue($value['order_item_id']);
                $items_synced++;
            } else {
                $this->log(
                    LoggerConstants::ZEND_LOG_WARN,
                    sprintf('Skipped Order Item %d: %s', $value['order_item_id'], $result)
                );
                $this->setItemFailedInQueue($value['order_item_id']);
            }
        }
    }

    /**
     * Sync the given order item to Klevu. Returns true on successful sync and
     * the error message otherwise.
     *
     * @param OrderItemInterface $item
     * @param string $sess_id
     * @param string $ip_address
     * @param string $order_date date
     * @param string $order_email
     * @param string $checkout_date timestamp
     *
     * @return bool|string
     */
    protected function sync($item, $sess_id, $ip_address, $order_date, $order_email, $checkout_date)
    {
        try {
            if (!$this->getApiKey($item->getStoreId())) {
                return "Klevu Search is not configured for this store.";
            }
            $klevuData = [
                'klevu_session_id' => $sess_id,
                'ip_address' => $ip_address,
                'date' => $order_date,
                'idcode' => $order_email,
                'checkoutdate' => $checkout_date
            ];
            // to maintain backward compatibility we set $klevuData here in case this method has been extended
            // klevu order sync data is already present as an extension attribute on $item
            $this->setKlevuExtensionAttributes($item, $klevuData);
            $orderItemData = $this->orderItemDataProvider->getData($item);
            try {
                $parameters = $this->orderSyncItemDataConvertor->convert($orderItemData);
            } catch (InvalidArgumentException $exception) {
                return $exception->getMessage();
            }

            // we can not replace the private method processOrderIp as it calls a protected method.
            // klevu_shopperIP and apiKey are not used in metadata
            // so we can leave this here and not worry about the protected methods
            $parameters['klevu_shopperIP'] = $this->processOrderIp($item->getOrderId(), $item->getStoreId());
            $parameters['klevu_apiKey'] = $this->getApiKey($item->getStoreId());

            $eventData = new DataObject([
                'order_item' => $item,
                'parameters' => $parameters,
            ]);
            $this->_eventManager->dispatch(
                'klevu_search_order_sync_send_before',
                ['event_data' => $eventData]
            );

            $store = $this->_storeModelStoreManagerInterface->getStore($item->getStoreId());
            $this->_apiActionProducttracking->setStore($store);
            $response = $this->_apiActionProducttracking->execute($eventData->getData('parameters'));
            if ($response->isSuccess()) {
                return true;
            }

            return $response->getMessage();
        } catch (\Exception $e) {
            $this->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf(
                    "Order Sync:: Exception thrown in %s - %s",
                    __METHOD__,
                    $e->getMessage()
                )
            );
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            $this->log(
                LoggerConstants::ZEND_LOG_INFO,
                sprintf("Order Item Id %s skipped for Klevu Order Sync ", $item->getOrderId())
            );
        }
    }

    /**
     * Check if Order Sync Send is enabled for the given store to process Order Items.
     *
     * @param int $store_id
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
     * @param int $store_id
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
     * @param int $store_id
     *
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getStoreCurrency($store_id)
    {
        $currencies = $this->getData("currencies");
        if (!is_array($currencies)) {
            $currencies = [];
        }
        if (!isset($currencies[$store_id])) {
            $store = $this->_storeModelStoreManagerInterface->getStore($store_id);
            $currencies[$store_id] = $store->getDefaultCurrencyCode();
            $this->setData("currencies", $currencies);
        }

        return $currencies[$store_id];
    }

    /**
     * Return the customer IP for the given order.
     *
     * @param int $order_id
     * @param int $store_id
     *
     * @return string
     */
    protected function getOrderIP($order_id, $store_id)
    {
        $configuredOrderIP = $this->_searchHelperConfig->getConfiguredOrderIP($store_id);
        if ($configuredOrderIP === OrderIP::ORDER_X_FORWARDED_FOR) {
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
     * @param int $order_id
     * @param int $store_id
     *
     * @return string
     */
    private function processOrderIp($order_id, $store_id)
    {
        $ips = $this->getOrderIP($order_id, $store_id);
        $configuredOrderIP = $this->_searchHelperConfig->getConfiguredOrderIP($store_id);
        if (empty($ips)) {
            $this->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf(
                    "Shopper IP address [%s] value found NULL for Order ItemId [%s] , Store ID [%s]",
                    $configuredOrderIP,
                    $order_id,
                    $store_id
                )
            );

            return null;
        }

        if (strpos($ips, ",") !== false) {
            $ips_array = explode(",", $ips);
            $trim_ip_value = array_map('trim', $ips_array);

            return array_shift($trim_ip_value);
        }

        return $ips;
    }

    /**
     * Return Order ItemId Already exits or not.
     *
     * @param int $order_item_id
     *
     * @return bool
     */
    protected function checkItemId($order_item_id)
    {
        if (!$order_item_id) {
            return false;
        }
        $connection = $this->_frameworkModelResource->getConnection();
        $query = $connection->select();
        $query->from([
            'order' => $this->_frameworkModelResource->getTableName("klevu_order_sync")
        ]);
        $query->where("order.order_item_id = ?", $order_item_id);
        $orderId = $connection->fetchAll($query);

        return count($orderId) === 1;
    }

    /**
     * Return a select statement for getting all items in the sync queue.
     *
     * @param int $storeId
     *
     * @return Select
     */
    protected function getSyncQueueSelect($storeId = null)
    {
        return $this->itemsToSyncProvider->getItemSelect((int)$storeId);
    }

    /**
     * Add the given order item IDs to the sync queue.
     *
     * @param array|int $order_item_ids
     *
     * @return int
     * @todo Move addItemsToQueue to ResourceModel
     */
    protected function addItemsToQueue($order_item_ids)
    {
        if (!is_array($order_item_ids)) {
            $order_item_ids = [$order_item_ids];
        }
        $connection = $this->_frameworkModelResource->getConnection();

        return $connection->insertArray(
            $this->_frameworkModelResource->getTableName("klevu_order_sync"),
            ["order_item_id", "klevu_session_id", "ip_address", "date", "idcode", "checkoutdate", "send"],
            $order_item_ids
        );
    }

    /**
     * Remove the given item from the sync queue.
     *
     * @param int $order_item_id
     *
     * @return bool
     * @todo Move removeItemFromQueue to ResourceModel
     */
    protected function removeItemFromQueue($order_item_id)
    {
        $where = sprintf("(order_item_id = %s)", $order_item_id);
        $connection = $this->_frameworkModelResource->getConnection();

        return $connection->update(
            $this->_frameworkModelResource->getTableName("klevu_order_sync"),
            ["send" => static::SYNC_QUEUE_ITEM_SUCCESS],
            $where
        ) === 1;
    }

    /**
     * @param int $order_item_id
     *
     * @return bool
     * @todo Move setItemFailedInQueue to ResourceModel
     */
    private function setItemFailedInQueue($order_item_id)
    {
        $where = sprintf("(order_item_id = %s)", $order_item_id);
        $connection = $this->_frameworkModelResource->getConnection();

        return $connection->update(
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
        $connection = $this->_frameworkModelResource->getConnection();

        $connection->delete(
            $this->_frameworkModelResource->getTableName('klevu_search_notification'),
            ["type" => static::NOTIFICATION_TYPE]
        );

        return $this;
    }

    /**
     * @param string $time
     *
     * @return KlevuSync
     * compatibility
     */
    public function schedule($time = "now")
    {
        return $this->_klevuSyncModel->schedule();
    }

    /**
     * @param int $copies
     *
     * @return bool
     * @throws FileSystemException
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
        return $this->fileDriver->isExists($this->getLockFilePath());
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
        $fileData = $this->fileDriver->stat($this->getLockFilePath());

        return (time() - static::LOCK_FILE_TTL) > $fileData['mtime'];
    }

    /**
     * Creates or updates filemtime of lock file for this process
     *
     * @throws FileSystemException
     */
    public function createLockFile()
    {
        $lockFilePath = $this->getLockFilePath();
        if (!$this->fileDriver->touch($lockFilePath)) {
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
        if (!$this->fileDriver->deleteFile($lockFilePath)) {
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
     * @param OrderItemInterface $orderItem
     * @param string[] $klevuData
     *
     * @return void
     *
     * Method exists for backward compatibility only
     * Extension attributes are already added at this point.
     * Though if sync method has been extended they may have been changed so set them again.
     */
    private function setKlevuExtensionAttributes(OrderItemInterface $orderItem, array $klevuData)
    {
        if (!$klevuData) {
            return;
        }
        $extensionAttributes = $orderItem->getExtensionAttributes();
        if (!$extensionAttributes) {
            /** @var OrderItemExtension $extensionAttribute */
            $extensionAttributes = $this->orderItemExtensionFactory->create();
        }
        $extensionAttributes->setKlevuOrderSync($klevuData);
        $orderItem->setExtensionAttributes($extensionAttributes);
    }
}
