<?php

namespace Klevu\Search\Service\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Sync\Product\DeleteInNextCronInterface;
use Klevu\Search\Model\Klevu\Klevu;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\DuplicateException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class DeleteInNextCron implements DeleteInNextCronInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string[] $idsToDelete
     * @param int[] $storeIds
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function execute(array $idsToDelete, array $storeIds)
    {
        if (empty($idsToDelete)) {
            throw new InvalidArgumentException(
                __('Must provide at least one row id')
            );
        }
        if (empty($storeIds)) {
            $storeIds = $this->getAllStoreIds();
        }
        $this->addEntriesToSyncTable($idsToDelete, $storeIds);
    }

    /**
     * @param string[] $idsToDelete
     * @param int[] $storeIds
     *
     * @return void
     */
    private function addEntriesToSyncTable(array $idsToDelete, array $storeIds)
    {
        $connection = $this->resourceConnection->getConnection('core_write');
        $tableName = $this->resourceConnection->getTableName('klevu_product_sync');

        foreach ($storeIds as $storeId) {
            $data = $this->addDataToIds($idsToDelete, (int)$storeId);

            try {
                $connection->insertMultiple(
                    $tableName,
                    $data
                );
            } catch (DuplicateException $e) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // Ignore duplicates throwing integrity constraint, for example if hitting Schedule Sync multiple times
                //  before page load (ref: KS-14956)
            }
        }
    }

    /**
     * @return array
     */
    private function getAllStoreIds()
    {
        $stores = $this->storeManager->getStores();

        return array_map(static function (StoreInterface $store) {
            return (int)$store->getId();
        }, $stores);
    }

    /**
     * @param array $idsToDelete
     * @param int $storeId
     *
     * @return array
     */
    private function addDataToIds(array $idsToDelete, $storeId)
    {
        $data = [];
        foreach ($idsToDelete as $idToDelete) {
            $idToDelete[Klevu::FIELD_STORE_ID] = (int)$storeId;
            $idToDelete[Klevu::FIELD_LAST_SYNCED_AT] = UpdateInNextCron::LAST_SYNC_AT_TIME;
            $idToDelete[Klevu::FIELD_TYPE] = Klevu::OBJECT_TYPE_PRODUCT;
            $data[] = $idToDelete;
        }

        return $data;
    }
}
