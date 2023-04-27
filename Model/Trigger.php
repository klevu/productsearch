<?php

namespace Klevu\Search\Model;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\TriggerFactory;

class Trigger
{
    const KLEVU_TRIGGER_CATALOG_PRODUCT_INDEX_PRICE = "Update_KlevuProductSync_For_CPIP";
    const KLEVU_TRIGGER_CATALOGINVENTORY_STOCK_STATUS = "Update_KlevuProductSync_For_LSA";
    const KLEVU_TRIGGER_CATALOGRULE_PRODUCT_PRICE = "Update_KlevuProductSync_For_CPP";

    /**
     * @var TriggerFactory
     */
    protected $_triggerFactory;
    /**
     * @var ResourceConnection
     */
    protected $_resourceConnection;
    /**
     * @var DeploymentConfig
     */
    protected $_deploymentConfig;

    /**
     * @param TriggerFactory $triggerFactory
     * @param ResourceConnection $resourceConnection
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        TriggerFactory $triggerFactory,
        ResourceConnection $resourceConnection,
        DeploymentConfig $deploymentConfig
    ) {
        $this->_triggerFactory = $triggerFactory;
        $this->_resourceConnection = $resourceConnection;
        $this->_deploymentConfig = $deploymentConfig;
    }

    /**
     * @return void
     *
     * @deprecated triggers added via mview
     * @see /etc/mview.xml & /etc/indexer.xml
     */
    public function activateTrigger()
    {
        $this->dropTriggerIfFoundExist();
    }

    /**
     * @return void
     */
    public function dropTriggerIfFoundExist()
    {
        $connection = $this->_resourceConnection->getConnection();
        $connection->query(
            // phpcs:ignore Magento2.SQL.RawQuery.FoundRawSql
            "DROP TRIGGER IF EXISTS " . strtolower(self::KLEVU_TRIGGER_CATALOG_PRODUCT_INDEX_PRICE)
        );
        $connection->query(
            // phpcs:ignore Magento2.SQL.RawQuery.FoundRawSql
            "DROP TRIGGER IF EXISTS " . strtolower(self::KLEVU_TRIGGER_CATALOGINVENTORY_STOCK_STATUS)
        );
        $connection->query(
            // phpcs:ignore Magento2.SQL.RawQuery.FoundRawSql
            "DROP TRIGGER IF EXISTS " . strtolower(self::KLEVU_TRIGGER_CATALOGRULE_PRODUCT_PRICE)
        );
    }
}
