<?php

namespace Klevu\Search\Api\Service\Sync\Product;

use InvalidArgumentException;

interface UpdateInNextCronInterface
{
    /**
     * @param string[] $idsToUpdate
     * @param int[] $storeIds
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function execute(array $idsToUpdate, array $storeIds);
}
