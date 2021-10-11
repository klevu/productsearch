<?php
namespace Klevu\Search\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface as MagentoProductInterface;

interface ProductCommonUpdaterInterface
{
    /**
     * @param MagentoProductInterface $product
     * @return void
     */
    public function markProductToQueue(MagentoProductInterface $product);
}
