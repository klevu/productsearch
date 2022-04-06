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
        $this->assertStringNotContainsString(ProductInterface::TYPE_ID, $whereString);
        $this->assertStringNotContainsString(ProductInterface::VISIBILITY, $whereString);
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
        $this->assertStringContainsString(ProductInterface::TYPE_ID, $whereString);
        $this->assertStringContainsString(ProductInterface::VISIBILITY, $whereString);
    }

    public function testGetMaxProductIdReturnsMaxProductIdAsInt()
    {
        $this->setupPhp5();

        $productCollection = $this->instantiateProductCollection();
        $this->assertIsInt($productCollection->getMaxProductId($this->getStore()));
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
}
