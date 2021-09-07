<?php

/**
 * Class \Klevu\Search\Model\Observer
 *
 * @method setIsProductSyncScheduled($flag)
 * @method bool getIsProductSyncScheduled()
 */

namespace Klevu\Search\Model\Observer;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class UpdateLastSyncCategory
 * @package Klevu\Search\Model\Observer
 */
class UpdateLastSyncCategory implements ObserverInterface
{
    /**
     * @var \Klevu\Search\Model\Product\MagentoProductActionsInterface
     */
    protected $_magentoProductActionsInterface;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * UpdateLastSyncCategory constructor.
     * @param \Klevu\Search\Model\Product\MagentoProductActionsInterface $magentoProductActionsInterface
     * @param \Klevu\Search\Helper\Data $searchHelperData
     */
    public function __construct(
        MagentoProductActionsInterface $magentoProductActionsInterface,
        \Klevu\Search\Helper\Data $searchHelperData
    )
    {
        $this->_magentoProductActionsInterface = $magentoProductActionsInterface;
        $this->_searchHelperData = $searchHelperData;
    }

    /**
     * When products are updated in bulk, update products so that they will be synced.
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            //getId adding as array
            $category_ids[] = $observer->getEvent()->getCategory()->getId();
            if (empty($category_ids)) {
                return;
            }
            $this->_magentoProductActionsInterface->markRecordIntoQueue($category_ids, 'categories');

            //getAllIds will return array
            $product_ids = $observer->getEvent()->getCategory()->getProductCollection()->getAllIds();
            if (empty($product_ids)) {
                return;
            }
            $this->_magentoProductActionsInterface->markRecordIntoQueue($product_ids,'products');
        } catch (\Exception $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_CRIT, sprintf("Marking products sync error:: UpdateLastSyncCategory :: %s", $e->getMessage()));
        }
    }
}

