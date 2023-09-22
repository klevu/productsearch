<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
use Magento\CatalogRule\Model\Indexer\IndexBuilder;
use Magento\CatalogRule\Model\ResourceModel\Rule as RuleResourceModel;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var RuleResourceModel $catalogRuleResource */
$catalogRuleResource = $objectManager->create(RuleResourceModel::class);

//Retrieve rule id by name
$select = $catalogRuleResource->getConnection()->select();
$select->from($catalogRuleResource->getMainTable(), 'rule_id');
$select->where('name = ?', 'klevu_test_rule');
$ruleId = $catalogRuleResource->getConnection()->fetchOne($select);

try {
    /** @var CatalogRuleRepositoryInterface $ruleRepository */
    $ruleRepository = $objectManager->create(CatalogRuleRepositoryInterface::class);
    $ruleRepository->deleteById($ruleId);
} catch (\Exception $ex) {
    //Nothing to remove
}
/** @var IndexBuilder $indexBuilder */
$indexBuilder = $objectManager->get(IndexBuilder::class);
$indexBuilder->reindexFull();
