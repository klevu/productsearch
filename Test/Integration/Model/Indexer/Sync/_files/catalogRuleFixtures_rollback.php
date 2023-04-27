<?php

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Api\Data\RuleInterface as CatalogRuleInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule\Collection as CatalogRuleCollection;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var CatalogRuleRepositoryInterface $catalogRuleRepository */
$catalogRuleRepository = $objectManager->get(CatalogRuleRepositoryInterface::class);

/** @var CatalogRuleCollection $catalogRuleCollection */
$catalogRuleCollection = $objectManager->get(CatalogRuleCollection::class);
$catalogRuleCollection->addFieldToFilter('name', ['eq' => 'klevu_catalog_rule_1']);
/** @var CatalogRuleInterface[] $catalogRules */
$catalogRules = $catalogRuleCollection->getItems();

foreach ($catalogRules as $catalogRule) {
    try {
        $catalogRuleRepository->delete($catalogRule);
    } catch (CouldNotDeleteException $e) {
        // this is fine, rule already deleted
    }
}
