<?php

namespace Klevu\Search\Test\Integration\Model\System\Config\Source\Product;

use Klevu\Search\Model\System\Config\Source\Product\Attributes as AttributesSourceModel;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class AttributesTest extends TestCase
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

    /**
     * @magentoAppArea adminhtml
     *
     * @return array[]
     */
    public function testToOptionArrayReturnsValidArray()
    {
        $this->setUpPhp5();

        /** @var AttributesSourceModel $attributeSourceModel */
        $attributeSourceModel = $this->objectManager->get(AttributesSourceModel::class);

        $options = $attributeSourceModel->toOptionArray();
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($options);
        } else {
            $this->assertTrue(is_array($options), 'Options return is array');
        }

        $this->assertNotEmpty($options);

        foreach ($options as $i => $option) {
            if (method_exists($this, 'assertIsArray')) {
                $this->assertIsArray($option);
            } else {
                $this->assertTrue(is_array($option), 'Option #' . $i . ' is array');
            }

            $this->assertArrayHasKey('label', $option, 'Option #' . $i . ' has label');
            $this->assertTrue(is_string($option['label']), 'Option #' . $i . ' label is string');
            $this->assertTrue('' !== trim($option['label']), 'Option #' . $i . ' label is not empty');

            $this->assertArrayHasKey('value', $option);
            $this->assertTrue(is_string($option['value']), 'Option #' . $i . ' value is string');
            $this->assertTrue('' !== trim($option['value']), 'Option #' . $i . ' value is not empty');
        }

        return $options;
    }

    /**
     * @magentoAppArea adminhtml
     *
     * @param array[] $options
     *
     * @depends testToOptionArrayReturnsValidArray
     */
    public function testToOptionArrayDoesNotContainUrlKey(array $options)
    {
        $this->assertEmpty(
            array_filter($options, static function (array $option) {
                return $option['value'] === 'url_key';
            })
        );
    }

    /**
     * @magentoAppArea adminhtml
     *
     * @param array[] $options
     *
     * @depends testToOptionArrayReturnsValidArray
     */
    public function testToOptionArrayDoesNotContainRating(array $options)
    {
        $this->assertEmpty(
            array_filter($options, static function (array $option) {
                return $option['value'] === 'rating';
            })
        );
    }

    /**
     * @magentoAppArea adminhtml
     *
     * @param array[] $options
     *
     * @depends testToOptionArrayReturnsValidArray
     */
    public function testToOptionArrayDoesNotContainRatingCount(array $options)
    {
        $this->assertEmpty(
            array_filter($options, static function (array $option) {
                return $option['value'] === 'rating_count';
            })
        );
    }
}
