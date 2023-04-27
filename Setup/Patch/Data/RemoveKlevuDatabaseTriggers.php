<?php

namespace Klevu\Search\Setup\Patch\Data;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Trigger as KlevuModelTrigger;
use Klevu\Search\Model\Indexer\Sync\ProductSyncIndexer;
use Magento\Config\Model\ResourceModel\Config as ConfigResourceModel;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RemoveKlevuDatabaseTriggers implements DataPatchInterface
{
    /**
     * @var KlevuModelTrigger
     */
    private $trigger;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ConfigWriterInterface
     */
    private $configWriter;
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;
    /**
     * @var ResourceConnection
     */
    private $configResourceModel;

    /**
     * @param KlevuModelTrigger $trigger
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigWriterInterface $configWriter
     * @param IndexerRegistry $indexerRegistry
     * @param ConfigResourceModel $configResourceModel
     */
    public function __construct(
        KlevuModelTrigger $trigger,
        ScopeConfigInterface $scopeConfig,
        ConfigWriterInterface $configWriter,
        IndexerRegistry $indexerRegistry,
        ConfigResourceModel $configResourceModel
    ) {
        $this->trigger = $trigger;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
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
        $this->disableOldTrigger();
        if ($this->isKlevuErpSettingEnabled()) {
            $this->setKlevuIndexerToScheduled();
            $this->disableKlevuErpSetting();
        }
    }

    /**
     * @return void
     */
    private function disableOldTrigger()
    {
        // add commit callback to avoid exception: DDL statements are not allowed in transactions
        $this->configResourceModel->addCommitCallback(function () {
            $this->trigger->dropTriggerIfFoundExist();
        });
    }

    /**
     * @return bool
     */
    private function isKlevuErpSettingEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            ConfigHelper::XML_PATH_TRIGGER_OPTIONS,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * @return void
     */
    private function disableKlevuErpSetting()
    {
        $this->configWriter->save(
            ConfigHelper::XML_PATH_TRIGGER_OPTIONS,
            0,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * @return void
     */
    private function setKlevuIndexerToScheduled()
    {
        // add commit callback to avoid exception: DDL statements are not allowed in transactions
        $this->configResourceModel->addCommitCallback(function () {
            $indexer = $this->indexerRegistry->get(ProductSyncIndexer::INDEXER_ID);
            $indexer->setScheduled(true);
        });
    }
}
