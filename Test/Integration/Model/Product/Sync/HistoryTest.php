<?php

namespace Klevu\Search\Test\Integration\Model\Product\Sync;

use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Api\Data\HistoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class HistoryTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $historyModel = $this->instantiateHistoryModel();

        $this->assertInstanceOf(History::class, $historyModel);
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
     */
    private function instantiateHistoryModel()
    {
        return $this->objectManager->get(HistoryInterface::class);
    }
}
