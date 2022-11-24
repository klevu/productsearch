<?php
/** @noinspection PhpDeprecationInspection */
/** @noinspection PhpUnhandledExceptionInspection */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$skusToDelete = [
    'klevu-category-paths-test-parent-no-categories',
    'klevu-category-paths-test-parent-with-categories',
    'klevu-category-paths-test-child-no-categories',
    'klevu-category-paths-test-child-with-categories',
    'klevu-category-paths-test-standalone',
];

$objectManager = Bootstrap::getObjectManager();

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$storeManager->setCurrentStore(null);

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var ProductCollection $productCollection */
$productCollection = $objectManager->create(ProductCollection::class);
$productCollection->addAttributeToFilter('sku', ['in' => $skusToDelete]);
$productCollection->setFlag('has_stock_status_filter', true);
$productCollection->load();
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
foreach ($productCollection as $product) {
    /** @var ProductRepositoryInterface $productRepository */
    $productRepository->delete($product);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

require __DIR__ . '/productAttributeFixtures_rollback.php';
