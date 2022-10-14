<?php

namespace Klevu\Search\Test\Integration\Service\Catalog\Product\Review;

use Klevu\Search\Api\Service\Catalog\Product\Review\ConvertRatingToStarsInterface;
use Klevu\Search\Service\Catalog\Product\Review\ConvertRatingToStars;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ConvertRatingToStarServiceTest extends TestCase
{
    public function testCanBeInstantiated()
    {
        $objectManager = ObjectManager::getInstance();
        $getRatingsCountService = $objectManager->get(ConvertRatingToStars::class);

        $this->assertInstanceOf(ConvertRatingToStarsInterface::class, $getRatingsCountService);
    }
}
