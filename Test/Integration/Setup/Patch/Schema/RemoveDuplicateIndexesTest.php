<?php

namespace Klevu\Search\Test\Integration\Patch\Schema;

use Klevu\Search\Setup\Patch\Schema\RemoveDuplicateIndexes;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RemoveDuplicateIndexesTest extends TestCase
{

    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var AdapterInterface|MockObject
     */
    private $mockConnection;
    /**
     * @var ModuleDataSetupInterface|MockObject
     */
    private $mockModuleDataSetup;

    public function testDropIndexIsNotCalledIfOldIndexesDoNotExist()
    {
        $this->setupPhp5();

        $newIndexes = [
            RemoveDuplicateIndexes::INDEX_GROUP_NEW => [],
            RemoveDuplicateIndexes::INDEX_PARENT_PRODUCT_NEW => []
        ];

        $this->mockConnection->expects($this->once())->method('getIndexList')->willReturn($newIndexes);
        $this->mockConnection->expects($this->never())->method('dropIndex');

        $this->mockModuleDataSetup->expects($this->once())->method('getConnection')->willReturn($this->mockConnection);

        $patch = $this->objectManager->create(RemoveDuplicateIndexes::class, [
            'moduleDataSetup' => $this->mockModuleDataSetup
        ]);

        $patch->apply();
    }

    public function testDropIndexIsNotCalledIfNewIndexesDoNotExist()
    {
        $this->setupPhp5();

        $newIndexes = [
            RemoveDuplicateIndexes::INDEX_GROUP_OLD => [],
            RemoveDuplicateIndexes::INDEX_PARENT_PRODUCT_OLD => []
        ];

        $this->mockConnection->expects($this->once())->method('getIndexList')->willReturn($newIndexes);
        $this->mockConnection->expects($this->never())->method('dropIndex');

        $this->mockModuleDataSetup->expects($this->once())->method('getConnection')->willReturn($this->mockConnection);

        $patch = $this->objectManager->create(RemoveDuplicateIndexes::class, [
            'moduleDataSetup' => $this->mockModuleDataSetup
        ]);

        $patch->apply();
    }

    public function testDropIndexISCalledIfDuplicateIndexesExist()
    {
        $this->setupPhp5();

        $newIndexes = [
            RemoveDuplicateIndexes::INDEX_GROUP_OLD => [],
            RemoveDuplicateIndexes::INDEX_PARENT_PRODUCT_OLD => [],
            RemoveDuplicateIndexes::INDEX_GROUP_NEW => [],
            RemoveDuplicateIndexes::INDEX_PARENT_PRODUCT_NEW => []
        ];

        $this->mockConnection->expects($this->once())->method('getIndexList')->willReturn($newIndexes);
        $this->mockConnection->expects($this->exactly(2))->method('dropIndex');

        $this->mockModuleDataSetup->expects($this->once())->method('getConnection')->willReturn($this->mockConnection);

        $patch = $this->objectManager->create(RemoveDuplicateIndexes::class, [
            'moduleDataSetup' => $this->mockModuleDataSetup
        ]);

        $patch->apply();
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->mockConnection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockModuleDataSetup = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
