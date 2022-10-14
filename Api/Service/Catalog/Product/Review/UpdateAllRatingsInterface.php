<?php

namespace Klevu\Search\Api\Service\Catalog\Product\Review;

use InvalidArgumentException;
use Klevu\Search\Exception\Catalog\Product\Review\KlevuProductAttributeMissingException;
use Magento\Store\Api\Data\StoreInterface;

interface UpdateAllRatingsInterface
{
    /**
     * @param StoreInterface|int $storeId
     *
     * @return void
     * @throws KlevuProductAttributeMissingException
     * @throws InvalidArgumentException
     */
    public function execute($storeId);
}
