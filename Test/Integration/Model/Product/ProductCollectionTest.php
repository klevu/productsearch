<?php

namespace Klevu\Search\Test\Integration\Model\Product;

use Klevu\Search\Model\Product\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\DB\Select;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 */
class ProductCollectionTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testInitReturnsProductCollection()
    {
        $this->setupPhp5();

        $store = $this->getStore();
        $klevuProductCollection = $this->instantiateProductCollection();
        $magentoProductCollection = $klevuProductCollection->initCollectionByType($store, [], []);

        $this->assertInstanceOf(Collection::class, $magentoProductCollection);

        $where = $magentoProductCollection->getSelect()->getPart(Select::WHERE);
        $whereString = json_encode($where);
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString(ProductInterface::TYPE_ID, $whereString);
            $this->assertStringNotContainsString(ProductInterface::VISIBILITY, $whereString);
        } else {
            $this->assertNotContains(ProductInterface::TYPE_ID, $whereString);
            $this->assertNotContains(ProductInterface::VISIBILITY, $whereString);
        }

    }

    public function testInitFiltersTypeAndVisibilityWhenProvided()
    {
        $this->setupPhp5();

        $store = $this->getStore();
        $klevuProductCollection = $this->instantiateProductCollection();
        $magentoProductCollection = $klevuProductCollection->initCollectionByType(
            $store,
            ['simple', 'virtual', 'configurable'],
            [Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_IN_SEARCH]
        );

        $this->assertInstanceOf(Collection::class, $magentoProductCollection);

        $where = $magentoProductCollection->getSelect()->getPart(Select::WHERE);
        $whereString = json_encode($where);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(ProductInterface::TYPE_ID, $whereString);
            $this->assertStringContainsString(ProductInterface::VISIBILITY, $whereString);
        } else {
            $this->assertContains(ProductInterface::TYPE_ID, $whereString);
            $this->assertContains(ProductInterface::VISIBILITY, $whereString);
        }
    }

    public function testGetMaxProductIdReturnsMaxProductIdAsInt()
    {
        $this->setupPhp5();

        $productCollection = $this->instantiateProductCollection();
        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($productCollection->getMaxProductId($this->getStore()));
        } else {
            $this->assertTrue(is_int($productCollection->getMaxProductId($this->getStore())), 'Is Int');
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadProductFixtures
     */
    public function testGetMaxProductId_ReturnsConfigurableProductId_IfThatIsHighest()
    {
        $this->setupPhp5();

        $expectedMaxProductId = $this->getMaxProductEntityId();

        $productCollection = $this->instantiateProductCollection();
        $actualMaxProductId = $productCollection->getMaxProductId($this->getStore());

        $this->assertSame($expectedMaxProductId, $actualMaxProductId);
    }

    /**
     * @return ProductCollection
     */
    private function instantiateProductCollection()
    {
        return $this->objectManager->get(ProductCollection::class);
    }

    /**
     * @return StoreInterface
     */
    private function getStore()
    {
        $store = $this->objectManager->get(StoreInterface::class);
        $store->setId(Store::DISTRO_STORE_ID);

        return $store;
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @return int
     */
    private function getMaxProductEntityId()
    {
        $resource = $this->objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('catalog_product_entity');

        $sql = sprintf("SELECT `entity_id` FROM %s ORDER BY `entity_id` DESC", $tableName);
        $result = $connection->fetchOne($sql);

        return (int)$result;
    }

    /**
     * Loads product collection creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        require __DIR__ . '/_files/productFixtures.php';
    }

    /**
     * Rolls back order creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        require __DIR__ . '/_files/productFixtures_rollback.php';
    }
}
