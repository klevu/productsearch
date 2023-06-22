<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Api\Service\Catalog\Product;

interface GetStockIdForWebsiteInterface
{
    /**
     * @param int|null $websiteId
     *
     * @return int
     */
    public function execute($websiteId = null);
}
