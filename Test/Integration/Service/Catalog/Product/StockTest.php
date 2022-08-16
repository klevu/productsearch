<?php

namespace Klevu\Search\Test\Integration\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\StockServiceInterface;
use Klevu\Search\Service\Catalog\Product\Stock;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class StockTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();
        $getKmcUrlService = $this->instantiateStockService();

        $this->assertInstanceOf(Stock::class, $getKmcUrlService);
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
     * @return StockServiceInterface
     */
    private function instantiateStockService()
    {
        return $this->objectManager->create(StockServiceInterface::class);
    }
}
