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
    'klevu_simple_synctest_instock_visible',
    'klevu_simple_synctest_instock_notvisible',
    'klevu_simple_synctest_instock_vissearch',
    'klevu_simple_synctest_instock_viscatalog',
    'klevu_simple_synctest_oos_visible',
    'klevu_simple_synctest_oos_notvisible',
    'klevu_simple_synctest_child_instock_1',
    'klevu_simple_synctest_child_instock_2',
    'klevu_simple_synctest_child_oos',
    'klevu_configurable_synctest_instock_childreninstock',
    'klevu_configurable_synctest_instock_childrenoos',
    'klevu_configurable_synctest_oos_childreninstock',
    'klevu_configurable_synctest_instock_notvisible_childreninstock',
    'klevu_configurable_synctest_instock_vissearch_childreninstock',
    'klevu_configurable_synctest_instock_viscatalog_childreninstock',
    'klevu_simple_synctest_groupchild_instock_1',
    'klevu_simple_synctest_groupchild_instock_2',
    'klevu_simple_synctest_groupchild_oos',
    'klevu_grouped_synctest_instock_childreninstock',
    'klevu_grouped_synctest_instock_notvisible_childreninstock',
    'klevu_grouped_synctest_instock_childrenoos',
    'klevu_grouped_synctest_oos_childreninstock',
    'klevu_simple_synctest_bundlechild_instock_1',
    'klevu_simple_synctest_bundlechild_instock_2',
    'klevu_simple_synctest_bundlechild_oos',
    'klevu_bundle_synctest_instock_childreninstock',
    'klevu_bundle_synctest_instock_childreninstock_fixedprice',
    'klevu_bundle_synctest_instock_notvisible_childreninstock',
    'klevu_bundle_synctest_instock_childrenoos',
    'klevu_bundle_synctest_oos_childreninstock',
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
