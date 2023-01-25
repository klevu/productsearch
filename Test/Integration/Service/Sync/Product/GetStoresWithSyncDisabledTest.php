<?php

namespace Klevu\Search\Test\Integration\Service\Sync\Product;

use Klevu\Search\Api\Service\Sync\Product\GetStoresWithSyncDisabledInterface;
use Klevu\Search\Service\Sync\Product\GetStoresWithSyncDisabled;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetStoresWithSyncDisabledTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var mixed
     */
    private $storeManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $getStoresWithSyncDisabledService = $this->instantiateGetStoresWithSyncDisabledService();

        $this->assertInstanceOf(GetStoresWithSyncDisabled::class, $getStoresWithSyncDisabledService);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     */
    public function testReturnsEmptyArrayIfAllStoresEnabled()
    {
        $this->setUpPhp5();

        $getStoresWithSyncDisabledService = $this->instantiateGetStoresWithSyncDisabledService();
        $result = $getStoresWithSyncDisabledService->execute();

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result);
        } else {
            $this->assertTrue(is_array($result), 'Is Array');
        }
        $this->assertCount(0, $result);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 0
     * @magentoConfigFixture klevu_test_store_2_store klevu_search/product_sync/enabled 0
     */
    public function testReturnsArrayOfStoreNameAndCodeForDisabledStores()
    {
        $this->setUpPhp5();
        $store1 = $this->getStore('klevu_test_store_1');
        $store2 = $this->getStore('klevu_test_store_2');

        $getStoresWithSyncDisabledService = $this->instantiateGetStoresWithSyncDisabledService();
        $result = $getStoresWithSyncDisabledService->execute();

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result);
        } else {
            $this->assertTrue(is_array($result), 'Is Array');
        }
        $this->assertCount(2, $result);

        $expected = $store1->getName() . ' (' . $store1->getCode() . ')';
        $this->assertSame($expected, $result[$store1->getId()]);

        $expected = $store2->getName() . ' (' . $store2->getCode() . ')';
        $this->assertSame($expected, $result[$store2->getId()]);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
    }

    /**
     * @return GetStoresWithSyncDisabledInterface
     */
    private function instantiateGetStoresWithSyncDisabledService()
    {
        return $this->objectManager->create(GetStoresWithSyncDisabledInterface::class, [
            'scopeConfig' => $this->scopeConfig,
            'storeManager' =>  $this->storeManager
        ]);
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
