<?php

namespace Klevu\Search\Model\Observer;

use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class UpdateLastSyncDate
 * @package Klevu\Search\Model\Observer
 */
class UpdateLastSyncDate implements ObserverInterface
{
    /**
     * @var MagentoProductActionsInterface
     */
    protected $magentoProductActions;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * UpdateLastSyncDate constructor.
     * @param \Klevu\Search\Helper\Data $searchHelperData
     * @param MagentoProductActionsInterface $magentoProductActions
     */
    public function __construct(
        MagentoProductActionsInterface $magentoProductActions,
        \Klevu\Search\Helper\Data $searchHelperData

    )
    {
        $this->magentoProductActions = $magentoProductActions;
        $this->_searchHelperData = $searchHelperData;
    }

    /**
     * When products are updated in bulk, update products so that they will be synced.
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $product_ids[] = $observer->getEvent()->getProduct()->getId();
            if (empty($product_ids)) {
                return;
            }
            $storeIds = $observer->getEvent()->getProduct()->getStoreIds();
            if ($storeIds > 0) {
                $this->magentoProductActions->markRecordIntoQueue($product_ids, 'products', $storeIds);
            } else {
                //For all the stores
                $this->magentoProductActions->markRecordIntoQueue($product_ids, 'products');
            }
        } catch (\Exception $e) {
            $this->_searchHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Marking products sync error:: UpdateLastSyncDate :: %s", $e->getMessage()));
        }
    }
}

