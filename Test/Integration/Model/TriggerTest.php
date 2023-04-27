<?php

namespace Klevu\Search\Test\Integration\Model;

use Klevu\Search\Model\Trigger as TriggerModel;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * "Unit" tests for Klevu\Search\Model\Trigger
 * Lives within Integration tests to ensure application is bootstrap during tests
 */
class TriggerTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * Tests that activateTrigger method creates expected database triggers
     *  when they do not already exist
     *
     * @magentoDbIsolation disabled
     */
    public function testActivateTriggerNotExists()
    {
        $this->setupPhp5();

        $fixtures = [
            'update_klevuproductsync_for_cpip',
            'update_klevuproductsync_for_lsa',
            'update_klevuproductsync_for_cpp',
        ];
        $this->removeTriggers($fixtures);

        /** @var TriggerModel $triggerModel */
        $triggerModel = $this->objectManager->get(TriggerModel::class);
        $triggerModel->activateTrigger();

        $existingTriggerNames = $this->getExistingTriggerNames();
        foreach ($fixtures as $fixtureTriggerName) {
            $this->assertFalse(in_array($fixtureTriggerName, $existingTriggerNames, true));
        }
    }

    /**
     * Tests that activateTrigger method retains expected database triggers
     *  when they already exist
     *
     * @magentoDbIsolation disabled
     */
    public function testActivateTriggerExists()
    {
        $this->setupPhp5();

        $fixtures = [
            'update_klevuproductsync_for_cpip',
            'update_klevuproductsync_for_lsa',
            'update_klevuproductsync_for_cpp',
        ];
        $this->createTriggers($fixtures);

        /** @var TriggerModel $triggerModel */
        $triggerModel = $this->objectManager->get(TriggerModel::class);
        $triggerModel->activateTrigger();

        $existingTriggerNames = $this->getExistingTriggerNames();
        foreach ($fixtures as $fixtureTriggerName) {
            $this->assertFalse(in_array($fixtureTriggerName, $existingTriggerNames, true));
        }
    }

    /**
     * Test that dropTriggerIfFoundExist does not create expected triggers
     *  when they do not already exist
     *
     * @magentoDbIsolation disabled
     */
    public function testDropTriggerIfFoundExistNotExists()
    {
        $this->setupPhp5();

        $fixtures = [
            'update_klevuproductsync_for_cpip',
            'update_klevuproductsync_for_lsa',
            'update_klevuproductsync_for_cpp',
        ];
        $this->removeTriggers($fixtures);

        /** @var TriggerModel $triggerModel */
        $triggerModel = $this->objectManager->get(TriggerModel::class);
        $triggerModel->dropTriggerIfFoundExist();

        $existingTriggerNames = $this->getExistingTriggerNames();
        foreach ($fixtures as $fixtureTriggerName) {
            $this->assertFalse(in_array($fixtureTriggerName, $existingTriggerNames, true));
        }
    }

    /**
     * Tests that dropTriggerIfFoundExist drops expected triggers
     *  when they do not already exist
     *
     * @magentoDbIsolation disabled
     */
    public function testDropTriggerIfFoundExistExists()
    {
        $this->setupPhp5();

        $fixtures = [
            'update_klevuproductsync_for_cpip',
            'update_klevuproductsync_for_lsa',
            'update_klevuproductsync_for_cpp',
        ];
        $this->createTriggers($fixtures);

        /** @var TriggerModel $triggerModel */
        $triggerModel = $this->objectManager->get(TriggerModel::class);
        $triggerModel->dropTriggerIfFoundExist();

        $existingTriggerNames = $this->getExistingTriggerNames();
        foreach ($fixtures as $fixtureTriggerName) {
            $this->assertFalse(in_array($fixtureTriggerName, $existingTriggerNames, true));
        }
    }

    /**
     * Alternative setup method to accommodate lack of return type casting in PHP5.6,
     *  given setUp() requires a void return type
     *
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->resourceConnection = $this->objectManager->get(ResourceConnection::class);
        $this->connection = $this->resourceConnection->getConnection();
    }

    /**
     * Creates dummy triggers with given names via direct SQL
     * Used to set initial test conditions
     *
     * @param string[] $triggerNamesToCreate
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function createTriggers(array $triggerNamesToCreate)
    {
        $existingTriggerNames = $this->getExistingTriggerNames();
        $missingTriggerNames = array_diff($triggerNamesToCreate, $existingTriggerNames);

        foreach ($missingTriggerNames as $missingTriggerName) {
            $this->connection->query(sprintf(
                'CREATE TRIGGER %s BEFORE UPDATE ON %s FOR EACH ROW SET NEW.send = NEW.send * 1',
                $missingTriggerName,
                $this->resourceConnection->getTableName('klevu_order_sync')
            ));
        }

        $existingTriggerNames = $this->getExistingTriggerNames();
        foreach ($triggerNamesToCreate as $triggerName) {
            $this->assertTrue(in_array($triggerName, $existingTriggerNames, true));
        }
    }

    /**
     * Removes existing database triggers with given names via direct SQL
     * Used to set initial test conditions
     *
     * @param string[] $triggerNamesToRemove
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function removeTriggers(array $triggerNamesToRemove)
    {
        foreach ($triggerNamesToRemove as $triggerName) {
            $this->connection->query(sprintf('DROP TRIGGER IF EXISTS %s', $triggerName));
        }

        $existingTriggerNames = $this->getExistingTriggerNames();
        foreach ($existingTriggerNames as $existingTriggerName) {
            $this->assertFalse(in_array($existingTriggerName, $triggerNamesToRemove, true));
        }
    }

    /**
     * Returns list of all existing database trigger names in database
     *
     * @return string[]
     * @throws \Zend_Db_Statement_Exception
     */
    private function getExistingTriggerNames()
    {
        $triggersResult = $this->connection->query('SHOW TRIGGERS');

        return array_column($triggersResult->fetchAll(), 'Trigger');
    }
}
