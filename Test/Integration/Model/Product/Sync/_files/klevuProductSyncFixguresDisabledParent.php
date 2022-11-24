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
    // Configurable
    [
        'parent' => 'klevu_configurable_synctest_instock_disabled_cinstock',
        'product' => 'klevu_simple_synctest_child_instock_1',
    ], [
        'parent' => 'klevu_configurable_synctest_instock_disabled_cinstock',
        'product' => 'klevu_simple_synctest_child_oos',
    ],
    [
        'parent' => 'klevu_configurable_synctest_instock_disabled_childrenoos',
        'product' => 'klevu_simple_synctest_child_oos',
    ],
    [
        'parent' => 'klevu_configurable_synctest_oos_disabled_cinstock',
        'product' => 'klevu_simple_synctest_child_instock_1',
    ],
    [
        'parent' => 'klevu_configurable_synctest_instock_notvisible_disabled_cinstock',
        'product' => 'klevu_simple_synctest_child_instock_1',
    ],
    [
        'parent' => 'klevu_configurable_synctest_instock_vissearch_disabled_cinstock',
        'product' => 'klevu_simple_synctest_child_instock_1',
    ],
    [
        'parent' => 'klevu_configurable_synctest_instock_viscatalog_disabled_cinstock',
        'product' => 'klevu_simple_synctest_child_instock_1',
    ],
];

$store = $storeManager->getStore('klevu_test_store_1');
foreach ($fixtureSkus as $fixtureSkuPair) {
    try {
        $product = $productRepository->get($fixtureSkuPair['product']);
    } catch (\Exception $e) {
        throw new \LogicException(sprintf(
            'Could not get product for fixture. %s %s',
            $e->getMessage(),
            json_encode($fixtureSkuPair)
        ));
    }
    try {
        $parentProduct = isset($fixtureSkuPair['parent'])
            ? $productRepository->get($fixtureSkuPair['parent'])
            : null;
    } catch (\Exception $e) {
        throw new \LogicException(sprintf(
            'Could not get parent for fixture. %s %s',
            $e->getMessage(),
            json_encode($fixtureSkuPair)
        ));
    }

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
