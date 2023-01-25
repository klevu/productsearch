<?php

namespace Klevu\Search\Test\Integration\Observer\Sync\Product;

use Klevu\Search\Observer\Sync\Product\SyncRecordsAfter;
use Magento\Framework\Event\ConfigInterface as EventConfig;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class SyncRecordsAfterObserverConfigTest extends TestCase
{
    const EVENT_NAME_ADD = 'klevu_api_send_add_records_after';
    const EVENT_NAME_DELETE = 'klevu_api_send_delete_records_after';
    const EVENT_NAME_UPDATE = 'klevu_api_send_update_records_after';
    const OBSERVER_NAME_ADD = 'klevu_search_api_send_addrecords_after';
    const OBSERVER_NAME_DELETE = 'klevu_search_api_send_deleterecords_after';
    const OBSERVER_NAME_UPDATE = 'klevu_search_api_send_updaterecords_after';

    /**
     * @magentoAppArea adminhtml
     * @dataProvider eventDataProvider
     */
    public function testObserversRecordsAfterConfigured($event, $observer)
    {
        /** @var EventConfig $observerConfig */
        $observerConfig = ObjectManager::getInstance()->create(EventConfig::class);
        $observers = $observerConfig->getObservers($event);
        $this->assertArrayHasKey($observer, $observers);
        $this->assertSame(
            ltrim(SyncRecordsAfter::class, '\\'),
            $observers[$observer]['instance']
        );
    }

    /**
     * @return \string[][]
     */
    public function eventDataProvider()
    {
        return [
            [self::EVENT_NAME_ADD, self::OBSERVER_NAME_ADD],
            [self::EVENT_NAME_DELETE, self::OBSERVER_NAME_DELETE],
            [self::EVENT_NAME_UPDATE, self::OBSERVER_NAME_UPDATE]
        ];
    }
}
