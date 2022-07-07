<?php

namespace Klevu\Search\Test\Integration\Model\Observer\Backend;

use Klevu\Search\Observer\Backend\SetCloudSearchV2UrlConfigValueObserver;
use Magento\Framework\Event\ConfigInterface as EventConfig;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class SetCloudSearchV2UrlConfigValueObserverConfigTest extends TestCase
{
    const OBSERVER_NAME = 'klevuSearchConfigSerCloudSearchV2UrlValue';

    /**
     * @magentoAppArea adminhtml
     */
    public function testRestApiKeyChangedIsConfigured()
    {
        /** @var EventConfig $observerConfig */
        $observerConfig = ObjectManager::getInstance()->create(EventConfig::class);
        $observers = $observerConfig->getObservers('admin_system_config_save');

        $this->assertArrayHasKey(self::OBSERVER_NAME, $observers);
        $this->assertSame(
            ltrim(SetCloudSearchV2UrlConfigValueObserver::class, '\\'),
            $observers[self::OBSERVER_NAME]['instance']
        );
    }
}
