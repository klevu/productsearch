<?php
/**
 * Klevu product resource model collection
 */
namespace Klevu\Search\Model\Klevu\Resource\Klevu;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Klevu\Search\Model\Klevu\Klevu',
            'Klevu\Search\Model\Klevu\Resource\Klevu'
        );
    }	
	
}