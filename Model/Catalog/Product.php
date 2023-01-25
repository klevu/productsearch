<?php

namespace Klevu\Search\Model\Catalog;

use Klevu\Search\Model\Klevu\Klevu;
use Magento\Catalog\Model\Product as MagentoProductModel;

class Product extends MagentoProductModel
{
    /**
     * @return string
     *
     * ONLY to be used in Klevu Product Sync Grid to avoid issue with multiple rows with the same entity_id
     * no need to include store id here as the gird is only loaded per store
     */
    public function getId()
    {
        $magentoParentId = (null !== $this->_getData('product_parent_id'))
            ? $this->_getData('product_parent_id')
            : '0';
        $magentoEntityId = (null !== $this->_getData('entity_id'))
            ? $this->_getData('entity_id')
            : '0';

        $returnParentId = (null !== $this->_getData(Klevu::FIELD_PARENT_ID))
            ? $this->_getData(Klevu::FIELD_PARENT_ID)
            : $magentoParentId;
        $returnEntityId = (null !== $this->_getData(Klevu::FIELD_PRODUCT_ID))
            ? $this->_getData(Klevu::FIELD_PRODUCT_ID)
            : $magentoEntityId;

        return $returnEntityId . '-' . $returnParentId;
    }
}
