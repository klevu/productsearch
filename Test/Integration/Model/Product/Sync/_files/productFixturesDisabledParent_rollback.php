<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$skusToDelete = [
    'klevu_simple_synctest_child_instock_1',
    'klevu_simple_synctest_child_instock_2',
    'klevu_simple_synctest_child_oos',
    'klevu_configurable_synctest_instock_disabled_cinstock',
    'klevu_configurable_synctest_instock_disabled_childrenoos',
    'klevu_configurable_synctest_oos_disabled_cinstock',
    'klevu_configurable_synctest_instock_notvisible_disabled_cinstock',
    'klevu_configurable_synctest_instock_vissearch_disabled_cinstock',
    'klevu_configurable_synctest_instock_viscatalog_disabled_cinstock',
];

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$storeManager->setCurrentStore(null);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

foreach ($skusToDelete as $skuToDelete) {
    try {
        $product = $productRepository->get($skuToDelete);
        $productRepository->delete($product);
    } catch (NoSuchEntityException $e) {
        // This is fine
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

require __DIR__ . '/productAttributeFixtures_rollback.php';
