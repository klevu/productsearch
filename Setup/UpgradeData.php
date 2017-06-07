<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Klevu\Search\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Upgrade Data script
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Init
     *
     * @param CategorySetupFactory $categorySetupFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '2.1.1') < 0) {
          //fix for content and addtocart module version if the current version less then 2.1.1
            $setup_module = $setup->getTable('setup_module');
            $product_search_version = "2.1.7";
            $setup->run("UPDATE `{$setup_module}` SET `schema_version` = '{$product_search_version}', data_version = '{$product_search_version}' WHERE (`{$setup_module}`.`module` = 'Klevu_Content' OR `{$setup_module}`.`module` = 'Klevu_Addtocart' ) AND data_version = '10.0.4'");
        }
        
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        if (version_compare($context->getVersion(), '2.1.0') < 0) {
            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'is_exclude_cat',
                [
                    'type' => 'int',
                    'label' => 'Exclude in Search',
                    'input' => 'select',
                    'sort_order' => 333,
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'global' => 0,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'group' => 'Display Settings'
                ]
            );
        }
        
        $setup->endSetup();
    }
}
