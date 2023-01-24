<?php

namespace Klevu\Search\Test\Unit\Service\Sync\Product;

use Klevu\Search\Service\Sync\Product\UpdateInNextCron;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateInNextCronTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private $mockResourceCollection;
    /**
     * @var AdapterInterface|MockObject
     */
    private $mockConnection;

    public function testThrowsExceptionIfNoRowIdsProvided()
    {
        $this->setUpPhp5();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must provide at least one row id');

        $updateInNextCron = $this->instantiateUpdateInNextCronService();
        $updateInNextCron->execute([], []);
    }

    public function testUpdateSyncTableEntryForAllStores()
    {
        $this->setUpPhp5();

        $rowIds = [1];
        $storeIds = [];
        $tableName = 'klevu_product_sync';

        $this->mockConnection->expects($this->once())
            ->method('quoteInto')
            ->with('row_id IN (?)', $rowIds);

        $this->mockConnection->expects($this->once())
            ->method('update')
            ->with($tableName, ['last_synced_at' => UpdateInNextCron::LAST_SYNC_AT_TIME]);

        $this->mockResourceCollection->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->mockConnection);

        $this->mockResourceCollection->expects($this->once())
            ->method('getTableName')
            ->with('klevu_product_sync')
            ->willReturn($tableName);

        $updateInNextCron = $this->instantiateUpdateInNextCronService();
        $updateInNextCron->execute($rowIds, $storeIds);
    }

    public function testUpdateSyncTableEntryForOneStore()
    {
        $this->setUpPhp5();

        $rowIds = [1];
        $storeIds = [1];
        $tableName = 'klevu_product_sync';

        $this->mockConnection->expects($this->exactly(2))
            ->method('quoteInto')
            ->withConsecutive(
                ['row_id IN (?)', $rowIds],
                [' AND `store_id` IN (?)', $storeIds]
            );

        $this->mockConnection->expects($this->once())
            ->method('update')
            ->with($tableName, ['last_synced_at' => UpdateInNextCron::LAST_SYNC_AT_TIME]);

        $this->mockResourceCollection->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->mockConnection);

        $this->mockResourceCollection->expects($this->once())
            ->method('getTableName')
            ->with('klevu_product_sync')
            ->willReturn($tableName);

        $updateInNextCron = $this->instantiateUpdateInNextCronService();
        $updateInNextCron->execute($rowIds, $storeIds);
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
    }

    /**
     * @return UpdateInNextCron
     */
    private function instantiateUpdateInNextCronService()
    {
        return new UpdateInNextCron(
            $this->mockResourceCollection
        );
    }
}
