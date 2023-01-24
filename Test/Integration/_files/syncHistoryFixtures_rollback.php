<?php

use Klevu\Search\Api\KlevuProductSyncHistoryRepositoryInterface as SyncHistoryRepositoryInterface;
use Klevu\Search\Model\Product\Sync\History as SyncHistory;
use Klevu\Search\Model\Product\Sync\ResourceModel\History\CollectionFactory as SyncHistoryCollectionFactory;
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
/** @var SyncHistoryCollectionFactory $klevuSyncHistoryCollectionFactory */
$klevuSyncHistoryCollectionFactory = $objectManager->get(SyncHistoryCollectionFactory::class);
/** @var SyncHistoryRepositoryInterface $syncHistoryRepository */
$syncHistoryRepository = $objectManager->get(SyncHistoryRepositoryInterface::class);

// -------------------------------------------------------------------------

$productSkusToDelete = [
    'klevu_simple_1',
];

// -------------------------------------------------------------------------

foreach ($productSkusToDelete as $productSku) {
    try {
        $product = $productRepository->get($productSku);
    } catch (NoSuchEntityException $e) {
        continue;
    }

    $collection = $klevuSyncHistoryCollectionFactory->create();
    $collection->addFieldToFilter('product_id', $product->getId());

    $collection->load();
    foreach ($collection as $klevuSyncHistory) {
        /** @var SyncHistory $klevuSyncHistory */
        $syncHistoryRepository->delete($klevuSyncHistory);
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
