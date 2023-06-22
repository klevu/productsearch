<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Provider\Eav\Attribute;

use Klevu\Search\Api\Provider\Eav\Attribute\ProductAttributeCollectionProviderInterface;
use Klevu\Search\Provider\Eav\Attribute\ProductAttributeCollectionProvider;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as EntityAttributeCollection;
use Magento\Framework\DB\Select;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ProductAttributeCollectionProviderTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testImplements_ProductAttributeCollectionProviderInterface()
    {
        $this->setUpPhp5();

        $this->assertInstanceOf(
            ProductAttributeCollectionProviderInterface::class,
            $this->instantiateProductAttributeCollectionProvider()
        );
    }

    public function testPreference_ForProductAttributeCollectionProviderInterface()
    {
        $this->setUpPhp5();

        $this->assertInstanceOf(
            ProductAttributeCollectionProvider::class,
            $this->objectManager->create(ProductAttributeCollectionProviderInterface::class)
        );
    }

    public function testGetCollection_ReturnsCollection()
    {
        $this->setUpPhp5();

        $provider = $this->instantiateProductAttributeCollectionProvider();
        $collection = $provider->getCollection();

        $this->assertInstanceOf(EntityAttributeCollection::class, $collection);

        $selectObject = $collection->getSelect();
        $this->assertInstanceOf(Select::class, $selectObject);
        $selectString = $selectObject->__toString();

        $pattern = '#WHERE \(`main_table`.`entity_type_id` = \'4\'\)#';
        $matches = [];
        preg_match($pattern, $selectString, $matches);
        $this->assertCount(1, $matches, 'Filter for Entity Type is Product');
    }

    /**
     * @param mixed[] $arguments
     *
     * @return ProductAttributeCollectionProvider
     */
    private function instantiateProductAttributeCollectionProvider(array $arguments = [])
    {
        return $this->objectManager->create(
            ProductAttributeCollectionProvider::class,
            $arguments
        );
    }
}
