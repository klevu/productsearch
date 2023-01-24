<?php

use Klevu\Search\Api\KlevuProductSyncHistoryRepositoryInterface as SyncHistoryRepositoryInterface;
use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Model\Product\Sync\HistoryFactory as SyncHistoryFactory;
use Klevu\Search\Model\Source\NextAction;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

include __DIR__ . '/syncHistoryFixtures_rollback.php';

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var SyncHistoryFactory $klevuSyncHistoryFactory */
$klevuSyncHistoryFactory = $objectManager->get(SyncHistoryFactory::class);
/** @var SyncHistoryRepositoryInterface $syncHistoryRepository */
$syncHistoryRepository = $objectManager->get(SyncHistoryRepositoryInterface::class);

// -------------------------------------------------------------------------

$fixtures = [];

$productSkus = [
    'klevu_bundle_product_test',
];
$storeCodes = [
    'klevu_test_store_1',
    'klevu_test_store_2',
];
foreach ($productSkus as $productSku) {
    $product = $productRepository->get($productSku);
    foreach ($storeCodes as $storeCode) {
        $store = $storeManager->getStore($storeCode);

        $fixtures[] = [
            History::FIELD_PRODUCT_ID => $product->getId(),
            History::FIELD_PARENT_ID => 0,
            History::FIELD_STORE_ID => $store->getId(),
            History::FIELD_ACTION => NextAction::ACTION_VALUE_UPDATE,
            History::FIELD_SUCCESS => true,
            History::FIELD_MESSAGE => "APi Call Was A Success",
        ];
    }
}

// -------------------------------------------------------------------------
for ($i = 0; $i < 5; $i++) { // create 5 history items
    foreach ($fixtures as $fixture) {
        $klevuSyncHistoryModel = $klevuSyncHistoryFactory->create();
        $klevuSyncHistoryModel->addData($fixture);
        $syncHistoryRepository->save($klevuSyncHistoryModel);
    }
}
// -------------------------------------------------------------------------

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
