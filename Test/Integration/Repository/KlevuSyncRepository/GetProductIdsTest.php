<?php

namespace Klevu\Search\Test\Integration\Repository\KlevuSyncRepository;

use Klevu\Search\Api\KlevuSyncRepositoryInterface;
use Klevu\Search\Model\Klevu\Klevu;
use Klevu\Search\Model\Klevu\Klevu as KlevuSync;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuSyncResourceModel;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class GetProductIdsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

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

        $this->assertIsArray($productIds);
        $this->assertCount($count, $productIds);
    }

    public function testGetMxSyncId()
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
    }

    /**
     * @return KlevuSyncRepositoryInterface
     */
    private function instantiateKlevuRepository()
    {
        return $this->objectManager->create(KlevuSyncRepositoryInterface::class);
    }

    /**
     * @return StoreInterface
     */
    private function getStore()
    {
        $store = $this->objectManager->create(StoreInterface::class);
        $store->setId(Store::DISTRO_STORE_ID);

        return $store;
    }

    /**
     * @return KlevuSync
     * @throws AlreadyExistsException
     */
    private function createKlevuSyncEntity()
    {
        $sync = $this->instantiateSync();
        $sync->setProductId(mt_rand(1, 4999999));
        $sync->setParentId(mt_rand(5000000, 9999999));
        $sync->setType(KlevuSync::OBJECT_TYPE_PRODUCT);
        $sync->setStoreId(Store::DISTRO_STORE_ID);
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
}

