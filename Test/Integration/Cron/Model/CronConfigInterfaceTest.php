<?php

namespace Klevu\Search\Test\Integration\Cron\Model;

use Magento\Cron\Model\ConfigInterface as CronConfigInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class CronConfigInterfaceTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Feature: Order synchronisation schedule can be defined by administrator
     *
     * Scenario: Order sync frequency is set using a user-friendly source option
     *    Given: Config setting "Order Sync Frequency" is not set to "custom"
     *      and: Config Setting "Custom Order Sync Frequency" has a value
     *     When: Crontab jobs configuration is calculated by Magento
     *      then: The klevu_search_order_sync cron job will use the config_path for "Order Sync Frequency"
     *
     * @magentoAppArea crontab
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_frequency 0 2 * * *
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_frequency_custom 1 2 3 4 5
     */
    public function testOrderSyncFrequencyReturnsNotCustom()
    {
        $this->setupPhp5();

        /** @var CronConfigInterface $cronConfigModel */
        $cronConfigModel = $this->objectManager->get(CronConfigInterface::class);
        $actualResult = $cronConfigModel->getJobs();

        $this->assertIsArray($actualResult);
        $this->assertTrue(isset($actualResult['default']['klevu_search_order_sync']['config_path']));
        $this->assertSame(
            'klevu_search/product_sync/order_sync_frequency',
            $actualResult['default']['klevu_search_order_sync']['config_path']
        );
    }

    /**
     * Feature: Order synchronisation schedule can be defined by administrator
     *
     * Scenario: Order sync frequency is set using a user-friendly source option
     *    Given: Config setting "Order Sync Frequency" is set to "custom"
     *      and: Config Setting "Custom Order Sync Frequency" has a value
     *     When: Crontab jobs configuration is calculated by Magento
     *      then: The klevu_search_order_sync cron job will use the config_path for "Custom Order Sync Frequency"
     *
     * @magentoAppArea crontab
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_frequency custom
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_frequency_custom 1 2 3 4 5
     */
    public function testOrderSyncFrequencyReturnsCustom()
    {
        $this->setupPhp5();

        /** @var CronConfigInterface $cronConfigModel */
        $cronConfigModel = $this->objectManager->get(CronConfigInterface::class);
        $actualResult = $cronConfigModel->getJobs();

        $this->assertIsArray($actualResult);
        $this->assertTrue(isset($actualResult['default']['klevu_search_order_sync']['config_path']));
        $this->assertSame(
            'klevu_search/product_sync/order_sync_frequency_custom',
            $actualResult['default']['klevu_search_order_sync']['config_path']
        );
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }
}
