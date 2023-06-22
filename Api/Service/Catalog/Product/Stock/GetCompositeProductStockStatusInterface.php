<?php

namespace Klevu\Search\Api\Service\Catalog\Product\Stock;

use Magento\Catalog\Api\Data\ProductInterface;

interface GetCompositeProductStockStatusInterface
{
    /**
     * @param ProductInterface $product
     * @param array $bundleOptions
     * @param int|null $stockId
     *
     * @return bool
     */
    public function execute(ProductInterface $product, array $bundleOptions, $stockId);
}
