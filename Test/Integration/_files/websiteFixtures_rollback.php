<?php

use Magento\Framework\Registry;
use Magento\Store\Model\Group as StoreGroup;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

$websiteCodesToDelete = [
    'klevu_test_website_1',
    'klevu_test_website_2',
];
$storeGroupCodesToDelete = [
    'klevu_test_group_1',
    'klevu_test_group_2',
];
$storeCodesToDelete = [
    'klevu_test_store_1',
    'klevu_test_store_2',
];

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

foreach ($storeCodesToDelete as $storeCode) {
    $store = $objectManager->create(Store::class);
    $store->load($storeCode, 'code');
    if ($store->getId()) {
        $store->delete();
    }
}

if (method_exists(StoreGroup::class, 'setCode')) {
    foreach ($storeGroupCodesToDelete as $storeGroupCode) {
        $storeGroup = $objectManager->create(StoreGroup::class);
        $storeGroup->load($storeGroupCode, 'code');
        if ($storeGroup->getId()) {
            $storeGroup->delete();
        }
    }
}

foreach ($websiteCodesToDelete as $websiteCode) {
    $website = $objectManager->create(Website::class);
    $website->load($websiteCode, 'code');
    if ($website->getId()) {
        $website->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
