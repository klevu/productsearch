<?php
/**
 * Class \Klevu\Search\Model\Observer
 *
 * @method execute($observer)
 *
 */

namespace Klevu\Search\Model\Observer;


use Magento\Framework\Event\Observer;
use Klevu\Logger\Constants as LoggerConstants;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class ScheduleOrderSync
 * @package Klevu\Search\Model\Observer
 */
class ScheduleOrderSync implements ObserverInterface
{
    /**
     * @var \Klevu\Search\Model\Product\Sync
     */
    protected $_modelProductSync;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_magentoFrameworkFilesystem;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $_modelProductAction;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    /**
     * @var \Klevu\Search\Model\Order\Sync
     */
    protected $_modelOrderSync;

    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;

    /**
     * ScheduleOrderSync constructor.
     * @param \Klevu\Search\Model\Product\Sync $modelProductSync
     * @param \Magento\Framework\Filesystem $magentoFrameworkFilesystem
     * @param \Klevu\Search\Helper\Data $searchHelperData
     * @param \Klevu\Search\Helper\Config $searchHelperConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface
     * @param \Klevu\Search\Model\Order\Sync $modelOrderSync
     */
    public function __construct(
        \Klevu\Search\Model\Product\Sync           $modelProductSync,
        \Magento\Framework\Filesystem              $magentoFrameworkFilesystem,
        \Klevu\Search\Helper\Data                  $searchHelperData,
        \Klevu\Search\Helper\Config                $searchHelperConfig,
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Klevu\Search\Model\Order\Sync             $modelOrderSync
    )
    {
        $this->_modelProductSync = $modelProductSync;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_searchHelperData = $searchHelperData;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_modelOrderSync = $modelOrderSync;
        $this->_searchHelperConfig = $searchHelperConfig;
    }

    /**
     * Schedule an Order Sync to run immediately. If the observed event
     * contains an order, add it to the sync queue before scheduling.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getData('order');
        if (!$order instanceof OrderInterface) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, sprintf("OrderQueue:: Valid order not found, Order not added in queue."));
            return;
        }
        $storeId = (int)$order->getStoreId();
        if (null === $storeId) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, sprintf("OrderQueue:: StoreID found null, Order not added in queue."));
            return;
        }

        try {
            $store = $this->_storeModelStoreManagerInterface->getStore($storeId);
            if (!$store instanceof StoreInterface) {
                $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, sprintf("OrderQueue:: Store Valid Instance not found for StoreID(%s), Order not added in queue.", $storeId));

                return;
            }
            //Does nothing if order sync disabled
            if (!$this->_searchHelperConfig->isOrderSyncEnabled($store->getId())) {
                $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, sprintf("OrderQueue:: Found Sales Order Queue option disabled for Store (%s), Order not added in queue.", $store->getName()));
                return;
            }
            $this->_modelOrderSync->addOrderToQueue($order);
            if ($this->_searchHelperConfig->isExternalCronEnabled()) {
                $this->_modelOrderSync->schedule();
            }
        } catch (\Exception $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_CRIT, sprintf("OrderQueue:: Exception thrown %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
    }
}

