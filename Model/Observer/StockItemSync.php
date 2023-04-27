<?php

namespace Klevu\Search\Model\Observer;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Product\Sync as ProductSync;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;

class StockItemSync implements ObserverInterface
{
    /**
     * @var ProductSync
     */
    protected $_modelProductSync;
    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;
    /**
     * @var ProductAction
     */
    protected $_modelProductAction;
    /**
     * @var MagentoProductActionsInterface
     */
    protected $_magentoProductActions;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;

    /**
     * @param ProductSync $modelProductSync
     * @param SearchHelper $searchHelperData
     * @param ConfigHelper $searchHelperConfig
     * @param MagentoProductActionsInterface $magentoProductActions
     */
    public function __construct(
        ProductSync $modelProductSync,
        SearchHelper $searchHelperData,
        ConfigHelper $searchHelperConfig,
        MagentoProductActionsInterface $magentoProductActions
    ) {
        $this->_modelProductSync = $modelProductSync;
        $this->_searchHelperData = $searchHelperData;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_magentoProductActions = $magentoProductActions;
    }

    /**
     * Send product to sync when stock status changes and Schedule a Product Sync to run immediately.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $item = $observer->getEvent()->getData('item');
            if ($item) {
                $originalValueOfStock = (bool)$item->getOrigData('is_in_stock');
                $newValueOfStock = (bool)$item->getData('is_in_stock');
                if ($originalValueOfStock !== $newValueOfStock) {
                    $product_ids[] = $item->getProductId();
                    $this->_magentoProductActions->markRecordIntoQueue($product_ids, 'products');
                    // schedule klevu product sync cron
                    if ($this->_searchHelperConfig->isExternalCronEnabled()) {
                        $this->_modelProductSync->schedule();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf("Marking change stock item sync error::StockItemSync :: %s", $e->getMessage())
            );
        }
    }
}
