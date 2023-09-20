<?php

namespace Klevu\Search\Setup\Patch\Data;

use Klevu\Search\Model\Indexer\Sync\ProductStockSyncIndexer;
use Magento\Config\Model\ResourceModel\Config as ConfigResourceModel;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class SetStockIndexerToUpdateBySchedule implements DataPatchInterface
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;
    /**
     * @var ResourceConnection
     */
    private $configResourceModel;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param ConfigResourceModel $configResourceModel
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        ConfigResourceModel $configResourceModel
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->configResourceModel = $configResourceModel;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return void
     */
    public function apply()
    {
        // add commit callback to avoid exception: DDL statements are not allowed in transactions
        $this->configResourceModel->addCommitCallback(function () {
            $indexer = $this->indexerRegistry->get(ProductStockSyncIndexer::INDEXER_ID);
            if (!$indexer->isScheduled()) {
                $indexer->setScheduled(true);
            }
        });
    }
}
