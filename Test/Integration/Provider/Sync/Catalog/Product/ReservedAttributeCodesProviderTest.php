<?php

namespace Klevu\Search\Test\Integration\Provider\Sync\Catalog;

use Klevu\Search\Provider\Sync\Catalog\Product\ReservedAttributeCodesProvider;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ReservedAttributeCodesProviderTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    public function testExecuteReturnsValidArray()
    {
        $this->setUpPhp5();

        /** @var ReservedAttributeCodesProvider $reservedAttributeCodesProvider */
        $reservedAttributeCodesProvider = $this->objectManager->get(ReservedAttributeCodesProvider::class);

        $reservedAttributeCodes = $reservedAttributeCodesProvider->execute();
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($reservedAttributeCodes);
        } else {
            $this->assertTrue(is_array($reservedAttributeCodes), 'Return is array');
        }

        foreach ($reservedAttributeCodes as $i => $reservedAttributeCode) {
            $this->assertTrue(is_string($reservedAttributeCode), 'Returned code #' . $i . ' is string');
            $this->assertTrue('' !== trim($reservedAttributeCode), 'Returned code #' . $i . ' is not empty');
        }

        return $reservedAttributeCodes;
    }

    /**
     * @param string[] $attributeCodesReturn
     *
     * @depends testExecuteReturnsValidArray
     */
    public function testRatingReturned(array $attributeCodesReturn)
    {
        $this->assertContains('rating', $attributeCodesReturn);
    }

    /**
     * @param string[] $attributeCodesReturn
     *
     * @depends testExecuteReturnsValidArray
     */
    public function testRatingCountReturned(array $attributeCodesReturn)
    {
        $this->assertContains('rating_count', $attributeCodesReturn);
    }

    /**
     * @param string[] $attributeCodesReturn
     *
     * @depends testExecuteReturnsValidArray
     */
    public function testNameReturned(array $attributeCodesReturn)
    {
        $this->assertContains('name', $attributeCodesReturn);
    }

    /**
     * @param string[] $attributeCodesReturn
     *
     * @depends testExecuteReturnsValidArray
     */
    public function testSkuReturned(array $attributeCodesReturn)
    {
        $this->assertContains('sku', $attributeCodesReturn);
    }

    /**
     * @param string[] $attributeCodesReturn
     *
     * @depends testExecuteReturnsValidArray
     */
    public function testDescriptionReturned(array $attributeCodesReturn)
    {
        $this->assertContains('description', $attributeCodesReturn);
    }

    /**
     * @param string[] $attributeCodesReturn
     *
     * @depends testExecuteReturnsValidArray
     */
    public function testShortDescriptionReturned(array $attributeCodesReturn)
    {
        $this->assertContains('short_description', $attributeCodesReturn);
    }

    /**
     * @param string[] $attributeCodesReturn
     *
     * @depends testExecuteReturnsValidArray
     */
    public function testPriceReturned(array $attributeCodesReturn)
    {
        $this->assertContains('price', $attributeCodesReturn);
    }

    /**
     * @param string[] $attributeCodesReturn
     *
     * @depends testExecuteReturnsValidArray
     */
    public function testTierPriceReturned(array $attributeCodesReturn)
    {
        $this->assertContains('tier_price', $attributeCodesReturn);
    }

    /**
     * @param string[] $attributeCodesReturn
     *
     * @depends testExecuteReturnsValidArray
     */
    public function testMediaGalleryReturned(array $attributeCodesReturn)
    {
        $this->assertContains('media_gallery', $attributeCodesReturn);
    }
}
