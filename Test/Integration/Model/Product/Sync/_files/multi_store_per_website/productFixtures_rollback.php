<?php
/** @noinspection PhpDeprecationInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;

$skusToDelete = [
    'klevu_simple_1'
];

$objectManager = Bootstrap::getObjectManager();

/** @var Website $website1 */
$website1 = $objectManager->create(Website::class);
$website1->load('klevu_test_website_1', 'code');

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$storeManager->setCurrentStore(null);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$stores = $website1->getStores();
foreach ($stores as $store) {
    foreach ($skusToDelete as $skuToDelete) {
        try {
            $product = $productRepository->get($skuToDelete, false, $store->getId());
            $productRepository->delete($product);
        } catch (\Exception $e) {
            // This is fine
        }
    }
}
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
