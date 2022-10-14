<?php

namespace Klevu\Search\Setup;

use Klevu\Search\Model\Attribute\Rating;
use Klevu\Search\Model\Attribute\ReviewCount;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), '2.1.1') < 0) {
            //fix for content and addtocart module version if the current version less then 2.1.1
            $setup_module = $setup->getTable('setup_module');
            $product_search_version = "2.1.7";
            $setup->run("UPDATE `{$setup_module}` " .
                "SET `schema_version` = '{$product_search_version}', data_version = '{$product_search_version}' " .
                "WHERE " .
                "(`{$setup_module}`.`module` = 'Klevu_Content' OR `{$setup_module}`.`module` = 'Klevu_Addtocart') " .
                "AND data_version = '10.0.4'");
        }

        if (version_compare($context->getVersion(), '2.1.0') < 0) {
            $eavSetup->addAttribute(
                Category::ENTITY,
                'is_exclude_cat',
                [
                    'type' => 'int',
                    'label' => 'Exclude in Search',
                    'input' => 'select',
                    'sort_order' => 333,
                    'source' => Boolean::class,
                    'global' => 0,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'group' => 'Display Settings'
                ]
            );
        }

        if (version_compare($context->getVersion(), '2.9.0', '<')) {
            if (!$eavSetup->getAttributeId(Product::ENTITY, ReviewCount::ATTRIBUTE_CODE)) {
                $eavSetup->addAttribute(
                    Product::ENTITY,
                    ReviewCount::ATTRIBUTE_CODE,
                    [
                        'type' => 'int',
                        'label' => 'Klevu Review Count',
                        'note' => 'Automatically calculated and populated by Klevu to sync ratings data. ' .
                            'For more information, please refer to the Klevu knowledgebase.',
                        'input' => 'text',
                        'global' => 0,
                        'default' => null,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => true,
                        'group' => 'Product Details',
                    ]
                );
            }

            if ($eavSetup->getAttributeId(Product::ENTITY, Rating::ATTRIBUTE_CODE)) {
                $eavSetup->updateAttribute(
                    Product::ENTITY,
                    Rating::ATTRIBUTE_CODE,
                    [
                        'frontend_label' => 'Klevu Rating',
                        'note' => 'Automatically calculated and populated by Klevu to sync ratings data. ' .
                            'For more information, please refer to the Klevu knowledgebase.',
                    ]
                );
            }
        }

        $setup->endSetup();
    }
}
