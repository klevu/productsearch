<?php

namespace Klevu\Search\Test\Integration\Setup\Patch\Data;

use Klevu\Search\Setup\Patch\Data\UpdateRatingProductAttributeLabel;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateRatingProductAttributeLabelTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var EavSetup|MockObject
     */
    private $mockEavSetup;
    /**
     * @var EavSetupFactory|MockObject
     */
    private $mockEavSetupFactory;

    public function testAttributeIsNotCreatedIfItExists()
    {
        $this->setupPhp5();

        $this->mockEavSetup->expects($this->once())->method('getAttributeId')->willReturn(false);
        $this->mockEavSetup->expects($this->never())->method('addAttribute');

        $this->mockEavSetupFactory->method('create')->willReturn($this->mockEavSetup);

        $patch = $this->objectManager->create(UpdateRatingProductAttributeLabel::class, [
            'eavSetupFactory' => $this->mockEavSetupFactory
        ]);

        $patch->apply();
    }

    public function testAttributeIsCreatedIfItDoesNotExists()
    {
        $this->setupPhp5();

        $this->mockEavSetup->expects($this->once())->method('getAttributeId')->willReturn(102);
        $this->mockEavSetup->expects($this->exactly(2))->method('updateAttribute');

        $this->mockEavSetupFactory->method('create')->willReturn($this->mockEavSetup);

        $patch = $this->objectManager->create(UpdateRatingProductAttributeLabel::class, [
            'eavSetupFactory' => $this->mockEavSetupFactory
        ]);

        $patch->apply();
    }

    /**
     * @return void
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->mockEavSetup = $this->getMockBuilder(EavSetup::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockEavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
