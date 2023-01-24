<?php

use Klevu\Search\Model\Klevu\KlevuFactory as KlevuModelFactory;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\CollectionFactory as KlevuModelCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var KlevuModelCollectionFactory $klevuSyncModelCollectionFactory */
$klevuSyncModelCollectionFactory = $objectManager->get(KlevuModelCollectionFactory::class);
/** @var KlevuModelFactory $klevuSyncModelFactory */
$klevuSyncModelFactory = $objectManager->get(KlevuModelFactory::class);

// -------------------------------------------------------------------------

$fixtures = [];
$storeCodes = [
    'klevu_test_store_1',
    'klevu_test_store_2',
];

$productSkus = [
    [
        'sku' => 'klevu_bundle_product_test',
        'children' => [
            'klevu_bundle_product_test_simple'
        ]
    ]
];

foreach ($productSkus as $productSku) {
    $product = $productRepository->get($productSku['sku']);
    foreach ($storeCodes as $storeCode) {
        $store = $storeManager->getStore($storeCode);

        $fixtures[] = [
            'product_id' => $product->getId(),
            'parent_id' => 0,
            'store_id' => $store->getId(),
            'last_synced_at' => date('Y-m-d H:i:s', time() - 86400),
            'type' => 'products',
            'error_flag' => 0
        ];

        foreach ($productSku['children'] as $child) {
            $childProduct = $productRepository->get($child);
            $fixtures[] = [
                'product_id' => $childProduct->getId(),
                'parent_id' => $product->getid(),
                'store_id' => $store->getId(),
                'last_synced_at' => date('Y-m-d H:i:s', time() - 86400),
                'type' => 'products',
                'error_flag' => 0
            ];
        }
    }
}


// -------------------------------------------------------------------------
foreach ($fixtures as $fixture) {
    $collection = $klevuSyncModelCollectionFactory->create();
    $collection->addFieldToFilter('product_id', $fixture['product_id']);
    $collection->addFieldToFilter('store_id', $fixture['store_id']);
    $collection->addFieldToFilter('type', $fixture['type']);

    $klevuSyncModel = $collection->getFirstItem();
    if (!$klevuSyncModel) {
        $klevuSyncModel = $klevuSyncModelFactory->create();
    }

    $klevuSyncModel->addData($fixture);
    $klevuSyncModel->save();
}

// -------------------------------------------------------------------------

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
