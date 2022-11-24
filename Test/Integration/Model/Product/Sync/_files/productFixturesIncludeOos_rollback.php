<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$skusToDelete = [
    'klevu_simple_synctest_instock_visible',
    'klevu_simple_synctest_instock_notvisible',
    'klevu_simple_synctest_instock_vissearch',
    'klevu_simple_synctest_instock_viscatalog',
    'klevu_simple_synctest_oos_visible',
    'klevu_simple_synctest_oos_notvisible',
    'klevu_simple_synctest_child_instock_1',
    'klevu_simple_synctest_child_instock_2',
    'klevu_simple_synctest_child_oos',
    'klevu_configurable_synctest_instock_childreninstock',
    'klevu_configurable_synctest_instock_childrenoos',
    'klevu_configurable_synctest_oos_childreninstock',
    'klevu_configurable_synctest_instock_notvisible_childreninstock',
    'klevu_configurable_synctest_instock_vissearch_childreninstock',
    'klevu_configurable_synctest_instock_viscatalog_childreninstock',
    'klevu_simple_synctest_groupchild_instock_1',
    'klevu_simple_synctest_groupchild_instock_2',
    'klevu_simple_synctest_groupchild_oos',
    'klevu_grouped_synctest_instock_childreninstock',
    'klevu_grouped_synctest_instock_notvisible_childreninstock',
    'klevu_grouped_synctest_instock_childrenoos',
    'klevu_grouped_synctest_oos_childreninstock',
    'klevu_simple_synctest_bundlechild_instock_1',
    'klevu_simple_synctest_bundlechild_instock_2',
    'klevu_simple_synctest_bundlechild_oos',
    'klevu_bundle_synctest_instock_childreninstock',
    'klevu_bundle_synctest_instock_childreninstock_fixedprice',
    'klevu_bundle_synctest_instock_notvisible_childreninstock',
    'klevu_bundle_synctest_instock_childrenoos',
    'klevu_bundle_synctest_oos_childreninstock',
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
