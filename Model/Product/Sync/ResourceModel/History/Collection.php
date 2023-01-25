<?php

namespace Klevu\Search\Model\Product\Sync\ResourceModel\History;

use Klevu\Search\Model\Product\Sync\History as SyncHistory;
use Klevu\Search\Model\Product\Sync\ResourceModel\History as SyncHistoryResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize collection
     */
    protected function _construct()
    {
        $this->_init(SyncHistory::class, SyncHistoryResourceModel::class);
    }
}
