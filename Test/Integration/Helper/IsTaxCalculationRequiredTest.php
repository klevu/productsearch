<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Helper;

use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class IsTaxCalculationRequiredTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @return void
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoConfigFixture default/tax/display/typeinsearch 1
     * @magentoConfigFixture default_store tax/display/typeinsearch 2
     */
    public function testReturnsTrue_WhenTaxIncluded()
    {
        $this->setupPhp5();

        $helper = $this->instantiateHelper();
        $isTaxCalculationRequired = $helper->isTaxCalRequired();
        $this->assertTrue($isTaxCalculationRequired, 'Tax Calculation Required');
    }

    /**
     * @magentoConfigFixture default/tax/display/typeinsearch 2
     * @magentoConfigFixture default_store tax/display/typeinsearch 1
     */
    public function testReturnsFalse_WhenTaxExcluded()
    {
        $this->setupPhp5();

        $helper = $this->instantiateHelper();
        $isTaxCalculationRequired = $helper->isTaxCalRequired();
        $this->assertFalse($isTaxCalculationRequired, 'Tax Calculation Required');
    }

    /**
     * @return ConfigHelper
     */
    private function instantiateHelper()
    {
        return $this->objectManager->create(ConfigHelper::class);
    }
}
