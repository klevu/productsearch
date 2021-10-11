<?php

use Magento\CatalogRule\Model\Rule as CatalogRule;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

$storeManager = $objectManager->get(StoreManagerInterface::class);

$websiteIds = [];
foreach (['klevu_test_store_1', 'klevu_test_store_2'] as $storeCode) {
    $store = $storeManager->getStore($storeCode);
    $websiteIds[] = $store->getWebsiteId();
}

$fixtures = [
    [
        'name' => 'Klevu Test Rule 1',
        'is_active' => 1,
        'customer_group_ids' => [0,1,2,3],
        'website_ids' => array_values($websiteIds),
        'from_date' => date('Y-m-d', time() - 86400),
        'to_date' => date('Y-m-d', time() + 86400),
        'simple_action' => 'by_percent',
        'discount_amount' => 10,
        'stop_rules_processing' => 0,
    ],
];

foreach ($fixtures as $fixture) {
    /** @var CatalogRule $catalogRule */
    $catalogRule = $objectManager->create(CatalogRule::class);
    $catalogRule->addData($fixture);
    $catalogRule->loadPost($catalogRule->getData());
    $catalogRule->save();
}
