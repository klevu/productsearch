<?php

use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$fixtures = [
    'default' => 'Default Store View',
    'es_es' => 'Spanish Store View',
];

$objectManager = Bootstrap::getObjectManager();
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);

$website = $storeManager->getWebsite();

$sortOrder = 0;
foreach ($fixtures as $storeCode => $storeName) {
    /** @var Store $store */
    $store = $objectManager->create(Store::class);
    if ($store->load($storeCode)->getId()) {
        continue;
    }

    $sortOrder += 10;

    $store->setCode($storeCode);
    $store->setWebsiteId($website->getId());
    $store->setGroupId($website->getDefaultGroupId());
    $store->setName($storeName);
    $store->setSortOrder($sortOrder);
    $store->setIsActive(1);
    $store->save();
}

