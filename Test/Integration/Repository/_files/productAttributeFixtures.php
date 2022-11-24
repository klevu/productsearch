<?php
/** @noinspection PhpUnhandledExceptionInspection */

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\TestFramework\Helper\Bootstrap;

include __DIR__ . '/productAttributeFixtures_rollback.php';

$objectManager = Bootstrap::getObjectManager();

/** @var EavConfig $eavConfig */
$eavConfig = $objectManager->create(EavConfig::class);
/** @var CategorySetup $installer */
$installer = $objectManager->create(CategorySetup::class);
/** @var AttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);

$configurableAttribute = $eavConfig->getAttribute('catalog_product', 'klevu_synctest_configurable');
$eavConfig->clear();

$productEntityTypeId = $installer->getEntityTypeId('catalog_product');

if (!$configurableAttribute->getId()) {
    $configurableAttribute = $objectManager->create(EavAttribute::class);
    $configurableAttribute->addData([
        'attribute_code' => 'klevu_synctest_configurable',
        'entity_type_id' => $productEntityTypeId,
        'is_global' => 1,
        'is_user_defined' => 1,
        'frontend_input' => 'select',
        'is_unique' => 0,
        'is_required' => 0,
        'is_searchable' => 0,
        'is_visible_in_advanced_search' => 0,
        'is_comparable' => 0,
        'is_filterable' => 0,
        'is_filterable_in_search' => 0,
        'is_used_for_promo_rules' => 0,
        'is_html_allowed_on_front' => 1,
        'is_visible_on_front' => 0,
        'used_in_product_listing' => 0,
        'used_for_sort_by' => 0,
        'frontend_label' => ['Klevu Sync Test Configurable'],
        'backend_type' => 'int',
        'option' => [
            'value' => [
                'option_0' => ['Option 1'],
                'option_1' => ['Option 2'],
                'option_2' => ['Option 3'],
                'option_3' => ['Option 4'],
                'option_4' => ['Option 5'],
                'option_5' => ['Option 6'],
                'option_6' => ['Option 7'],
                'option_7' => ['Option 8'],
                'option_8' => ['Option 9'],
                'option_9' => ['Option 10'],
                'option_10' => ['Option 11'],
                'option_11' => ['Option 12'],
                'option_12' => ['Option 13'],
                'option_13' => ['Option 14'],
                'option_14' => ['Option 15'],
                'option_15' => ['Option 16'],
                'option_16' => ['Option 17'],
                'option_17' => ['Option 18'],
                'option_18' => ['Option 19'],
                'option_19' => ['Option 20'],
            ],
            'order' => [
                'option_0' => 1,
                'option_1' => 2,
                'option_2' => 3,
                'option_3' => 4,
                'option_4' => 5,
                'option_5' => 6,
                'option_6' => 7,
                'option_7' => 8,
                'option_8' => 9,
                'option_9' => 10,
                'option_10' => 11,
                'option_11' => 12,
                'option_12' => 13,
                'option_13' => 14,
                'option_14' => 15,
                'option_15' => 16,
                'option_16' => 17,
                'option_17' => 18,
                'option_18' => 19,
                'option_19' => 20,
            ],
        ],
    ]);
    $attributeRepository->save($configurableAttribute);

    $installer->addAttributeToGroup(
        'catalog_product',
        'Default',
        'General',
        $configurableAttribute->getId()
    );
}

$eavConfig->clear();
