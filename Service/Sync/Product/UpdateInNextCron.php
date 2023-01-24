<?php

namespace Klevu\Search\Service\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Sync\Product\UpdateInNextCronInterface;
use Klevu\Search\Model\Klevu\Klevu;
use Magento\Framework\App\ResourceConnection;

class UpdateInNextCron implements UpdateInNextCronInterface
{
    const LAST_SYNC_AT_TIME = 0;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string[] $idsToUpdate
     * @param int[] $storeIds
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function execute(array $idsToUpdate, array $storeIds)
    {
        if (empty($idsToUpdate)) {
            throw new InvalidArgumentException(
                __('Must provide at least one row id')
            );
        }
        $this->resetLastSyncedAtTime($idsToUpdate, $storeIds);
    }

    /**
     * @param string[] $idsToUpdate
     * @param int[] $storeIds
     *
     * @return void
     */
    private function resetLastSyncedAtTime(array $idsToUpdate, array $storeIds)
    {
        $connection = $this->resourceConnection->getConnection('core_write');
        $where = implode(
            ' AND ',
            [
                $connection->quoteInto('row_id IN (?)', $idsToUpdate)
            ]
        );
        if (!empty($storeIds)) {
            $where .= $connection->quoteInto(' AND `store_id` IN (?)', $storeIds);
        }
        $connection->update(
            $this->resourceConnection->getTableName('klevu_product_sync'),
            [Klevu::FIELD_LAST_SYNCED_AT => self::LAST_SYNC_AT_TIME],
            $where
        );
    }
}
