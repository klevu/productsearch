<?php

namespace Klevu\Search\Test\Integration\Model\Api\Action;

use Klevu\Search\Model\Api\Action\Producttracking;
use Klevu\Search\Model\Api\Response\Invalid as KlevuInvalidApiRequest;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ProductTrackingTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    /**
     * @dataProvider InvalidSalePriceDataProvider
     */
    public function testValidationFailsForMissingPrice($invalidPrice)
    {
        $this->setupPhp5();

        $productTracking = $this->objectManager->get(Producttracking::class);
        $parameters = [
            'klevu_apiKey' => 'klevu-api-key',
            'klevu_type' => 'type',
            'klevu_productId' => '12345',
            'klevu_unit' => 'unit',
            'klevu_salePrice' => $invalidPrice,
            'klevu_currency' => 'GBP',
        ];

        /** @var KlevuInvalidApiRequest $response */
        $response = $productTracking->execute($parameters);
        $errors = $response->getErrors();

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($errors);
        } else {
            $this->assertTrue(is_array($errors), 'Is Array');
        }
        $this->assertCount(1, $errors);
        $this->assertArrayHasKey('klevu_salePrice', $errors);
    }

    /**
     * @dataProvider ValidSalePriceDataProvider
     */
    public function testValidationDoesNotFailForZeroPrice($validPrice)
    {
        $this->setupPhp5();

        $productTracking = $this->objectManager->get(Producttracking::class);
        $parameters = [
            'klevu_apiKey' => 'klevu-api-key',
            'klevu_type' => 'type',
            'klevu_productId' => '12345',
            'klevu_unit' => 'unit',
            'klevu_salePrice' => $validPrice,
            'klevu_currency' => 'GBP',
        ];

        /** @var KlevuInvalidApiRequest $response */
        $response = $productTracking->execute($parameters);
        $errors = $response->getErrors();

        if (method_exists($this, 'assertIsNotArray')) {
            $this->assertIsNotArray($errors);
        } else {
            $this->assertTrue(!is_array($errors), 'Is Not Array');
        }
    }

    /**
     * @return array
     */
    public function InvalidSalePriceDataProvider()
    {
        return [
            [null],
            [false],
            [[]]
        ];
    }

    /**
     * @return array
     */
    public function ValidSalePriceDataProvider()
    {
        return [
            [0],
            [0.00],
            ['0'],
            ['0.00']
        ];
    }

    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }
}
