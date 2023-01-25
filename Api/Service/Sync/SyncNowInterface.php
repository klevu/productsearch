<?php

namespace Klevu\Search\Api\Service\Sync;

use InvalidArgumentException;
use Klevu\Search\Exception\MissingSyncEntityIds;
use Klevu\Search\Exception\StoreNotIntegratedException;
use Klevu\Search\Exception\StoreSyncDisabledException;
use Magento\Framework\Exception\NoSuchEntityException;

interface SyncNowInterface
{
    /**
     * @param array $entityIds
     * @param int $storeId
     *
     * @return void
     * @throws MissingSyncEntityIds
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     * @throws StoreNotIntegratedException
     * @throws StoreSyncDisabledException
     */
    public function execute(array $entityIds, $storeId);
}
