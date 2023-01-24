<?php

namespace Klevu\Search\Api\Service\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Exception\Sync\Product\CouldNotDeleteHistoryException;

interface DeleteHistoryInterface
{
    /**
     * @param array[] $records
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws CouldNotDeleteHistoryException
     */
    public function execute(array $records);
}
