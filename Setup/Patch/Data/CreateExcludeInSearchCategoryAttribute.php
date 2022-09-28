<?php

namespace Klevu\Search\Setup\Patch\Data;

use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreateExcludeInSearchCategoryAttribute implements DataPatchInterface
{
    const ATTRIBUTE_CODE = 'is_exclude_cat';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @return string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return $this|CreateExcludeInSearchCategoryAttribute
     * @throws LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        if (!($eavSetup->getAttributeId(Category::ENTITY, static::ATTRIBUTE_CODE))) {
            $eavSetup->addAttribute(
                Category::ENTITY,
                static::ATTRIBUTE_CODE,
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
        $this->moduleDataSetup->endSetup();

        return $this;
    }
}
