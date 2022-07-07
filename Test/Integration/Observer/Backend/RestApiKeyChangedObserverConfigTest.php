<?php

namespace Klevu\Search\Test\Integration\Model\Observer\Backend;

use Klevu\Search\Observer\Backend\RestApiKeyChanged;
use Magento\Framework\Event\ConfigInterface as EventConfig;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class RestApiKeyChangedObserverConfigTest extends TestCase
{
    const OBSERVER_NAME = 'KlevuSearch_RestApiKeyChanged';

    /**
     * @magentoAppArea adminhtml
     */
    public function testRestApiKeyChangedIsConfigured()
    {
        /** @var EventConfig $observerConfig */
        $observerConfig = ObjectManager::getInstance()->create(EventConfig::class);
        $observers = $observerConfig->getObservers('admin_system_config_changed_section_klevu_search');

        $this->assertArrayHasKey(self::OBSERVER_NAME, $observers);
        $this->assertSame(
            ltrim(RestApiKeyChanged::class, '\\'),
            $observers[self::OBSERVER_NAME]['instance']
        );
    }
}
