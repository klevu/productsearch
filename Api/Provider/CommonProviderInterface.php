<?php

namespace Klevu\Search\Api\Provider;

use Magento\Catalog\Api\Data\ProductInterface;

interface CommonProviderInterface
{
    /**
     * Returns the parent ids for given product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getParentIds(ProductInterface $product);

    /**
     * Returns the child ids for given product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getChildIds(ProductInterface $product);
}

