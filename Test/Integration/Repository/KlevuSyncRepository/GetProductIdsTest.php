<?php

namespace Klevu\Search\Test\Integration\Repository\KlevuSyncRepository;

use Klevu\Search\Api\KlevuSyncRepositoryInterface;
use Klevu\Search\Model\Klevu\Klevu;
use Klevu\Search\Model\Klevu\Klevu as KlevuSync;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuSyncResourceModel;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation disabled
 */
class GetProductIdsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetProductIdsReturnsArray()
    {
        $this->setupPhp5();
        $count = 10;

        for ($i = 0; $i < $count; $i++) {
            $this->createKlevuSyncEntity();
        }
        $store = $this->getStore();
        $klevuRepository = $this->instantiateKlevuRepository();
        $productIds = $klevuRepository->getProductIds($store);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($productIds);
        } else {
            $this->assertTrue(is_array($productIds), 'Is Array');
        }
        $this->assertCount($count, $productIds);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testGetMaxSyncId()
    {
        $this->setupPhp5();
        for ($i = 0; $i < 10; $i++) {
            $this->createKlevuSyncEntity();
        }
        $store = $this->getStore();
        $klevuRepository = $this->instantiateKlevuRepository();
        $allProducts = $klevuRepository->getProductIds($store);
        $maxSyncId = max(array_column($allProducts, Klevu::FIELD_ENTITY_ID));

        $this->assertSame(
            (int)$maxSyncId,
            $klevuRepository->getMaxSyncId($store)
        );

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return KlevuSyncRepositoryInterface
     */
    private function instantiateKlevuRepository()
    {
        return $this->objectManager->create(KlevuSyncRepositoryInterface::class);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStore($storeCode = 'klevu_test_store_1')
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @return KlevuSync
     * @throws AlreadyExistsException
     */
    private function createKlevuSyncEntity()
    {
        $store = $this->getStore();
        $storeId = $store->getId();

        $sync = $this->instantiateSync();
        $sync->setProductId(mt_rand(1, 4999999));
        $sync->setParentId(mt_rand(5000000, 9999999));
        $sync->setType(KlevuSync::OBJECT_TYPE_PRODUCT);
        $sync->setStoreId($storeId);
        $sync->setErrorFlag(0);

        $this->instantiateSyncResourceModel()->save($sync);

        return $sync;
    }

    /**
     * @return KlevuSync
     */
    private function instantiateSync()
    {
        return $this->objectManager->create(KlevuSync::class);
    }

    /**
     * @return KlevuSyncResourceModel
     */
    private function instantiateSyncResourceModel()
    {
        return $this->objectManager->create(KlevuSyncResourceModel::class);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }
}

