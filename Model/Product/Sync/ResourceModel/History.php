<?php

namespace Klevu\Search\Model\Product\Sync\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class History extends AbstractDb
{
    const TABLE = 'klevu_product_sync_history';
    const ENTITY_ID = 'sync_id';

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, self::ENTITY_ID);
    }
}
