<?php

namespace Klevu\Search\Model\Observer;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class SetCategoryProductsToSync
 * @package Klevu\Search\Model\Observer
 */
class SetCategoryProductsToSync implements ObserverInterface
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
     * SetCategoryProductsToSync constructor.
     * @param MagentoProductActionsInterface $magentoProductActions
     * @param \Klevu\Search\Helper\Data $searchHelperData
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
            //returns the product_ids array
            $product_ids = $observer->getData('product_ids');
            if (empty($product_ids)) {
                return;
            }
            $this->magentoProductActions->markRecordIntoQueue($product_ids, 'products');
        } catch (\Exception $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_DEBUG, sprintf("Marking products sync error:: SetCategoryProductsToSync :: %s", $e->getMessage()));
        }

    }
}

