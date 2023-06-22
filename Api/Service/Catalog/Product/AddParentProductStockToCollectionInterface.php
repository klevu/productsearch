<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Api\Service\Catalog\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Api\Data\StoreInterface;

interface AddParentProductStockToCollectionInterface
{
    /**
     * @param ProductCollection $productCollection
     * @param StoreInterface $store
     * @param bool $includeOosProducts
     *
     * @return ProductCollection
     */
    public function execute(ProductCollection $productCollection, StoreInterface $store, $includeOosProducts);
}
