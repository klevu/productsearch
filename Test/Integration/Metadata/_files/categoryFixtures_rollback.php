<?php
/** @noinspection PhpDeprecationInspection */

use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$urlKeysToDelete = [
    'klevu-test-category-1',
    'klevu-test-category-1-1',
];

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$collection = $objectManager->create(CategoryCollection::class);
$collection->addAttributeToFilter('url_key', ['in' => $urlKeysToDelete]);
$collection->load();
$collection->delete();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
