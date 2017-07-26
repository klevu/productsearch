<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Klevu\Search\Setup;
 
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
 
class Uninstall implements UninstallInterface
{
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
		//remove triggers
		$setup->run("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_CPIP;");
		$setup->run("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_LSA;");
		$setup->run("DROP TRIGGER IF EXISTS Update_KlevuProductSync_For_CPP;");
		
		//remove tables
		$notifications_table = $setup->getTable('klevu_notification');
        $setup->run("DROP TABLE IF EXISTS `{$notifications_table}`");
		$product_sync_table = $setup->getTable('klevu_product_sync');
        $setup->run("DROP TABLE IF EXISTS `{$product_sync_table}`");
		$order_sync_table = $setup->getTable('klevu_order_sync');
        $setup->run("DROP TABLE IF EXISTS `{$order_sync_table}`");
		
        $setup->endSetup();
    }
}