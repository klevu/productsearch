<?php

use Magento\Framework\Registry;
use Magento\Review\Model\ResourceModel\Rating\Option\Vote\Collection as RatingVoteCollection;
use Magento\Review\Model\Review;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$review = $objectManager->get(Review::class);

$ratingsToDeleteCollection = $objectManager->get(RatingVoteCollection::class);

foreach ($ratingsToDeleteCollection->getItems() as $ratingToDelete) {
    if ($ratingToDelete->getId()) {
        $ratingToDelete->delete();
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
