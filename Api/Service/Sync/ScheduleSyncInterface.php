<?php

namespace Klevu\Search\Api\Service\Sync;

use Klevu\Search\Exception\MissingSyncEntityIds;
use Magento\Framework\Exception\NoSuchEntityException;

interface ScheduleSyncInterface
{
    /**
     * @param array $entityIds
     * @param int $storeId
     *
     * @return void
     * @throws MissingSyncEntityIds
     * @throws NoSuchEntityException
     */
    public function execute(array $entityIds, $storeId);
}
