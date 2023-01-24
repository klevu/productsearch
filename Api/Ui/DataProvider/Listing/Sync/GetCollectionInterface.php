<?php

namespace Klevu\Search\Api\Ui\DataProvider\Listing\Sync;

use Magento\Catalog\Model\ResourceModel\AbstractCollection;

interface GetCollectionInterface
{
    /**
     * @return AbstractCollection
     */
    public function execute();
}
