<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Klevu\Search\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        
        $installer = $setup;
        $installer->startSetup();
        
        if (version_compare($context->getVersion(), '2.0.2') < 0) {
            //code to upgrade to 2.0.2
            $order_sync_table = $installer->getTable('klevu_order_sync');
            $installer->run("ALTER TABLE `{$order_sync_table}` ADD `klevu_session_id` VARCHAR(255) NOT NULL , ADD `ip_address` VARCHAR(255) NOT NULL , ADD `date` DATETIME NOT NULL");
        }
        
        if (version_compare($context->getVersion(), '2.0.10') < 0) {
            //code to upgrade to 2.0.2
            $klevu_sync_table = $installer->getTable('klevu_product_sync');
            $installer->run("ALTER TABLE `{$klevu_sync_table}` ADD `error_flag` INT(11) NOT NULL DEFAULT '0' AFTER `type`");
        }
        
        if (version_compare($context->getVersion(), '2.1.10') < 0) {
            $sql = "SHOW COLUMNS FROM `{$installer->getTable('klevu_product_sync')}` LIKE 'test_mode'";
            if (!empty($setup->getConnection()->fetchAll($sql))) {
                //remove test mode
                $setup->run("ALTER TABLE `{$installer->getTable('klevu_product_sync')}` DROP `test_mode`");
            }
            
            $setup->run("ALTER TABLE `{$installer->getTable('klevu_product_sync')}` ADD KEY `KLEVU_PRODUCT_SYNC_PARENT_PRODUCT_ID` (`parent_id`,`product_id`), 
			ADD KEY `KLEVU_PRODUCT_SYNC_STORE_ID` (`store_id`)");
            
            $order_sync_table = $installer->getTable('klevu_order_sync');
            $installer->run("ALTER TABLE `{$order_sync_table}` ADD `idcode` VARCHAR(255) NOT NULL AFTER `date`");
        }
		
		if (version_compare($context->getVersion(), '2.2.5') < 0) {
			$setup->run("ALTER TABLE `{$installer->getTable('klevu_product_sync')}` DROP PRIMARY KEY");
			$setup->run("ALTER TABLE `{$installer->getTable('klevu_product_sync')}` ADD `row_id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`row_id`)");
			$setup->run("ALTER TABLE `{$installer->getTable('klevu_product_sync')}` ADD UNIQUE KEY `KLEVU_GROUP_ID` (`product_id`,`parent_id`,`store_id`,`type`)");
			$order_sync_table = $installer->getTable('klevu_order_sync');
			$installer->run("ALTER TABLE `{$order_sync_table}` ADD `checkoutdate` VARCHAR(255) NOT NULL AFTER `idcode`");
		}
        
        $installer->endSetup();
    }
}
