<?php

namespace Klevu\Search\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

/**
 * db_schema.xml index config does not use referenceId to create index name.
 * Index names are auto generated based on column names.
 * This resulted in duplicate indexes.
 * Here we remove the old duplicate indexes if both old and new exist.
 */
class RemoveDuplicateIndexes implements SchemaPatchInterface
{
    const TABLE_NAME = 'klevu_product_sync';
    const INDEX_GROUP_OLD = 'KLEVU_GROUP_ID';
    const INDEX_GROUP_NEW = 'KLEVU_PRODUCT_SYNC_PRODUCT_ID_PARENT_ID_STORE_ID_TYPE';
    const INDEX_PARENT_PRODUCT_OLD = 'KLEVU_PRODUCT_SYNC_PARENT_PRODUCT_ID';
    const INDEX_PARENT_PRODUCT_NEW = 'KLEVU_PRODUCT_SYNC_PARENT_ID_PRODUCT_ID';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * PatchInitial constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
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
     * @return $this|RemoveDuplicateIndexes
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $indexList = $connection->getIndexList($this->moduleDataSetup->getTable(static::TABLE_NAME));
        if (array_key_exists(static::INDEX_GROUP_OLD, $indexList) &&
            array_key_exists(static::INDEX_GROUP_NEW, $indexList)
        ) {
            $connection->dropIndex(
                $this->moduleDataSetup->getTable(static::TABLE_NAME),
                static::INDEX_GROUP_OLD
            );
        }
        if (array_key_exists(static::INDEX_PARENT_PRODUCT_OLD, $indexList)
            && array_key_exists(static::INDEX_PARENT_PRODUCT_NEW, $indexList)
        ) {
            $connection->dropIndex(
                $this->moduleDataSetup->getTable(static::TABLE_NAME),
                static::INDEX_PARENT_PRODUCT_OLD
            );
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }
}
