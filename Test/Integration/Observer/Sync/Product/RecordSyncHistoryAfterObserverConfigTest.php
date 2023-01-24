<?php

namespace Klevu\Search\Test\Integration\Observer\Sync\Product;

use Klevu\Search\Observer\Sync\Product\RecordSyncHistoryAfter;
use Magento\Framework\Event\ConfigInterface as EventConfig;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class RecordSyncHistoryAfterObserverConfigTest extends TestCase
{
    const EVENT_NAME = 'klevu_record_sync_history_after';
    const OBSERVER_NAME = 'klevu_record_sync_history_after';

    /**
     * @magentoAppArea adminhtml
     */
    public function testSyncRecordsAfterConfigured()
    {
        /** @var EventConfig $observerConfig */
        $observerConfig = ObjectManager::getInstance()->create(EventConfig::class);
        $observers = $observerConfig->getObservers(self::EVENT_NAME);
        $this->assertArrayHasKey(self::OBSERVER_NAME, $observers);
        $this->assertSame(
            ltrim(RecordSyncHistoryAfter::class, '\\'),
            $observers[self::OBSERVER_NAME]['instance']
        );
    }
}
