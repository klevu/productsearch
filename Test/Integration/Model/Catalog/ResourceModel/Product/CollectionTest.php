<?php

namespace Klevu\Search\Test\Integration\Model\Catalog\ResourceModel\Product;

use Klevu\Search\Model\Catalog\Product as KlevuProduct;
use Klevu\Search\Model\Catalog\ResourceModel\Product\Collection as KlevuProductCollection;
use Magento\Eav\Model\Entity;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testModelIsKlevuImplementationOfProduct()
    {
        $this->setUpPhp5();

        $collection = $this->instantiateCollection();
        $collection->load();
        $products = $collection->getItems();

        $keys = array_keys($products);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('-0', $keys[0]);
        } else {
            $this->assertContains('-0', $keys[0]);
        }
        $product = $products[$keys[0]];
        $entityId = $product->getData(Entity::DEFAULT_ENTITY_ID_FIELD);

        $this->assertInstanceOf(KlevuProduct::class, $product);
        $expected = $entityId . '-0';
        $this->assertSame($expected, $product->getId());

        static::loadWebsiteFixturesRollback();
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
     * @return KlevuProductCollection
     */
    private function instantiateCollection()
    {
        return $this->objectManager->get(KlevuProductCollection::class);
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../../../_files/productFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../../../_files/productFixtures_rollback.php';
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../../_files/websiteFixtures_rollback.php';
    }
}
