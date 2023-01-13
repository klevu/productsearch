<?php
/** @noinspection PhpDeprecationInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$attributeCodesToDelete = [
    'klevu_boolean_attribute',
];

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var EavConfig $eavConfig */
$eavConfig = Bootstrap::getObjectManager()->get(EavConfig::class);

foreach ($attributeCodesToDelete as $attributeCodeToDelete) {
    $attribute = $eavConfig->getAttribute('catalog_product', $attributeCodeToDelete);
    if ($attribute instanceof AbstractAttribute && $attribute->getId()) {
        $attribute->delete();
    }
}

$eavConfig->clear();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
