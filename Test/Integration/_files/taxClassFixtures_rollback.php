<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Registry;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Model\ClassModel;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\ObjectManagerInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Tax\Model\Calculation\Rule;
use Magento\Tax\Model\Calculation\Rate;
use Magento\Tax\Api\TaxRateRepositoryInterface;

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();
$taxRuleRepository = $objectManager->get(TaxRuleRepositoryInterface::class);
/** @var SearchCriteriaBuilder $searchBuilder */
$searchBuilder = $objectManager->get(SearchCriteriaBuilder::class);
$searchBuilder->addFilter(Rule::KEY_CODE, 'Test Rule');
$searchCriteria = $searchBuilder->create();
$taxRuleResults = $taxRuleRepository->getList($searchCriteria);
$taxRules = $taxRuleResults->getItems();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);
foreach ($taxRules as $taxRule) {
    try {
        $taxRuleRepository->delete($taxRule);
    } catch (NoSuchEntityException $exception) {
        //Rule already removed
    }
}

$searchBuilder->addFilter(Rate::KEY_CODE, 'Klevu Test Tax Rate');
$searchCriteria = $searchBuilder->create();
/** @var TaxRateRepositoryInterface $groupRepository */
$taxRateRepository = $objectManager->get(TaxRateRepositoryInterface::class);
$taxRatesResult = $taxRateRepository->getList($searchCriteria);
$taxRates = $taxRatesResult->getItems();
foreach ($taxRates as $taxRate) {
    try {
        $taxRateRepository->delete($taxRate);
    } catch (NoSuchEntityException $exception) {
        //TaxRate already removed
    }
}
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
