<?php

namespace Klevu\Search\Plugin\Admin;

use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save as AttributeSave;
use Magento\Catalog\Helper\Product\Edit\Action\Attribute as AttributeHelper;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Class BulkProductAttributeSavePlugin responsible for marking products for next sync
 */
class BulkProductAttributeSavePlugin
{
    /**
     * @var AttributeHelper
     */
    protected $attributeHelper;
    /**
     * @var MagentoProductActionsInterface
     */
    protected $magentoProductActions;

    /**
     * BulkProductAttributeSavePlugin constructor.
     *
     * @param AttributeHelper $attributeHelper
     * @param MagentoProductActionsInterface $magentoProductActions
     */
    public function __construct(
        AttributeHelper $attributeHelper,
        MagentoProductActionsInterface $magentoProductActions
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->magentoProductActions = $magentoProductActions;
    }

    /**
     * @param AttributeSave $subject
     * @param Redirect $result
     *
     * @return Redirect
     */
    public function afterExecute(AttributeSave $subject, $result)
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
