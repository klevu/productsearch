<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\ObjectManagerInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Magento\Tax\Api\Data\TaxClassInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Api\Data\TaxRateInterfaceFactory;
use Magento\Tax\Api\Data\TaxRateInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Tax\Api\Data\TaxRuleInterfaceFactory;
use Magento\Tax\Api\Data\TaxRuleInterface;

include __DIR__ . '/taxClassFixtures_rollback.php';

/** @var ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();
/** @var TaxClassRepositoryInterface $taxClassRepository */
$taxClassRepository = $objectManager->get(TaxClassRepositoryInterface::class);
$taxClassFactory = $objectManager->get(TaxClassInterfaceFactory::class);

try {
    $taxCustomerClass = $taxClassRepository->get(3);
    $taxCustomerClassId = $taxCustomerClass->getClassId();
} catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
    /** @var TaxClassInterface $taxClassDataObject */
    $taxClassDataObject = $taxClassFactory->create();
    $taxClassDataObject->setClassName('Retail Customer');
    $taxClassDataObject->setClassType(TaxClassManagementInterface::TYPE_CUSTOMER);
    $taxCustomerClassId = $taxClassRepository->save($taxClassDataObject);
}

try {
    $taxProductClass = $taxClassRepository->get(2);
    $taxProductClassId = $taxProductClass->getClassId();
} catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
    // if default tax class id does not exist, create a new one
    $taxClassDataObject = $taxClassFactory->create();
    $taxClassDataObject->setClassName('Taxable Goods');
    $taxClassDataObject->setClassType(TaxClassManagementInterface::TYPE_PRODUCT);
    $taxProductClassId = $taxClassRepository->save($taxClassDataObject);
}

$taxRateFactory = $objectManager->get(TaxRateInterfaceFactory::class);
/** @var TaxRateInterface $taxRate */
$taxRate = $taxRateFactory->create();
$taxRate->setTaxCountryId('GB');
$taxRate->setTaxRegionId(0);
$taxRate->setTaxPostcode('*');
$taxRate->setCode('Klevu Test Tax Rate');
$taxRate->setRate('20');
/** @var TaxRateRepositoryInterface $taxRateRepository */
$taxRateRepository = $objectManager->get(TaxRateRepositoryInterface::class);
$taxRate = $taxRateRepository->save($taxRate);

/** @var TaxRuleRepositoryInterface $taxRuleRepository */
$taxRuleRepository = $objectManager->get(TaxRuleRepositoryInterface::class);
$taxRuleFactory = $objectManager->get(TaxRuleInterfaceFactory::class);

/** @var TaxRuleInterface $taxRule */
$taxRule = $taxRuleFactory->create();
$taxRule->setCode('Test Rule');
$taxRule->setCustomerTaxClassIds([$taxCustomerClassId]);
$taxRule->setProductTaxClassIds([$taxProductClassId]);
$taxRule->setTaxRateIds([$taxRate->getId()]);
$taxRule->setPriority(0);
$taxRuleRepository->save($taxRule);
