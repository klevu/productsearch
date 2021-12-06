<?php

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;
use Magento\UrlRewrite\Model\UrlRewrite;

$objectManager = Bootstrap::getObjectManager();
/** @var CategoryRepositoryInterface $categoryRepository */
$categoryRepository = $objectManager->get(CategoryRepositoryInterface::class);
$urlRewriteCollectionFactory = $objectManager->get(UrlRewriteCollectionFactory::class);

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$collection = $objectManager->create(CategoryCollection::class);
$collection->addAttributeToFilter('name', ['like' => '%[Klevu][Product Test %']);
foreach ($collection as $category) {
    $categoryRepository->delete($category);
}

/** @var UrlRewriteCollection $urlRewriteCollection */
$urlRewriteCollection = $urlRewriteCollectionFactory->create();
$urlRewriteCollection->addFieldToFilter('entity_type', 'category');
$urlRewriteCollection->addFieldToFilter('request_path', ['like' => 'klevu-product-test-%']);
foreach ($urlRewriteCollection as $urlRewrite) {
    /** @var UrlRewrite $urlRewrite */
    $urlRewrite->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
