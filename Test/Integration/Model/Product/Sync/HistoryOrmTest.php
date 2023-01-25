<?php

namespace Klevu\Search\Test\Integration\Model\Product\Sync;

use Klevu\Search\Api\Data\HistoryInterface;
use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Model\Product\Sync\ResourceModel\History as HistoryResourceModel;
use Klevu\Search\Model\Product\Sync\ResourceModel\History\Collection as SyncHistoryCollection;
use Klevu\Search\Model\Source\NextAction;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Store\Model\Store;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class HistoryOrmTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testCanLoadAndSave()
    {
        $this->setupPhp5();

        $syncHistory = $this->createSyncHistoryEntity();
        $syncToLoad = $this->instantiateSyncHistory();
        $this->instantiateSyncHistoryResourceModel()->load($syncToLoad, $syncHistory->getId());

        $this->assertSame((int)$syncHistory->getId(), (int)$syncToLoad->getId());
        $this->assertSame((int)$syncHistory->getProductId(), (int)$syncToLoad->getProductId());
        $this->assertSame($syncHistory->getMessage(), $syncToLoad->getMessage());
    }

    public function testCanLoadMultipleHistoryItems()
    {
        $this->setupPhp5();

        $syncHistoryA = $this->createSyncHistoryEntity();
        $syncHistoryB = $this->createSyncHistoryEntity();

        $collection = $this->instantiateSyncHistoryCollection();
        $items = $collection->getItems();

        $this->assertContains((int)$syncHistoryA->getId(), array_keys($items));
        $this->assertContains((int)$syncHistoryB->getId(), array_keys($items));
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
     * @return HistoryInterface
     * @throws AlreadyExistsException
     */
    private function createSyncHistoryEntity()
    {
        $syncHistory = $this->instantiateSyncHistory();
        $syncHistory->setProductId(mt_rand(1, 4999999));
        $syncHistory->setParentId(mt_rand(1, 4999999));
        $syncHistory->setAction(NextAction::ACTION_VALUE_ADD);
        $syncHistory->setStoreId(Store::DISTRO_STORE_ID);
        $syncHistory->setSuccess(true);
        $syncHistory->setMessage("success");

        $this->instantiateSyncHistoryResourceModel()->save($syncHistory);

        return $syncHistory;
    }

    /**
     * @return HistoryInterface
     */
    private function instantiateSyncHistory()
    {
        return $this->objectManager->create(History::class);
    }

    /**
     * @return HistoryResourceModel
     */
    private function instantiateSyncHistoryResourceModel()
    {
        return $this->objectManager->create(HistoryResourceModel::class);
    }

    /**
     * @return SyncHistoryCollection
     */
    private function instantiateSyncHistoryCollection()
    {
        return $this->objectManager->create(SyncHistoryCollection::class);
    }
}
