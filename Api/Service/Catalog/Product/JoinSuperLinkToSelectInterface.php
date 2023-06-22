<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Api\Service\Catalog\Product;

use Magento\Framework\DB\Select;

interface JoinSuperLinkToSelectInterface
{
    /**
     * @param Select $select
     *
     * @return Select
     */
    public function execute(Select $select);
}
