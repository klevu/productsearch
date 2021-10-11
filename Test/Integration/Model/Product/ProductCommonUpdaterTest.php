<?php

namespace Klevu\Search\Test\Integration\Model\Product;


use Klevu\Search\Model\Product\ProductCommonUpdaterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\ResourceConnection as FrameworkModelResource;
use Magento\Catalog\Model\ProductRepository;
use Zend_Db_Statement_Exception;

/**
 * Test for product common updater
 *
 * @see \Klevu\Search\Model\Product\ProductCommonUpdater
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class ProductCommonUpdaterTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var ProductCommonUpdaterInterface
     */
    private $productCommonUpdater;

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
        $this->productCommonUpdater = $this->objectManager->get(ProductCommonUpdaterInterface::class);
    }

    /**
     * Feature: Products are queued up for synchronisation at time of any update
     *
     * Scenario: Product is update through the backend for a store
     *    Given: There is a store configured
     *     When: An existing product is updated in the ADMIN area
     *     Then: The child items are queued up for synchronisation
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadDataFixtures
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    public function testUpdateLastSyncDate_UpdateGroupedParentProductByChildId()
    {
        $this->setupPhp5();
        $this->storeManager->setCurrentStore(1);

        //Clear fixtures before starting tests
        self::loadProductFixturesRollback();

        //Trigger virtual sync for single grouped with two children's
        self::loadAllGroupedProductFixtures();

        $groupedProduct = $this->productRepository->get('klevu_grouped_product_test');
        $simpleProduct = $this->productRepository->get('klevu_grouped_product_test_simple');

        $ids = [$groupedProduct->getId(), $simpleProduct->getId()];
        //Get klevu_product_sync for 1 grouped product having two children's - not empty exists with last_synced_at - not 0
        $klevuProductsBefore = $this->getKlevuProducts($ids, true);
        $this->assertIsArray($klevuProductsBefore);
        $this->assertEmpty($klevuProductsBefore);

        //Mark update
        $this->productCommonUpdater->markProductToQueue($simpleProduct);

        //Get klevu_product_sync for 1 grouped product having two children's - not empty exists with last_synced_at - 0
        $klevuProductsAfter = $this->getKlevuProducts($ids, false);
        $this->assertIsArray($klevuProductsAfter);
        $this->assertCount(2, $klevuProductsAfter);

        //forcefully rollbacks here as loadProductFixturesRollback not a part of a @magentoDataFixture
        self::loadProductFixturesRollback();
    }


    /**
     * Feature: Products are queued up for synchronisation at time of any update
     *
     * Scenario: Product is update through the backend for a store
     *    Given: There is a store configured
     *     When: An existing Child product is updated in the ADMIN area
     *     Then: The parent items are queued up for synchronisation
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadDataFixtures
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    public function testUpdateLastSyncDate_UpdateChildIdsByGroupParentProduct()
    {
        $this->setupPhp5();
        $this->storeManager->setCurrentStore(1);

        //Clear fixtures before starting tests
        self::loadProductFixturesRollback();

        //Trigger virtual sync for single grouped with two children's
        self::loadAllGroupedProductFixtures();

        $groupedProduct = $this->productRepository->get('klevu_grouped_product_test');
        $simpleProduct = $this->productRepository->get('klevu_grouped_product_test_simple');

        $ids = [$groupedProduct->getId(), $simpleProduct->getId()];

        $klevuProductsBefore = $this->getKlevuProducts($ids, true);
        $this->assertIsArray($klevuProductsBefore);
        $this->assertEmpty($klevuProductsBefore);

        $this->productCommonUpdater->markProductToQueue($groupedProduct);

        $klevuProductsAfter = $this->getKlevuProducts($ids, false);
        $this->assertIsArray($klevuProductsAfter);
        $this->assertCount(2, $klevuProductsAfter);

        //forcefully rollbacks here as loadProductFixturesRollback not a part of a @magentoDataFixture
        self::loadProductFixturesRollback();
    }

    /**
     * Feature: Products are queued up for synchronisation at time of any update
     *
     * Scenario: Product is update through the backend for a store
     *    Given: There is a store configured
     *     When: An existing product is updated in the ADMIN area
     *     Then: The child items are queued up for synchronisation
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadDataFixtures
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    public function testUpdateLastSyncDate_UpdateBundleParentProductByChildId()
    {
        $this->setupPhp5();
        $this->storeManager->setCurrentStore(1);

        //Clear fixtures before starting tests
        self::loadProductFixturesRollback();

        //Trigger virtual sync for single grouped with two children's
        self::loadAllBundleProductFixtures();

        $bundleProduct = $this->productRepository->get('klevu_bundle_product_test');
        $simpleProduct = $this->productRepository->get('klevu_bundle_product_test_simple');

        $ids = [$bundleProduct->getId(), $simpleProduct->getId()];
        //Get klevu_product_sync for 1 grouped product having two children's - not empty exists with last_synced_at - not 0
        $klevuProductsBefore = $this->getKlevuProducts($ids, true);
        $this->assertIsArray($klevuProductsBefore);
        $this->assertEmpty($klevuProductsBefore);

        //Mark update
        $this->productCommonUpdater->markProductToQueue($simpleProduct);

        //Get klevu_product_sync for 1 grouped product having two children's - not empty exists with last_synced_at - 0
        $klevuProductsAfter = $this->getKlevuProducts($ids, false);
        $this->assertIsArray($klevuProductsAfter);
        $this->assertCount(2, $klevuProductsAfter);

        //forcefully rollbacks here as loadProductFixturesRollback not a part of a @magentoDataFixture
        self::loadProductFixturesRollback();
    }

    /**
     * Feature: Products are queued up for synchronisation at time of any update
     *
     * Scenario: Product is update through the backend for a store
     *    Given: There is a store configured
     *     When: An existing Child product is updated in the ADMIN area
     *     Then: The parent items are queued up for synchronisation
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoDataFixture loadDataFixtures
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    public function testUpdateLastSyncDate_UpdateChildIdsByBundleParentProduct()
    {
        $this->setupPhp5();
        $this->storeManager->setCurrentStore(1);

        //Clear fixtures before starting tests
        self::loadProductFixturesRollback();

        //Trigger virtual sync for single grouped with two children's
        self::loadAllBundleProductFixtures();

        $bundleProduct = $this->productRepository->get('klevu_bundle_product_test');
        $simpleProduct = $this->productRepository->get('klevu_bundle_product_test_simple');

        $ids = [$bundleProduct->getId(), $simpleProduct->getId()];

        $klevuProductsBefore = $this->getKlevuProducts($ids, true);
        $this->assertIsArray($klevuProductsBefore);
        $this->assertEmpty($klevuProductsBefore);

        $this->productCommonUpdater->markProductToQueue($bundleProduct);

        $klevuProductsAfter = $this->getKlevuProducts($ids, false);
        $this->assertIsArray($klevuProductsAfter);
        $this->assertCount(2, $klevuProductsAfter);

        //forcefully rollbacks here as loadProductFixturesRollback not a part of a @magentoDataFixture
        self::loadProductFixturesRollback();
    }

    /**
     * Loads website and products creation fixtures because annotations use a relative path
     *  from integration tests root
     */
    public static function loadDataFixtures()
    {
        static::loadWebsiteFixtures();
    }

    /**
     * Rolls back website and products creation fixtures because annotations use a relative path
     *  from integration tests root
     */
    public static function loadDataFixturesRollback()
    {
        static::loadWebsiteFixturesRollback();
    }

    /**
     * Loads store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        require __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        require __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }

    /**
     * Loads product creation scripts and klevu product sync fixtures because annotations use a relative path
     *  from integration tests root
     */
    public static function loadAllGroupedProductFixtures()
    {
        static::loadGroupedProductFixtures();
        static::loadKlevuGroupedProductSyncFixtures();
    }

    /**
     * Loads product creation scripts and klevu product sync fixtures because annotations use a relative path
     *  from integration tests root
     */
    public static function loadAllBundleProductFixtures()
    {
        static::loadBundleProductFixtures();
        static::loadKlevuBundleProductSyncFixtures();
    }


    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadGroupedProductFixtures()
    {
        require __DIR__ . '/../../_files/productFixtures_groupedProduct.php';
    }

    /**
     * Loads bundle product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadBundleProductFixtures()
    {
        require __DIR__ . '/../../_files/productFixtures_bundleProduct.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        require __DIR__ . '/../../_files/productFixtures_groupedProduct_rollback.php';
    }

    /**
     * Loads klevu product sync fixtures scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuGroupedProductSyncFixtures()
    {
        require __DIR__ . '/_files/klevuGroupedProductSyncFixtures.php';
    }

    /**
     * Loads klevu product sync fixtures scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuBundleProductSyncFixtures()
    {
        require __DIR__ . '/_files/klevuBundleProductSyncFixtures.php';
    }

    /**
     * Returns klevu products from klevu_product_sync table
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    private function getKlevuProducts($ids = [], $lastSyncedAt = false)
    {
        /** @var FrameworkModelResource $resource */
        $resource = $this->objectManager->get(FrameworkModelResource::class);
        $connection = $resource->getConnection();

        $select = $connection->select();
        $select->from([
            'product_sync' => $resource->getTableName('klevu_product_sync'),
        ]);

        if ($lastSyncedAt) {
            $select->where('product_sync.last_synced_at = ?', 0);
        }

        $select->where('product_sync.product_id IN (?)', $ids);
        $stmt = $connection->query($select);
        return $stmt->fetchAll();
    }
}
