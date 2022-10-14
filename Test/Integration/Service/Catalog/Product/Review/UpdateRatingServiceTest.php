<?php

namespace Klevu\Search\Test\Integration\Service\Catalog\Product\Reivew;

use Klevu\Search\Api\Service\Catalog\Product\Review\UpdateRatingInterface;
use Klevu\Search\Service\Catalog\Product\Review\UpdateRating;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class UpdateRatingServiceTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testUpdateRatingImplementsUpdateRatingInterface()
    {
        $this->setUpPhp5();

        $updateRating = $this->instantiateUpdateRatingService();

        $this->assertInstanceOf(UpdateRatingInterface::class, $updateRating);
    }

    /**
     * @return UpdateRating
     */
    private function instantiateUpdateRatingService()
    {
        return $this->objectManager->create(UpdateRating::class);
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }
}
