<?php

use Magento\Framework\Registry;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\Review;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$review = $objectManager->get(Review::class);

/** @var ReviewCollectionFactory $reviewsToDeleteCollectionFactory */
$reviewsToDeleteCollectionFactory = $objectManager->get(ReviewCollectionFactory::class);
$reviewsToDeleteCollection = $reviewsToDeleteCollectionFactory->create();
$reviewsToDeleteCollection->addFieldToFilter('nickname', 'Integration Test Fixture');

foreach ($reviewsToDeleteCollection->getItems() as $reviewToDelete) {
    if ($reviewToDelete->getId()) {
        $reviewToDelete->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
