<?php

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

        $setup->endSetup();
    }
}
