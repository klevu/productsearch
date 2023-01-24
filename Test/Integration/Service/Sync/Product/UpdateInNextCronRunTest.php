<?php

namespace Klevu\Search\Test\Integration\Service\Sync\Product;

use Klevu\Search\Api\Service\Sync\Product\UpdateInNextCronInterface;
use Klevu\Search\Service\Sync\Product\UpdateInNextCron;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class UpdateInNextCronRunTest extends TestCase
{
    /**
     * @var  ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $updateInNextCron = $this->instantiateUpdateInNextCronService();

        $this->assertInstanceOf(UpdateInNextCron::class, $updateInNextCron);
    }

    public function testThrowsExceptionIfNoRowIdsProvided()
    {
        $this->setUpPhp5();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must provide at least one row id');

        $updateInNextCron = $this->instantiateUpdateInNextCronService();
        $updateInNextCron->execute([], []);
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
     * @return UpdateInNextCron
     */
    private function instantiateUpdateInNextCronService()
    {
        return $this->objectManager->get(UpdateInNextCronInterface::class);
    }
}
