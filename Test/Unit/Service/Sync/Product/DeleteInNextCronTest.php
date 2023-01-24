<?php

namespace Klevu\Search\Test\Unit\Service\Sync\Product;

use Klevu\Search\Service\Sync\Product\DeleteInNextCron;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteInNextCronTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private $mockResourceCollection;
    /**
     * @var AdapterInterface|MockObject
     */
    private $mockConnection;
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $mockStoreManager;

    public function testThrowsExceptionIfNoRowIdsProvided()
    {
        $this->setUpPhp5();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must provide at least one row id');

        $updateInNextCron = $this->instantiateDeleteInNextCronService();
        $updateInNextCron->execute([], []);
    }

    /**
     * @dataProvider storeIdsDataProvider
     */
    public function testAddsSyncTableEntryForEachStore($storeIds)
    {
        $this->setUpPhp5();

        $rowIds = [
            '0-1' => [
                'parent_id' => '0',
                'product_id' => '1'
            ]
        ];
        $tableName = 'klevu_product_sync';

        $mockStore = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStore->method('getId')->willReturn(1);
        $this->mockStoreManager->method('getStores')
            ->willReturn([$mockStore]);

        $this->mockConnection->expects($this->exactly(count($storeIds)))
            ->method('insertMultiple')
            ->with($tableName);

        $this->mockResourceCollection->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->mockConnection);
        $this->mockResourceCollection->expects($this->once())
            ->method('getTableName')
            ->with('klevu_product_sync')
            ->willReturn($tableName);

        $deleteInNextCron = $this->instantiateDeleteInNextCronService();
        $deleteInNextCron->execute($rowIds, $storeIds);
    }

    public function testAddsSyncTableEntryForAllStoreWhenNoStoreIdProvided()
    {
        $this->setUpPhp5();

        $storeIds = [];
        $rowIds = [
            '0-1' => [
                'parent_id' => '0',
                'product_id' => '1'
            ]
        ];
        $tableName = 'klevu_product_sync';

        $mockStore1 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStore1->method('getId')->willReturn(1);
        $mockStore2 = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStore2->method('getId')->willReturn(1);
        $this->mockStoreManager->method('getStores')
            ->willReturn([$mockStore1, $mockStore2]);

        $this->mockConnection->expects($this->exactly(2))
            ->method('insertMultiple')
            ->with($tableName);

        $this->mockResourceCollection->expects($this->once())
            ->method('getConnection')
            ->with('core_write')
            ->willReturn($this->mockConnection);
        $this->mockResourceCollection->expects($this->once())
            ->method('getTableName')
            ->with('klevu_product_sync')
            ->willReturn($tableName);

        $deleteInNextCron = $this->instantiateDeleteInNextCronService();
        $deleteInNextCron->execute($rowIds, $storeIds);
    }

    /**
     * @return array
     */
    public function storeIdsDataProvider()
    {
        return [
            [[1]],
            [[1, 2]],
            [[1, 2, 3]]
        ];
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->mockConnection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockResourceCollection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStoreManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return DeleteInNextCron
     */
    private function instantiateDeleteInNextCronService()
    {
        return new DeleteInNextCron(
            $this->mockResourceCollection,
            $this->mockStoreManager
        );
    }
}
