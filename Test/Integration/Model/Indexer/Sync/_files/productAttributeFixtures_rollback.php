<?php
/** @noinspection PhpDeprecationInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$attributeCodesToDelete = [
    'klevu_test_configurable',
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
        try {
            $attribute->delete();
        } catch (Exception $e) {
            // previous product fixtures may not have rolled back and attribute is still assigned to configurable
            // we don't want to kill the tests if that is the case.
        }
    }
}

$eavConfig->clear();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
