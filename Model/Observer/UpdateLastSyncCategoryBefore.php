<?php

/**
 * Class \Klevu\Search\Model\Observer
 *
 * @method setIsProductSyncScheduled($flag)
 * @method bool getIsProductSyncScheduled()
 */

namespace Klevu\Search\Model\Observer;

use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class UpdateLastSyncCategory
 * @package Klevu\Search\Model\Observer
 */
class UpdateLastSyncCategoryBefore implements ObserverInterface
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
            // we are only interested in invalidating products before they are removed because of anchor change, for this we compare the anchor values
            // this is done before the save to be able to get the full list of original products before the link is removed
            $originalAnchor  = boolval($observer->getEvent()->getCategory()->getOrigData('is_anchor'));
            $newAnchor = boolval($observer->getEvent()->getCategory()->getData('is_anchor'));
            if($originalAnchor !== $newAnchor) {
                $product_ids = $observer->getEvent()->getCategory()->getProductCollection()->getAllIds();
                if (empty($product_ids)) {
                    return;
                }
                $this->_magentoProductActionsInterface->markRecordIntoQueue($product_ids,'products');
            }
            //getAllIds will return array

        } catch (\Exception $e) {
            $this->_searchHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Marking products sync error:: UpdateLastSyncCategory :: %s", $e->getMessage()));
        }
    }
}

