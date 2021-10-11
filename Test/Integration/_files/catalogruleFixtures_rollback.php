<?php

use Magento\CatalogRule\Model\Rule as CatalogRule;
use Magento\CatalogRule\Model\ResourceModel\Rule\Collection as CatalogRuleCollection;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$ruleNamesToDelete = [
    'Klevu Test Rule 1',
];

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var CatalogRuleCollection $collection */
$collection = $objectManager->create(CatalogRuleCollection::class);
$collection->addFieldToFilter('name', ['in' => $ruleNamesToDelete]);
$collection->load();
foreach ($collection as $rule) {
    $rule->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
