<?php

namespace Klevu\Search\Test\Integraton\Observer\Catalog\Product;

use Klevu\Search\Observer\Catalog\Product\EditAction;
use Magento\Framework\Event\ConfigInterface as EventConfig;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class EditActionObserverConfigTest extends TestCase
{
    const OBSERVER_NAME = 'KlevuSearch_ProductEditAction';

    /**
     * @magentoAppArea adminhtml
     */
    public function testRestApiKeyChangedIsConfigured()
    {
        /** @var EventConfig $observerConfig */
        $observerConfig = ObjectManager::getInstance()->create(EventConfig::class);
        $observers = $observerConfig->getObservers('catalog_product_edit_action');

        $this->assertArrayHasKey(self::OBSERVER_NAME, $observers);
        $this->assertSame(
            ltrim(EditAction::class, '\\'),
            $observers[self::OBSERVER_NAME]['instance']
        );
    }
}

