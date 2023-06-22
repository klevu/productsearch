<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\GetStockIdForWebsiteInterface;
use Klevu\Search\Service\Catalog\Product\GetStockIdForWebsite;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetStockIdForWebsiteTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function testImplements_GetStockIdForWebsiteInterface()
    {
        $this->setUpPhp5();

        $this->assertInstanceOf(
            GetStockIdForWebsiteInterface::class,
            $this->instantiateGetStockIdForWebsite()
        );
    }

    public function testPreference_ForGetStockIdForWebsiteInterface()
    {
        $this->setUpPhp5();
        if ($this->isMsiModuleConfiguredAndEnabled()) {
            $this->markTestSkipped('MSI module is installed and preferences this interface');
        }

        $this->assertInstanceOf(
            GetStockIdForWebsite::class,
            $this->objectManager->create(GetStockIdForWebsiteInterface::class)
        );
    }

    public function testExecute_ReturnsDefaultStockId()
    {
        $this->setUpPhp5();

        $service = $this->instantiateGetStockIdForWebsite();
        $stockId = $service->execute(1);

        $this->assertSame(Stock::DEFAULT_STOCK_ID, $stockId);
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @param $arguments
     *
     * @return GetStockIdForWebsite
     */
    private function instantiateGetStockIdForWebsite($arguments = [])
    {
        return $this->objectManager->create(GetStockIdForWebsite::class, $arguments);
    }

    /**
     * @return bool
     */
    private function isMsiModuleConfiguredAndEnabled()
    {
        $moduleList = $this->objectManager->create(ModuleList::class);
        $moduleName = 'Klevu_Msi';

        return $moduleList->has($moduleName);
    }
}
