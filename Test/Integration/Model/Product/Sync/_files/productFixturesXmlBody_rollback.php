<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$skusToDelete = [
    'klevu_simple_synctest_xmlbody',
    'klevu_virtual_synctest_xmlbody',
    'klevu_downloadable_synctest_xmlbody',
    'klevu_configchild_synctest_xmlbody',
    'klevu_config_synctest_xmlbody',
    'klevu_groupchild_synctest_xmlbody',
    'klevu_grouped_synctest_xmlbody',
    'klevu_bundlechild_synctest_xmlbody',
    'klevu_bundle_synctest_xmlbody',
    'klevu_giftcard_synctest_xmlbody',
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
