<?php

namespace Klevu\Search\Api\Service\Sync\Product;

use InvalidArgumentException;

interface DeleteInNextCronInterface
{
    /**
     * @param string[] $idsToDelete
     * @param int[] $storeIds
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function execute(array $idsToDelete, array $storeIds);
}
