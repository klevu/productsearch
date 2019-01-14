<?php
/**
 * Klevu product resource model
 */
namespace Klevu\Search\Model\Klevu\Resource;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Klevu extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('klevu_product_sync', 'row_id');
    }
}