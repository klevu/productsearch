<?php

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Api\Data\RuleInterface as CatalogRuleInterface;
use Magento\CatalogRule\Model\Rule\Condition\Combine as ConditionCombine;
use Magento\CatalogRule\Model\Rule\Condition\Product as ConditionProduct;
use Magento\CatalogRule\Model\Rule\Job as CatalogRuleJob;
use Magento\CatalogRule\Model\RuleFactory as CatalogRuleFactory;
use Magento\Framework\DataObject;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Website $baseWebsite */
$baseWebsite = $objectManager->create(Website::class);
$baseWebsite->load('base', 'code');

/** @var Website $website1 */
$website1 = $objectManager->create(Website::class);
$website1->load('klevu_test_website_1', 'code');

/** @var Website $website2 */
$website2 = $objectManager->create(Website::class);
$website2->load('klevu_test_website_2', 'code');


$catalogRuleFactory = $objectManager->get(CatalogRuleFactory::class);
/** @var CatalogRuleInterface $catalogRule */
$catalogRule = $catalogRuleFactory->create();

$catalogRule->setName('klevu_catalog_rule_1');
$catalogRule->setDescription('description');
$catalogRule->setIsActive(1);
$catalogRule->setCustomerGroupIds(implode(',', [1]));
$catalogRule->setWebsiteIds(
    implode(
        ',',
        array_filter(
            [
                $baseWebsite->getId(),
                $website1->getId(),
                $website2->getId(),
            ]
        )
    )
);
$catalogRule->setFromDate('');
$catalogRule->setToDate('');
$catalogRule->setSimpleAction('by_fixed');
$catalogRule->setDiscountAmount(10);
$catalogRule->setStopRulesProcessing(0);

$conditions = [];
$conditions["1"] = [
    "type" => ConditionCombine::class,
    "aggregator" => "all",
    "value" => 1,
    "new_child" => ""
];
$conditions["1--1"] = [
    "type" => ConditionProduct::class,
    "attribute" => "sku",
    "operator" => "==",
    "value" => "klevu-simple-1"
];
$catalogRule->setData('conditions',$conditions);

// Validating rule data before Saving
$validateResult = $catalogRule->validateData(new DataObject($catalogRule->getData()));
if ($validateResult !== true) {
    foreach ($validateResult as $errorMessage) {
        echo $errorMessage;
    }
    return;
}

try {
    $catalogRule->loadPost($catalogRule->getData());
    $catalogRuleRepository = $objectManager->get(CatalogRuleRepositoryInterface::class);
    $catalogRuleRepository->save($catalogRule);
    
    $ruleJob = $objectManager->get(CatalogRuleJob::class);
    $ruleJob->applyAll();
} catch (Exception $e) {
    echo $e->getMessage();
}
