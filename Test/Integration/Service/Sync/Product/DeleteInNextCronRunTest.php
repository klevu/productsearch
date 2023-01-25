<?php

namespace Klevu\Search\Test\Integration\Service\Sync\Product;

use Klevu\Search\Api\Service\Sync\Product\DeleteInNextCronInterface;
use Klevu\Search\Service\Sync\Product\DeleteInNextCron;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class DeleteInNextCronRunTest extends TestCase
{
    /**
     * @var  ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();

        $updateInNextCron = $this->instantiateDeleteInNextCronService();

        $this->assertInstanceOf(DeleteInNextCron::class, $updateInNextCron);
    }

    public function testThrowsExceptionIfNoRowIdsProvided()
    {
        $this->setUpPhp5();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must provide at least one row id');

        $updateInNextCron = $this->instantiateDeleteInNextCronService();
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
     * @return DeleteInNextCron
     */
    private function instantiateDeleteInNextCronService()
    {
        return $this->objectManager->get(DeleteInNextCronInterface::class);
    }
}
