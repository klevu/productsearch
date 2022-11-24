<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$skusToDelete = [
    'klevu_simple_reviewtest_pendingtoapproved',
    'klevu_simple_reviewtest_disapprovedtoapproved',
    'klevu_simple_reviewtest_approvedtopending',
    'klevu_simple_reviewtest_disapprovedtopending',
    'klevu_simple_reviewtest_approvedtodisapproved',
    'klevu_simple_reviewtest_pendingtodisapproved',
    'klevu_simple_reviewtest_approvedwithrating',
    'klevu_simple_reviewtest_approvedwithoutrating',
    'klevu_simple_reviewtest_pendingwithrating',
    'klevu_simple_reviewtest_pendingwithoutrating',
    'klevu_simple_reviewtest_disapprovedwithrating',
    'klevu_simple_reviewtest_disapprovedwithoutrating',
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

foreach ($skusToDelete as $sku) {
    try {
        $productRepository->deleteById($sku);
    } catch (NoSuchEntityException $e) {
        // This is fine
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
