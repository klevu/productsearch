<?php

use Magento\Framework\Registry;
use Magento\Review\Model\ResourceModel\Review\Collection as ReviewCollection;
use Magento\Review\Model\Review;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$review = $objectManager->get(Review::class);

$reviewsToDeleteCollection = $objectManager->get(ReviewCollection::class);
$reviewsToDeleteCollection->addFieldToFilter('entity_id', $review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE));

foreach ($reviewsToDeleteCollection->getItems() as $reviewToDelete) {
    if ($reviewToDelete->getId()) {
        $reviewToDelete->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
