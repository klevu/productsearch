<?php

use Magento\CatalogRule\Model\ResourceModel\Rule\Collection as CatalogRuleCollection;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$userNamesToDelete = [
    'dummy_username',
    'dummy_username_2'
];

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var CatalogRuleCollection $collection */
$collection = $objectManager->create(\Magento\User\Model\ResourceModel\User\Collection::class);
$collection->addFieldToFilter('username', ['in' => $userNamesToDelete]);
$collection->load();
foreach ($collection as $rule) {
    $rule->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
