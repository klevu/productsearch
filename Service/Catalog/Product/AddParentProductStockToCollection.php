<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\AddParentProductStockToCollectionInterface;
use Klevu\Search\Api\Service\Catalog\Product\JoinParentStockToSelectInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Api\Data\StoreInterface;

class AddParentProductStockToCollection implements AddParentProductStockToCollectionInterface
{
    /**
     * @var JoinParentStockToSelectInterface
     */
    private $joinParentStockToSelect;

    /**
     * @param JoinParentStockToSelectInterface $joinParentStockToSelect
     */
    public function __construct(
        JoinParentStockToSelectInterface $joinParentStockToSelect
    ) {
        $this->joinParentStockToSelect = $joinParentStockToSelect;
    }

    /**
     * @param ProductCollection $productCollection
     * @param StoreInterface $store
     * @param bool $includeOosProducts
     *
     * @return ProductCollection
     */
    public function execute(ProductCollection $productCollection, StoreInterface $store, $includeOosProducts)
    {
        if ($includeOosProducts) {
            return $productCollection;
        }
        $select = $productCollection->getSelect();
        $this->joinParentStockToSelect->execute($select, $store->getId(), $includeOosProducts);

        return $productCollection;
    }
}
