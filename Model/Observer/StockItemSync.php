<?php

/**
 * Class \Klevu\Search\Model\Observer
 *
 */

namespace Klevu\Search\Model\Observer;

use Klevu\Logger\Constants as LoggerConstants;
use Magento\Framework\Event\ObserverInterface;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;

class StockItemSync implements ObserverInterface
{

    /**
     * @var \Klevu\Search\Model\Product\Sync
     */
    protected $_modelProductSync;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $_modelProductAction;

    /**
     * @var \Klevu\Search\Model\Product\MagentoProductActionsInterface
     */
    protected $_magentoProductActions;

    public function __construct(
        \Klevu\Search\Model\Product\Sync $modelProductSync,
        \Klevu\Search\Helper\Data $searchHelperData,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        MagentoProductActionsInterface $magentoProductActions
    )
    {

        $this->_modelProductSync = $modelProductSync;
        $this->_searchHelperData = $searchHelperData;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_magentoProductActions = $magentoProductActions;
    }

    /**
     * Send product to sync when stock status changes and Schedule a Product Sync to run immediately.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $item = $observer->getEvent()->getData('item');
            if($item) {
                $originalValueOfStock  = boolval($item->getOrigData('is_in_stock'));
                $newValueOfStock = boolval($item->getData('is_in_stock'));
                if($originalValueOfStock !== $newValueOfStock) {
                    $product_ids[] =  $item->getProductId();
                    $this->_magentoProductActions->markRecordIntoQueue($product_ids, 'products');
                    // schedule klevu product sync cron
                    if ($this->_searchHelperConfig->isExternalCronEnabled()) {
                        $this->_modelProductSync->schedule();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_CRIT, sprintf("Marking change stock item sync error::StockItemSync :: %s", $e->getMessage()));
        }
    }
}
