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
}
