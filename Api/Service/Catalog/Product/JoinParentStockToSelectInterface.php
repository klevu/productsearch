<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Api\Service\Catalog\Product;

use Magento\Framework\DB\Select;

interface JoinParentStockToSelectInterface
{
    /**
     * @param Select $select
     * @param int $storeId
     * @param bool $includeOosProducts
     * @param bool $returnStock
     * @param bool $joinParentEntity
     *
     * @return Select
     */
    public function execute(Select $select, $storeId, $includeOosProducts, $returnStock, $joinParentEntity);
}
