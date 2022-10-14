<?php

namespace Klevu\Search\Test\Integration\Model\Observer;

use Klevu\Search\Model\Observer\RatingsUpdate;
use Magento\Framework\Event\ConfigInterface as EventConfig;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class RatingsUpdateObserverConfigTest extends TestCase
{
    const OBSERVER_NAME_UPDATE = 'ratingsUpdate';
    const OBSERVER_NAME_DELETE = 'ratingsDelete';

    /**
     * @magentoAppArea global
     */
    public function testRatingsUpdateIsConfigured()
    {
        /** @var EventConfig $observerConfig */
        $observerConfig = ObjectManager::getInstance()->create(EventConfig::class);
        $observers = $observerConfig->getObservers('review_save_after');

        $this->assertArrayHasKey(self::OBSERVER_NAME_UPDATE, $observers);
        $this->assertSame(
            ltrim(RatingsUpdate::class, '\\'),
            $observers[self::OBSERVER_NAME_UPDATE]['instance']
        );
    }

    /**
     * @magentoAppArea global
     */
    public function testRatingsDeleteIsConfigured()
    {
        /** @var EventConfig $observerConfig */
        $observerConfig = ObjectManager::getInstance()->create(EventConfig::class);
        $observers = $observerConfig->getObservers('review_delete_after');

        $this->assertArrayHasKey(self::OBSERVER_NAME_DELETE, $observers);
        $this->assertSame(
            ltrim(RatingsUpdate::class, '\\'),
            $observers[self::OBSERVER_NAME_DELETE]['instance']
        );
    }
}
