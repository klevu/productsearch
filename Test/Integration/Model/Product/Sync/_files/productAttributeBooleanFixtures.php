<?php
/** @noinspection PhpUnhandledExceptionInspection */

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\TestFramework\Helper\Bootstrap;

include __DIR__ . '/productAttributeBooleanFixtures_rollback.php';

$objectManager = Bootstrap::getObjectManager();

/** @var EavConfig $eavConfig */
$eavConfig = $objectManager->create(EavConfig::class);
/** @var CategorySetup $installer */
$installer = $objectManager->create(CategorySetup::class);
/** @var AttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);

$booleanAttribute = $eavConfig->getAttribute('catalog_product', 'klevu_boolean_attribute');
$eavConfig->clear();

$productEntityTypeId = $installer->getEntityTypeId('catalog_product');

if (!$booleanAttribute->getId()) {
    $booleanAttribute = $objectManager->create(EavAttribute::class);
    $booleanAttribute->addData([
        'attribute_code' => 'klevu_boolean_attribute',
        'entity_type_id' => $productEntityTypeId,
        'source_model' => Boolean::class,
        'frontend_input' => 'boolean',
        'frontend_label' => ['Klevu Boolean Attribute'],
        'backend_type' => 'int',
        'default_value' => 0,
        'is_global' => ScopedAttributeInterface::SCOPE_STORE,
        'is_user_defined' => 1,
        'is_unique' => 0,
        'is_required' => 0,
        'is_searchable' => 0,
        'is_visible_in_advanced_search' => 0,
        'is_comparable' => 0,
        'is_filterable' => 0,
        'is_filterable_in_search' => 1,
        'is_used_for_promo_rules' => 0,
        'is_html_allowed_on_front' => 1,
        'is_visible_on_front' => 0,
        'used_in_product_listing' => 0,
        'used_for_sort_by' => 0,
    ]);
    $attributeRepository->save($booleanAttribute);

    $installer->addAttributeToGroup(
        'catalog_product',
        'Default',
        'General',
        $booleanAttribute->getId()
    );
}
