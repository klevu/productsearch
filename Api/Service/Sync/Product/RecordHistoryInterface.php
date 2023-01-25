<?php

namespace Klevu\Search\Api\Service\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Data\HistoryInterface;
use Klevu\Search\Exception\Sync\Product\CouldNotSaveHistoryException;

interface RecordHistoryInterface
{
    /**
     * @param array[] $records
     *
     * @return HistoryInterface
     * @throws CouldNotSaveHistoryException
     */
    public function execute(array $records);
}
