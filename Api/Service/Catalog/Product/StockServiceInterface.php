<?php

namespace Klevu\Search\Api\Service\Catalog\Product;

use Magento\Catalog\Api\Data\ProductInterface;

interface StockServiceInterface
{
    /**
     * @return void
     */
    public function clearCache();

    /**
     * @param array $productIds
     * @param string|int|null $websiteId
     *
     * @return void
     */
    public function preloadKlevuStockStatus(array $productIds, $websiteId = null);

    /**
     * Get stock data for simple and parent product
     *
     * @param ProductInterface $product
     * @param ProductInterface|null $parentProduct
     *
     * @return string
     */
    public function getKlevuStockStatus(ProductInterface $product, $parentProduct = null);

    /**
     * @param ProductInterface $product
     * @param ProductInterface|null $parentProduct
     *
     * @return bool
     */
    public function isInStock(ProductInterface $product, $parentProduct = null);
}
