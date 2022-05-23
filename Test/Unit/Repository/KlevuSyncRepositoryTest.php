<?php

namespace Klevu\Search\Test\Unit\Repository;

use Klevu\Search\Model\Klevu\ResourceModel\Klevu;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\Collection;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\CollectionFactory;
use Klevu\Search\Repository\KlevuSyncRepository;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class KlevuSyncRepositoryTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testGetMaxSyncIdReturnsInt()
    {
        $this->setupPhp5();
        $maxProductId = mt_rand(1,99999999);

        $mockKlevuSyncItemBuilder = $this->getMockBuilder(Klevu::class);
        if (method_exists($mockKlevuSyncItemBuilder, 'addMethods')) {
            $mockKlevuSyncItemBuilder->addMethods(['getId']);
        } else {
            $mockKlevuSyncItemBuilder->setMethods(['getId']);
        }
        $mockKlevuSyncItem = $mockKlevuSyncItemBuilder->disableOriginalConstructor()->getMock();
        $mockKlevuSyncItem->expects($this->once())
            ->method('getId')
            ->willReturn($maxProductId);

        $mockKlevuCollection = $this->getMockKlevuCollection(false);
        $mockKlevuCollection->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($mockKlevuSyncItem);

        $mockKlevuCollectionFactory = $this->getMockKlevuCollectionFactory($mockKlevuCollection);
        $mockKlevuResourceModel = $this->getMockKlevuResourceModel();
        $mockLogger = $this->mockLogger();

        $mockStore = $this->getMockStore();

        $repo = $this->objectManager->getObject(KlevuSyncRepository::class, [
            'KlevuSyncCollectionFactory' => $mockKlevuCollectionFactory,
            'klevuResourceModel' => $mockKlevuResourceModel,
            'logger' => $mockLogger
        ]);

        $result = $repo->getMaxSyncId($mockStore);

        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($result);
        } else {
            $this->assertTrue(is_int($result), 'Is Int');
        }
        $this->assertSame($maxProductId, $result);
    }

    public function testGetProductIdsReturnsArrayForDelete()
    {
        $this->setupPhp5();

        $mockKlevuCollection = $this->getMockKlevuCollection();
        $mockKlevuCollection->expects($this->never())->method('filterProductsToUpdate');

        $mockKlevuCollectionFactory = $this->getMockKlevuCollectionFactory($mockKlevuCollection);
        $mockKlevuResourceModel = $this->getMockKlevuResourceModel();
        $mockLogger = $this->mockLogger();

        $mockStore = $this->getMockStore();

        $repo = $this->objectManager->getObject(KlevuSyncRepository::class, [
            'KlevuSyncCollectionFactory' => $mockKlevuCollectionFactory,
            'klevuResourceModel' => $mockKlevuResourceModel,
            'logger' => $mockLogger
        ]);
        $result = $repo->getProductIds($mockStore);
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result);
        } else {
            $this->assertTrue(is_array($result), 'Is Array');
        }
        $this->assertCount(4, $result);
        $keys = array_keys($result);
        $this->assertArrayHasKey($keys[0], $result);
        $this->assertArrayHasKey('row_id', $result[$keys[0]]);
        $this->assertSame('1', $result[$keys[0]]['row_id']);
        $this->assertArrayHasKey('product_id', $result[$keys[0]]);
        $this->assertSame('1', $result[$keys[0]]['product_id']);
        $this->assertArrayHasKey('parent_id', $result[$keys[0]]);
        $this->assertSame('0', $result[$keys[0]]['parent_id']);

        $this->assertCount(4, $result);
        $this->assertArrayHasKey($keys[1], $result);
        $this->assertArrayHasKey('row_id', $result[$keys[1]]);
        $this->assertSame('5', $result[$keys[1]]['row_id']);
        $this->assertArrayHasKey('product_id', $result[$keys[1]]);
        $this->assertSame('12', $result[$keys[1]]['product_id']);
        $this->assertArrayHasKey('parent_id', $result[$keys[1]]);
        $this->assertSame('13', $result[$keys[1]]['parent_id']);
    }

    public function testGetProductIdsFiltersProductsToUpdate()
    {
        $this->setupPhp5();

        $mockKlevuCollection = $this->getMockKlevuCollection();
        $mockKlevuCollection->expects($this->once())->method('filterProductsToUpdate');
        $mockKlevuCollectionFactory = $this->getMockKlevuCollectionFactory($mockKlevuCollection);
        $mockKlevuResourceModel = $this->getMockKlevuResourceModel();
        $mockLogger = $this->mockLogger();

        $mockStore = $this->getMockStore();

        $repo = $this->objectManager->getObject(KlevuSyncRepository::class, [
            'KlevuSyncCollectionFactory' => $mockKlevuCollectionFactory,
            'klevuResourceModel' => $mockKlevuResourceModel,
            'logger' => $mockLogger
        ]);
        $result = $repo->getProductIds($mockStore, [], 0, true);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result);
        } else {
            $this->assertTrue(is_array($result), 'Is Array');
        }
        $this->assertCount(4, $result);
        $keys = array_keys($result);
        $this->assertArrayHasKey($keys[0], $result);
        $this->assertArrayHasKey('row_id', $result[$keys[0]]);
        $this->assertSame('1', $result[$keys[0]]['row_id']);
    }

    /**
     * @return StoreInterface|MockObject
     */
    private function getMockStore()
    {
        return $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|LoggerInterface
     */
    private function mockLogger()
    {
        return $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return Collection|MockObject
     */
    private function getMockKlevuCollection($callInit = true)
    {
        $mockKlevuCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        if ($callInit) {
            $mockKlevuCollection->expects($this->once())
                ->method('initCollectionByType')
                ->willReturnSelf();
        }
        return $mockKlevuCollection;
    }

    /**
     * @param $mockKlevuCollection
     *
     * @return CollectionFactory|MockObject
     */
    private function getMockKlevuCollectionFactory($mockKlevuCollection)
    {
        $mockKlevuCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockKlevuCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockKlevuCollection);

        return $mockKlevuCollectionFactory;
    }

    /**
     * @return Klevu|MockObject
     */
    private function getMockKlevuResourceModel()
    {
        $mockKlevuResourceModel = $this->getMockBuilder(Klevu::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockKlevuResourceModel->expects($this->any())
            ->method('getBatchDataForCollection')
            ->willReturn(
                [
                    0 => ["row_id" => "1", "product_id" => "1", "parent_id" => "0"],
                    1 => ["row_id" => "5", "product_id" => "12", "parent_id" => "13"],
                    2 => ["row_id" => "3", "product_id" => "3", "parent_id" => "0"],
                    3 => ["row_id" => "4", "product_id" => "4", "parent_id" => "0"]
                ]
            );

        return $mockKlevuResourceModel;
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = new ObjectManager($this);
    }
}
