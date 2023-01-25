<?php

namespace Klevu\Search\Test\Integration\Model\Source;

use Klevu\Search\Model\Source\NextAction;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class NextActionTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $nextActionSource = $this->instantiateNextActionSource();

        $this->assertInstanceOf(OptionSourceInterface::class, $nextActionSource);
    }

    public function testReturnsArray()
    {
        $this->setUpPhp5();

        $nextActionSource = $this->instantiateNextActionSource();
        $result = $nextActionSource->toOptionArray();

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result);
        } else {
            $this->assertTrue(is_array($result), 'Is Array');
        }
        $this->assertCount(4, $result);

        $keys = array_keys($result);

        $this->assertArrayHasKey('value', $result[$keys[1]]);
        $this->assertSame($result[$keys[1]]['value'], NextAction::ACTION_VALUE_ADD);
        $this->assertArrayHasKey('label', $result[$keys[1]]);
        $this->assertSame($result[$keys[1]]['label'], ucwords(NextAction::ACTION_ADD));

        $this->assertArrayHasKey('value', $result[$keys[2]]);
        $this->assertSame($result[$keys[2]]['value'], NextAction::ACTION_VALUE_UPDATE);
        $this->assertArrayHasKey('label', $result[$keys[2]]);
        $this->assertSame($result[$keys[2]]['label'], ucwords(NextAction::ACTION_UPDATE));

        $this->assertArrayHasKey('value', $result[$keys[3]]);
        $this->assertSame($result[$keys[3]]['value'], NextAction::ACTION_VALUE_DELETE);
        $this->assertArrayHasKey('label', $result[$keys[3]]);
        $this->assertSame($result[$keys[3]]['label'], ucwords(NextAction::ACTION_DELETE));
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
     * @return NextAction
     */
    private function instantiateNextActionSource()
    {
        return $this->objectManager->create(NextAction::class, [

        ]);
    }
}
