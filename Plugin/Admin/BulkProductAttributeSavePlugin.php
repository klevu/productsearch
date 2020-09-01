<?php

namespace Klevu\Search\Plugin\Admin;

use Klevu\Search\Model\Product\MagentoProductActionsInterface;

/**
 * Class BulkProductAttributeSavePlugin responsible for marking products for next sync
 * @package Klevu\Search\Plugin\Admin
 */
class BulkProductAttributeSavePlugin
{

    /**
     * BulkProductAttributeSavePlugin constructor.
     * @param \Magento\Catalog\Helper\Product\Edit\Action\Attribute $attributeHelper
     * @param MagentoProductActionsInterface $magentoProductActions
     * @param \Klevu\Search\Helper\Log $searchHelper
     */
    public function __construct(
        \Magento\Catalog\Helper\Product\Edit\Action\Attribute $attributeHelper,
        MagentoProductActionsInterface $magentoProductActions
    )
    {
        $this->attributeHelper = $attributeHelper;
        $this->magentoProductActions = $magentoProductActions;
    }

    /**
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterExecute(
        \Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save $subject,
        $result
    )
    {
        try {
            $productIds = $this->attributeHelper->getProductIds();
            $storeId = $this->attributeHelper->getSelectedStoreId();
            if (empty($productIds)) {
                return $result;
            }
            if ($storeId > 0) {
                $this->magentoProductActions->markRecordIntoQueue($productIds, 'products', (int)$storeId);
            } else {
                //For all the stores
                $this->magentoProductActions->markRecordIntoQueue($productIds, 'products');
            }
        } catch (\Exception $e) {
            return $result;
        }
        return $result;
    }
}

