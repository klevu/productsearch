<?php

namespace Klevu\Search\Model\Observer;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetProductsToSync implements ObserverInterface
{
    /**
     * @var MagentoProductActionsInterface
     */
    protected $magentoProductActions;
    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;

    /**
     * SetProductsToSync constructor.
     *
     * @param MagentoProductActionsInterface $magentoProductActions
     * @param SearchHelper $searchHelperData
     */
    public function __construct(
        MagentoProductActionsInterface $magentoProductActions,
        SearchHelper $searchHelperData
    ) {
        $this->magentoProductActions = $magentoProductActions;
        $this->_searchHelperData = $searchHelperData;
    }

    /**
     * When products are updated in bulk, update products so that they will be synced.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            //returns the product_ids array
            $product_ids = $observer->getData('product_ids');
            if (empty($product_ids)) {
                return;
            }
            $this->magentoProductActions->markRecordIntoQueue($product_ids, 'products');
        } catch (\Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_DEBUG,
                sprintf("Marking products sync error:: SetProductsToSync :: %s", $e->getMessage())
            );
        }
    }
}
