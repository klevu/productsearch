<?php

namespace Klevu\Search\Test\Integration\Provider\Catalog\Product\Review;

use Klevu\Search\Api\Provider\Catalog\Product\Review\ReviewCountDataProviderInterface;
use Klevu\Search\Provider\Catalog\Product\Review\MagentoReviewCountDataProvider;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class MagentoReviewCountDataProviderTest extends TestCase
{
    public function testCanBeInstantiated()
    {
        $objectManager = ObjectManager::getInstance();
        $getRatingsCountService = $objectManager->get(MagentoReviewCountDataProvider::class);

        $this->assertInstanceOf(ReviewCountDataProviderInterface::class, $getRatingsCountService);
    }
}
