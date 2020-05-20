<?php
/**
 * Klevu trigger model
 */

namespace Klevu\Search\Model;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\DB\Ddl\TriggerFactory;

class Trigger
{
    const KLEVU_TRIGGER_CATALOG_PRODUCT_INDEX_PRICE = "Update_KlevuProductSync_For_CPIP";
    const KLEVU_TRIGGER_CATALOGINVENTORY_STOCK_STATUS = "Update_KlevuProductSync_For_LSA";
    const KLEVU_TRIGGER_CATALOGRULE_PRODUCT_PRICE = "Update_KlevuProductSync_For_CPP";

    /**
     * @var \Magento\Framework\DB\Ddl\TriggerFactory
     */
    protected $_triggerFactory;

    public function __construct(
        TriggerFactory $triggerFactory,
        ResourceConnection $resourceConnection,
        DeploymentConfig $deploymentConfig
    )
    {
        $this->_triggerFactory = $triggerFactory;
        $this->_resourceConnection = $resourceConnection;
        $this->_deploymentConfig = $deploymentConfig;
    }


    public function activateTrigger()
    {
        $connection = $this->_resourceConnection->getConnection();
        $dbname = $this->_deploymentConfig->get(
            ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT
            . '/' . ConfigOptionsListConstants::KEY_NAME
        );

        //First dropping trigger if found Exists
        $this->dropTriggerIfFoundExist();

        /** @var \Magento\Framework\DB\Ddl\Trigger $trigger */
        $trigger_cpip = $this->_triggerFactory->create()
            ->setName(static::KLEVU_TRIGGER_CATALOG_PRODUCT_INDEX_PRICE)
            ->setTime(\Magento\Framework\DB\Ddl\Trigger::TIME_AFTER)
            ->setEvent(\Magento\Framework\DB\Ddl\Trigger::EVENT_UPDATE)
            ->setTable($this->_resourceConnection->getTableName('catalog_product_index_price'));
        $trigger_cpip->addStatement("IF NEW.price <> OLD.price || NEW.final_price <> OLD.final_price THEN
                IF (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . $dbname . "' AND  table_name = '" . $this->_resourceConnection->getTableName('klevu_product_sync') . "') <> 0 THEN
                UPDATE `" . $this->_resourceConnection->getTableName('klevu_product_sync') . "`
                SET last_synced_at = '0000-00-00 00:00:00'
                WHERE product_id = NEW.entity_id;
                END IF ;
                END IF ;");
        $connection->createTrigger($trigger_cpip);

        $trigger_lsa = $this->_triggerFactory->create()
            ->setName(static::KLEVU_TRIGGER_CATALOGINVENTORY_STOCK_STATUS)
            ->setTime(\Magento\Framework\DB\Ddl\Trigger::TIME_AFTER)
            ->setEvent(\Magento\Framework\DB\Ddl\Trigger::EVENT_UPDATE)
            ->setTable($this->_resourceConnection->getTableName('cataloginventory_stock_status'));
        $trigger_lsa->addStatement("IF NEW.stock_status <> OLD.stock_status THEN
                IF (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . $dbname . "' AND table_name = '" . $this->_resourceConnection->getTableName('klevu_product_sync') . "') <> 0 THEN
                UPDATE `" . $this->_resourceConnection->getTableName('klevu_product_sync') . "`
                SET last_synced_at = '0000-00-00 00:00:00'
                WHERE product_id = NEW.product_id;
                END IF ;
                END IF ;");
        $connection->createTrigger($trigger_lsa);

        $trigger_cpp = $this->_triggerFactory->create()
            ->setName(static::KLEVU_TRIGGER_CATALOGRULE_PRODUCT_PRICE)
            ->setTime(\Magento\Framework\DB\Ddl\Trigger::TIME_BEFORE)
            ->setEvent(\Magento\Framework\DB\Ddl\Trigger::EVENT_UPDATE)
            ->setTable($this->_resourceConnection->getTableName('catalogrule_product_price'));
        $trigger_cpp->addStatement("IF NEW.rule_price <> OLD.rule_price THEN
                IF (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '" . $dbname . "' AND table_name = '" . $this->_resourceConnection->getTableName('klevu_product_sync') . "') <> 0 THEN
                UPDATE `" . $this->_resourceConnection->getTableName('klevu_product_sync') . "`
                SET last_synced_at = '0000-00-00 00:00:00'
                WHERE product_id = NEW.product_id;
                END IF ;
                END IF ;");
        $connection->createTrigger($trigger_cpp);
    }

    public function dropTriggerIfFoundExist()
    {
        $connection = $this->_resourceConnection->getConnection();
        $connection->query("DROP TRIGGER IF EXISTS " . strtolower("Update_KlevuProductSync_For_CPIP"));
        $connection->query("DROP TRIGGER IF EXISTS " . strtolower("Update_KlevuProductSync_For_LSA"));
        $connection->query("DROP TRIGGER IF EXISTS " . strtolower("Update_KlevuProductSync_For_CPP"));

    }
}
