<?php

namespace Klevu\Search\Model\Observer;

use Klevu\Logger\Constants as LoggerConstants;

use Klevu\Search\Helper\Data as Klevu_SearchHelperData;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Klevu\Search\Model\Product\ProductCommonUpdaterInterface as ProductCommonUpdater;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Class UpdateLastSyncDate
 * @package Klevu\Search\Model\Observer
 */
class UpdateLastSyncDate implements ObserverInterface
{

    /**
     * @var ProductCommonUpdater
     */
    private $productCommonUpdater;

    /**
     * @var Klevu_SearchHelperData
     */
    protected $_searchHelperData;

    /**
     * @deprecated will be removed in future release
     * @var MagentoProductActionsInterface
     */
    protected $magentoProductActions;

    /**
     * @param MagentoProductActionsInterface $magentoProductActions
     * @param Klevu_SearchHelperData $searchHelperData
     * @param ProductCommonUpdater $productCommonUpdater
     */
    public function __construct(
        MagentoProductActionsInterface $magentoProductActions,
        Klevu_SearchHelperData         $searchHelperData,
        ProductCommonUpdater           $productCommonUpdater = null
    )
    {
        $this->magentoProductActions = $magentoProductActions;
        $this->_searchHelperData = $searchHelperData;
        $this->productCommonUpdater = $productCommonUpdater ?:
            ObjectManager::getInstance()->get(ProductCommonUpdater::class);
    }

    /**
     * When products are updated in bulk, update products so that they will be synced.
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $product = $event->getDataUsingMethod('product');
            if (!($product instanceof ProductInterface) || !$product->getId()) {
                return;
            }
            $this->productCommonUpdater->markProductToQueue($product);
        } catch (\Exception $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_CRIT, sprintf("Marking products sync error:: UpdateLastSyncDate :: %s", $e->getMessage()));
        }
    }


}


