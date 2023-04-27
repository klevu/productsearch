<?php // phpcs:disable Magento2.Legacy.InstallUpgrade.ObsoleteUpgradeDataScript

namespace Klevu\Search\Setup;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Indexer\Sync\ProductSyncIndexer;
use Klevu\Search\Model\Product\Sync\History as SyncHistory;
use Klevu\Search\Model\Product\Sync\ResourceModel\History as SyncHistoryResourceModel;
use Klevu\Search\Model\Trigger as KlevuDbTrigger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriterInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var ConfigWriterInterface
     */
    private $configWriter;
    /**
     * @var KlevuDbTrigger
     */
    private $trigger;
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @param ConfigWriterInterface|null $configWriter
     * @param KlevuDbTrigger|null $trigger
     * @param IndexerRegistry|null $indexerRegistry
     */
    public function __construct(
        ConfigWriterInterface $configWriter = null,
        KlevuDbTrigger $trigger = null,
        IndexerRegistry $indexerRegistry = null
    ) {
        $objectManager = ObjectManager::getInstance();
        $this->configWriter = $configWriter
            ?: $objectManager->get(ConfigWriterInterface::class);
        $this->trigger = $trigger
            ?: $objectManager->get(KlevuDbTrigger::class);
        $this->indexerRegistry = $indexerRegistry
            ?: $objectManager->get(IndexerRegistry::class);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @return void
     * @throws \Zend_Db_Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '2.0.2') < 0) {
            //code to upgrade to 2.0.2
            $order_sync_table = $installer->getTable('klevu_order_sync');
            $installer->run(
                "ALTER TABLE `{$order_sync_table}` " .
                "ADD `klevu_session_id` VARCHAR(255) NOT NULL , " .
                "ADD `ip_address` VARCHAR(255) NOT NULL , " .
                "ADD `date` DATETIME NOT NULL"
            );
        }

        if (version_compare($context->getVersion(), '2.0.10') < 0) {
            //code to upgrade to 2.0.2
            $klevu_sync_table = $installer->getTable('klevu_product_sync');
            $installer->run(
                "ALTER TABLE `{$klevu_sync_table}` ADD `error_flag` INT(11) NOT NULL DEFAULT '0' AFTER `type`"
            );
        }

        if (version_compare($context->getVersion(), '2.1.10') < 0) {
            $sql = "SHOW COLUMNS FROM `{$installer->getTable('klevu_product_sync')}` LIKE 'test_mode'";
            if (!empty($setup->getConnection()->fetchAll($sql))) {
                //remove test mode
                $setup->run(
                    "ALTER TABLE `{$installer->getTable('klevu_product_sync')}` DROP `test_mode`"
                );
            }

            $setup->run(
                "ALTER TABLE `{$installer->getTable('klevu_product_sync')}` " .
                "ADD KEY `KLEVU_PRODUCT_SYNC_PARENT_PRODUCT_ID` (`parent_id`,`product_id`), " .
                "ADD KEY `KLEVU_PRODUCT_SYNC_STORE_ID` (`store_id`)"
            );

            $order_sync_table = $installer->getTable('klevu_order_sync');
            $installer->run(
                "ALTER TABLE `{$order_sync_table}` ADD `idcode` VARCHAR(255) NOT NULL AFTER `date`"
            );
        }

        if (version_compare($context->getVersion(), '2.2.5') < 0) {
            $setup->run(
                "ALTER TABLE `{$installer->getTable('klevu_product_sync')}` DROP PRIMARY KEY"
            );
            $setup->run(
                "ALTER TABLE `{$installer->getTable('klevu_product_sync')}` " .
                "ADD `row_id` INT NOT NULL AUTO_INCREMENT FIRST, " .
                "ADD PRIMARY KEY (`row_id`)"
            );
            $setup->run(
                "ALTER TABLE `{$installer->getTable('klevu_product_sync')}` " .
                "ADD UNIQUE KEY `KLEVU_GROUP_ID` (`product_id`,`parent_id`,`store_id`,`type`)"
            );
            $order_sync_table = $installer->getTable('klevu_order_sync');
            $installer->run(
                "ALTER TABLE `{$order_sync_table}` ADD `checkoutdate` VARCHAR(255) NOT NULL AFTER `idcode`"
            );
        }

        if (version_compare($context->getVersion(), '2.2.12') < 0) {
            $order_sync_table = $installer->getTable('klevu_order_sync');
            $installer->run(
                "ALTER TABLE `{$order_sync_table}` ADD  `send` BOOLEAN NOT NULL DEFAULT FALSE AFTER `checkoutdate`"
            );
        }

        if (version_compare($context->getVersion(), '2.10.0') < 0) {
            $connection = $setup->getConnection();
            if (!$connection->isTableExists($setup->getTable(SyncHistoryResourceModel::TABLE))) {
                $table = $connection->newTable(
                    $setup->getTable(SyncHistoryResourceModel::TABLE)
                );
                $table->addColumn(
                    SyncHistoryResourceModel::ENTITY_ID,
                    Table::TYPE_INTEGER,
                    10,
                    ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                    'Sync History Entity Id'
                );
                $table->addColumn(
                    SyncHistory::FIELD_PRODUCT_ID,
                    Table::TYPE_INTEGER,
                    10,
                    ['nullable' => false, 'unsigned' => true],
                    'Magento Product ID'
                );
                $table->addColumn(
                    SyncHistory::FIELD_PARENT_ID,
                    Table::TYPE_INTEGER,
                    10,
                    ['nullable' => false, 'unsigned' => true, 'default' => 0],
                    'Magento Parent Product ID'
                );
                $table->addColumn(
                    SyncHistory::FIELD_STORE_ID,
                    Table::TYPE_SMALLINT,
                    null,
                    ['nullable' => false, 'unsigned' => true],
                    'Magento Store ID'
                );
                $table->addColumn(
                    SyncHistory::FIELD_ACTION,
                    Table::TYPE_BOOLEAN,
                    1,
                    ['nullable' => false],
                    'Action Taken By API Call'
                );
                $table->addColumn(
                    SyncHistory::FIELD_SUCCESS,
                    Table::TYPE_BOOLEAN,
                    null,
                    ['nullable' => false, 'default' => 1],
                    'Was API Call Successful'
                );
                $table->addColumn(
                    SyncHistory::FIELD_MESSAGE,
                    Table::TYPE_TEXT,
                    null,
                    ['nullable' => true],
                    'API Response Message'
                );
                $table->addColumn(
                    SyncHistory::FIELD_SYNCED_AT,
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                    'Timestamp of Api Call'
                );
                $table->addIndex(
                    $setup->getIdxName(
                        SyncHistoryResourceModel::TABLE,
                        [
                            SyncHistory::FIELD_PRODUCT_ID,
                            SyncHistory::FIELD_PARENT_ID,
                            SyncHistory::FIELD_STORE_ID
                        ]
                    ),
                    [
                        SyncHistory::FIELD_PRODUCT_ID,
                        SyncHistory::FIELD_PARENT_ID,
                        SyncHistory::FIELD_STORE_ID
                    ]
                );
                $table->addForeignKey(
                    $installer->getFkName(
                        $installer->getTable(SyncHistoryResourceModel::TABLE),
                        SyncHistory::FIELD_STORE_ID,
                        'store',
                        'store_id'
                    ),
                    SyncHistory::FIELD_STORE_ID,
                    $setup->getTable('store'),
                    'store_id',
                    Table::ACTION_CASCADE
                );
                $connection->createTable($table);
            }
        }

        if (version_compare($context->getVersion(), '2.10.3', '<=')) {
            $this->trigger->dropTriggerIfFoundExist();
            $indexer = $this->indexerRegistry->get(ProductSyncIndexer::INDEXER_ID);
            $indexer->setScheduled(true);
            $this->configWriter->save(
                ConfigHelper::XML_PATH_TRIGGER_OPTIONS,
                0,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT
            );
        }

        $installer->endSetup();
    }
}
