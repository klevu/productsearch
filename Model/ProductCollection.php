<?php

namespace Klevu\Search\Model;

use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;
use Magento\Framework\Model\AbstractModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection as Magento_ProductCollection;

/**
 * Product collection model
 *
 * @SuppressWarnings(PHPMD)
 */
class ProductCollection extends AbstractModel
{
    /**
     * @var Klevu_HelperManager
     */
    protected $_helperManager;

    /**
     * @var Magento_ProductCollection
     */
    private $_productCollection;


    /**
     * ProductCollection constructor.
     * @param Magento_ProductCollection $productCollection
     * @param Klevu_HelperManager $helperManager
     */
    public function __construct(
        Magento_ProductCollection $productCollection,
        Klevu_HelperManager $helperManager
    )
    {
        $this->_productCollection = $productCollection;
        $this->_helperManager = $helperManager;
    }


    /**
     * Get total number of products (max to 5001).
     *
     * @return intval
     */
    public function loadProductCountByCollection()
    {
        $collection = $this->_productCollection->addAttributeToSelect('entity_id');
        //limit is added intently
        $collection->getSelect()->limit(5001);
        $collection->load();
        return intval(count($collection));
    }
}
