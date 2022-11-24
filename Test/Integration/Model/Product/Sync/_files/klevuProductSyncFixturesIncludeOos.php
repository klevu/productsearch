<?php

use Klevu\Search\Model\Klevu\KlevuFactory as KlevuModelFactory;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\CollectionFactory as KlevuModelCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

include __DIR__ . '/klevuProductSyncFixturesIncludeOos_rollback.php';

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

$fixtureSkus = [
    // Simple
    [
        'parent' => null,
        'product' => 'klevu_simple_synctest_instock_visible',
    ], [
        'parent' => null,
        'product' => 'klevu_simple_synctest_instock_notvisible',
    ], [
        'parent' => null,
        'product' => 'klevu_simple_synctest_instock_vissearch',
    ], [
        'parent' => null,
        'product' => 'klevu_simple_synctest_instock_viscatalog',
    ], [
        'parent' => null,
        'product' => 'klevu_simple_synctest_oos_visible',
    ], [
        'parent' => null,
        'product' => 'klevu_simple_synctest_oos_notvisible',
    ],
    // Configurable
    [
        'parent' => 'klevu_configurable_synctest_instock_childreninstock',
        'product' => 'klevu_simple_synctest_instock_visible',
    ], [
        'parent' => 'klevu_configurable_synctest_instock_childreninstock',
        'product' => 'klevu_simple_synctest_child_instock_1',
    ], [
        'parent' => 'klevu_configurable_synctest_instock_childreninstock',
        'product' => 'klevu_simple_synctest_child_oos',
    ], [
        'parent' => 'klevu_configurable_synctest_instock_childreninstock',
        'product' => 'klevu_simple_synctest_child_instock_2',
    ],
    [
        'parent' => 'klevu_configurable_synctest_instock_childrenoos',
        'product' => 'klevu_simple_synctest_child_oos',
    ],
    [
        'parent' => 'klevu_configurable_synctest_oos_childreninstock',
        'product' => 'klevu_simple_synctest_child_instock_1',
    ], [
        'parent' => 'klevu_configurable_synctest_oos_childreninstock',
        'product' => 'klevu_simple_synctest_child_instock_2',
    ],
    [
        'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
        'product' => 'klevu_simple_synctest_child_instock_1',
    ], [
        'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
        'product' => 'klevu_simple_synctest_child_instock_2',
    ],
    [
        'parent' => 'klevu_configurable_synctest_instock_vissearch_childreninstock',
        'product' => 'klevu_simple_synctest_child_instock_1',
    ], [
        'parent' => 'klevu_configurable_synctest_instock_vissearch_childreninstock',
        'product' => 'klevu_simple_synctest_child_instock_2',
    ],
    [
        'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
        'product' => 'klevu_simple_synctest_child_instock_1',
    ], [
        'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
        'product' => 'klevu_simple_synctest_child_instock_2',
    ],
    // Grouped
    [
        'parent' => null,
        'product' => 'klevu_grouped_synctest_instock_childreninstock',
    ], [
        'parent' => null,
        'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
    ], [
        'parent' => null,
        'product' => 'klevu_grouped_synctest_instock_childrenoos',
    ], [
        'parent' => null,
        'product' => 'klevu_grouped_synctest_oos_childreninstock',
    ],
    // Bundle
    [
        'parent' => null,
        'product' => 'klevu_bundle_synctest_instock_childreninstock',
    ], [
        'parent' => null,
        'product' => 'klevu_bundle_synctest_instock_childreninstock_fixedprice',
    ], [
        'parent' => null,
        'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
    ], [
        'parent' => null,
        'product' => 'klevu_bundle_synctest_instock_childrenoos',
    ], [
        'parent' => null,
        'product' => 'klevu_bundle_synctest_oos_childreninstock',
    ],
];

$store = $storeManager->getStore('klevu_test_store_1');
foreach ($fixtureSkus as $fixtureSkuPair) {
    $product = $productRepository->get($fixtureSkuPair['product']);
    $parentProduct = isset($fixtureSkuPair['parent'])
        ? $productRepository->get($fixtureSkuPair['parent'])
        : null;

    $fixtures[] = [
        'product_id' => $product->getId(),
        'parent_id' => $parentProduct ? $parentProduct->getId() : 0,
        'store_id' => $store->getId(),
        'last_synced_at' => date('Y-m-d H:i:s', time() - 86400),
        'type' => 'products',
        'error_flag' => 0,
    ];
}

// -------------------------------------------------------------------------
foreach ($fixtures as $fixture) {
    $collection = $klevuSyncModelCollectionFactory->create();
    $collection->addFieldToFilter('product_id', $fixture['product_id']);
    $collection->addFieldToFilter('parent_id', $fixture['parent_id']);
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
