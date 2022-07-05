<?php

namespace Klevu\Search\Test\Integration\Model\Product\ResourceModel;

use Klevu\Search\Model\Product\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Eav\Model\Entity;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class getParentProductRelationsTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadProductFixtures
     *
     */
    public function testGetParentProductRelationsReturnsArray()
    {
        $this->setupPhp5();

        $productIds = $this->getProductIds();

        $productResource = $this->instantiateKlevuProductResourceModel();
        $parentProductRelations = $productResource->getParentProductRelations($productIds);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($parentProductRelations);
        } else {
            $this->assertTrue(is_array($parentProductRelations), 'Is Array');
        }
        $this->assertNotCount(0, $parentProductRelations);
        $this->assertArrayHasKey(Entity::DEFAULT_ENTITY_ID_FIELD, $parentProductRelations[0]);
        $this->assertArrayHasKey('product_id', $parentProductRelations[0]);
        $this->assertArrayHasKey('parent_id', $parentProductRelations[0]);
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     */
    public function testGetParentProductRelationsReturnsEmptyArrayIfNoIdsProvided()
    {
        $this->setupPhp5();

        $productIds = [];

        $productResource = $this->instantiateKlevuProductResourceModel();
        $parentProductRelations = $productResource->getParentProductRelations($productIds);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($parentProductRelations);
        } else {
            $this->assertTrue(is_array($parentProductRelations), 'Is Array');
        }
        $this->assertCount(0, $parentProductRelations);
    }

    /**
     * @return ProductResourceModel|mixed
     */
    private function instantiateKlevuProductResourceModel()
    {
        return $this->objectManager->create(ProductResourceModel::class);
    }

    /**
     * @return array
     */
    private function getProductIds()
    {
        $productCollection = $this->objectManager->get(Collection::class);

        return $productCollection->getAllIds();
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Loads product collection creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        require __DIR__ . '/../_files/productFixtures.php';
    }

    /**
     * Rolls back order creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        require __DIR__ . '/../_files/productFixtures_rollback.php';
    }
}