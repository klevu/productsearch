<?php

use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;

$storeCodesToDelete = [
    'es_es',
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

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
