<?php

namespace Klevu\Search\Test\Integration\Service\Sync\Product;

use Klevu\Search\Api\Service\Sync\Product\GetNextActionsInterface;
use Klevu\Search\Model\Source\NextAction;
use Klevu\Search\Service\Sync\Product\GetNextActions;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetNextActionsTest extends TestCase
{
    /**
     * @var  ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $getNextActionsService = $this->instantiateGetNextActionsService();

        $this->assertInstanceOf(GetNextActions::class, $getNextActionsService);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testReturnsArray()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $productIds = [];

        $getNextActionsService = $this->instantiateGetNextActionsService();
        $result = $getNextActionsService->execute($store, $productIds);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result);
        } else {
            $this->assertTrue(is_array($result), 'Is Array');
        }

        $this->assertArrayHasKey(NextAction::ACTION_ADD, $result);
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result[NextAction::ACTION_ADD]);
        } else {
            $this->assertTrue(is_array($result[NextAction::ACTION_ADD]), 'Is Array');
        }

        $this->assertArrayHasKey(NextAction::ACTION_DELETE, $result);
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result[NextAction::ACTION_DELETE]);
        } else {
            $this->assertTrue(is_array($result[NextAction::ACTION_DELETE]), 'Is Array');
        }

        $this->assertArrayHasKey(NextAction::ACTION_UPDATE, $result);
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result[NextAction::ACTION_UPDATE]);
        } else {
            $this->assertTrue(is_array($result[NextAction::ACTION_UPDATE]), 'Is Array');
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @return GetNextActionsInterface
     */
    private function instantiateGetNextActionsService()
    {
        return $this->objectManager->get(GetNextActionsInterface::class);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode)
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }
}
