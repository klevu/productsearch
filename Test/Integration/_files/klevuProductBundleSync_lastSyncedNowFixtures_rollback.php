<?php

use Klevu\Search\Model\Klevu\Klevu as KlevuSyncModel;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\CollectionFactory as KlevuModelCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var KlevuModelCollectionFactory $klevuSyncModelCollectionFactory */
$klevuSyncModelCollectionFactory = $objectManager->get(KlevuModelCollectionFactory::class);

// -------------------------------------------------------------------------

$productSkusToDelete = [
    'klevu_bundle_product_test',
    'klevu_bundle_product_test_simple'
];

// -------------------------------------------------------------------------

foreach ($productSkusToDelete as $productSku) {
    try {
        $product = $productRepository->get($productSku);
    } catch (NoSuchEntityException $e) {
        continue;
    }

    $collection = $klevuSyncModelCollectionFactory->create();
    $collection->addFieldToFilter('product_id', $product->getId());
    $collection->addFieldToFilter('type', 'products');

    $collection->load();
    foreach ($collection as $klevuSyncEntity) {
        /** @var KlevuSyncModel $klevuSyncEntity */
        $klevuSyncEntity->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
