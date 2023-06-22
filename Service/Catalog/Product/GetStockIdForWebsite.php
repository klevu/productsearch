<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\GetStockIdForWebsiteInterface;
use Magento\CatalogInventory\Model\Stock as MagentoStock; // required for M2.1 & PHP 5.6 to avoid name clash with Stock

class GetStockIdForWebsite implements GetStockIdForWebsiteInterface
{
    /**
     * @param int|null $websiteId // is only used in MSI implementation
     *
     * @return int
     */
    public function execute($websiteId = null)
    {
        return MagentoStock::DEFAULT_STOCK_ID;
    }
}
