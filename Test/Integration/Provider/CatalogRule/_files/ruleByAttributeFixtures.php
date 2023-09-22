<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\CatalogRule\Model\Rule;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\CatalogRule\Model\Rule\Condition\Product;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

require 'ruleByAttributeFixtures_rollback.php';

$objectManager = Bootstrap::getObjectManager();
/** @var Website $baseWebsite */
$baseWebsite = $objectManager->create(Website::class);
$baseWebsite->load('base', 'code');

$ruleFactory = $objectManager->get(RuleFactory::class);
/** @var Rule $rule */
$rule = $ruleFactory->create();
$rule->loadPost([
    'name' => 'klevu_test_rule',
    'is_active' => '1',
    'stop_rules_processing' => 0,
    'website_ids' => [$baseWebsite->getId()],
    'customer_group_ids' => [0, 1],
    'discount_amount' => 2,
    'simple_action' => 'by_percent',
    'from_date' => '',
    'to_date' => '',
    'sort_order' => 0,
    'sub_is_enable' => 0,
    'sub_discount_amount' => 0,
    'conditions' => [
        '1' => [
            'type' => Combine::class,
            'aggregator' => 'all',
            'value' => '1',
            'new_child' => '',
        ],
        '1--1' => [
            'type' => Product::class,
            'attribute' => 'klevu_test_attribute',
            'operator' => '==',
            'value' => 'test_attribute_value',
        ],
    ],
]);
$rule->save();
