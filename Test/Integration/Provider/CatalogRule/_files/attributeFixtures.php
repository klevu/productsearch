<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Eav\Model\Config as EavConfig;
use Magento\TestFramework\Helper\Bootstrap;

require 'attributeFixtures_rollback.php';

$objectManager = Bootstrap::getObjectManager();

/** @var CategorySetup $installer */
$installer = $objectManager->create(
    CategorySetup::class
);

/** @var $attribute EavAttribute */
$attribute = $objectManager->create(
    EavAttribute::class
);
$attribute->setData(
    [
        'attribute_code' => 'klevu_test_attribute',
        'entity_type_id' => $installer->getEntityTypeId('catalog_product'),
        'is_global' => 1,
        'is_user_defined' => 1,
        'frontend_input' => 'text',
        'is_used_for_promo_rules' => 1,
        'backend_type' => 'text',
    ]
);
$attribute->save();

/* Assign attribute to attribute set */
$installer->addAttributeToGroup(
    'catalog_product',
    'Default',
    'General',
    $attribute->getId()
);

/** @var EavConfig $eavConfig */
$eavConfig = $objectManager->get(EavConfig::class);
$eavConfig->clear();
