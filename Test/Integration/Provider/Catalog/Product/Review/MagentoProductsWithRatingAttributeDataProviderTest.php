<?php

namespace Klevu\Search\Test\Integration\Provider\Catalog\Product\Review;

use Klevu\Search\Provider\Catalog\Product\Review\MagentoProductsWithRatingAttributeDataProvider;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class MagentoProductsWithRatingAttributeDataProviderTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testGetProductIdsForAllStores()
    {
        $this->setUpPhp5();

        /** @var MagentoProductsWithRatingAttributeDataProvider $provider */
        $provider = $this->objectManager->get(MagentoProductsWithRatingAttributeDataProvider::class);

        $expectedProductIds = $this->getProductIdsForSkus([
            'klevu_simple_with_rating_with_reviewcount_allstores',
            'klevu_simple_with_rating_with_reviewcount_store1',
            'klevu_simple_with_rating_with_reviewcount_store2',
            'klevu_simple_with_rating_without_reviewcount_allstores',
            'klevu_simple_with_rating_without_reviewcount_store1',
            'klevu_simple_with_rating_without_reviewcount_store2',
            'klevu_simple_without_rating_with_reviewcount_allstores',
            'klevu_simple_without_rating_with_reviewcount_store1',
            'klevu_simple_without_rating_with_reviewcount_store2',
        ]);
        $notExpectedProductIds = $this->getProductIdsForSkus([
            'klevu_simple_without_rating_without_reviewcount_allstores',
            'klevu_simple_without_rating_without_reviewcount_store1',
            'klevu_simple_without_rating_without_reviewcount_store2',
            'klevu_simple_with_rating_with_reviewcount_globalscope',
        ]);

        $result = $provider->getProductIdsForAllStores();
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result);
        } else {
            $this->assertTrue(is_array($result), 'Result is array');
        }

        $missingExpectedIds = array_diff($expectedProductIds, $result);
        // Note, we have to check against explicitly unexpected ids based on fixture data here
        //  as dirty databases with records in the default store will muddy our test
        $unexpectedReturnedIds = array_intersect($notExpectedProductIds, $result);

        $this->assertEmpty(
            $missingExpectedIds,
            'Missing Expected Ids: ' . json_encode($this->getSkusForProductIds($missingExpectedIds))
        );
        $this->assertEmpty(
            $unexpectedReturnedIds,
            'Unexpected Returned Ids: ' . json_encode($this->getSkusForProductIds($unexpectedReturnedIds))
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testGetProductIdsForStore()
    {
        $this->setUpPhp5();

        /** @var MagentoProductsWithRatingAttributeDataProvider $provider */
        $provider = $this->objectManager->get(MagentoProductsWithRatingAttributeDataProvider::class);

        // Store 1
        $store1 = $this->storeManager->getStore('klevu_test_store_1');

        $expectedProductIds = $this->getProductIdsForSkus([
            'klevu_simple_with_rating_with_reviewcount_allstores',
            'klevu_simple_with_rating_with_reviewcount_store1',
            'klevu_simple_with_rating_without_reviewcount_allstores',
            'klevu_simple_with_rating_without_reviewcount_store1',
            'klevu_simple_without_rating_with_reviewcount_allstores',
            'klevu_simple_without_rating_with_reviewcount_store1',
        ]);
        $notExpectedProductIds = $this->getProductIdsForSkus([
            'klevu_simple_with_rating_with_reviewcount_store2',
            'klevu_simple_with_rating_without_reviewcount_store2',
            'klevu_simple_without_rating_with_reviewcount_store2',
            'klevu_simple_without_rating_without_reviewcount_allstores',
            'klevu_simple_without_rating_without_reviewcount_store1',
            'klevu_simple_without_rating_without_reviewcount_store2',
            'klevu_simple_with_rating_with_reviewcount_globalscope',
        ]);

        $result = $provider->getProductIdsForStore((int)$store1->getId());
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result);
        } else {
            $this->assertTrue(is_array($result), 'Result is array');
        }

        $missingExpectedIds = array_diff($expectedProductIds, $result);
        $unexpectedReturnedIds = array_diff($result, $expectedProductIds);

        $this->assertEmpty(
            $missingExpectedIds,
            'Missing Expected Ids (Store 1): ' . json_encode($this->getSkusForProductIds($missingExpectedIds))
        );
        $this->assertEmpty(
            $unexpectedReturnedIds,
            'Unexpected Returned Ids (Store 1)): ' . json_encode($this->getSkusForProductIds($unexpectedReturnedIds))
        );

        // Store 2
        $store2 = $this->storeManager->getStore('klevu_test_store_2');

        $expectedProductIds = $this->getProductIdsForSkus([
            'klevu_simple_with_rating_with_reviewcount_allstores',
            'klevu_simple_with_rating_with_reviewcount_store2',
            'klevu_simple_with_rating_without_reviewcount_allstores',
            'klevu_simple_with_rating_without_reviewcount_store2',
            'klevu_simple_without_rating_with_reviewcount_allstores',
            'klevu_simple_without_rating_with_reviewcount_store2',
        ]);
        $notExpectedProductIds = $this->getProductIdsForSkus([
            'klevu_simple_with_rating_with_reviewcount_store1',
            'klevu_simple_with_rating_without_reviewcount_store1',
            'klevu_simple_without_rating_with_reviewcount_store1',
            'klevu_simple_without_rating_without_reviewcount_allstores',
            'klevu_simple_without_rating_without_reviewcount_store1',
            'klevu_simple_without_rating_without_reviewcount_store2',
            'klevu_simple_with_rating_with_reviewcount_globalscope',
        ]);

        $result = $provider->getProductIdsForStore((int)$store2->getId());
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result);
        } else {
            $this->assertTrue(is_array($result), 'Result is array');
        }

        $missingExpectedIds = array_diff($expectedProductIds, $result);
        $unexpectedReturnedIds = array_diff($result, $expectedProductIds);

        $this->assertEmpty(
            $missingExpectedIds,
            'Missing Expected Ids (Store 2): ' . json_encode($this->getSkusForProductIds($missingExpectedIds))
        );
        $this->assertEmpty(
            $unexpectedReturnedIds,
            'Unexpected Returned Ids (Store 2)): ' . json_encode($this->getSkusForProductIds($unexpectedReturnedIds))
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testGetProductIdsForStoreGlobalScope()
    {
        $this->setUpPhp5();

        /** @var MagentoProductsWithRatingAttributeDataProvider $provider */
        $provider = $this->objectManager->get(MagentoProductsWithRatingAttributeDataProvider::class);

        $store = $this->storeManager->getStore(Store::DEFAULT_STORE_ID);

        $expectedProductIds = $this->getProductIdsForSkus([
            'klevu_simple_with_rating_with_reviewcount_globalscope',
        ]);
        $notExpectedProductIds = $this->getProductIdsForSkus([
            'klevu_simple_with_rating_with_reviewcount_allstores',
            'klevu_simple_with_rating_with_reviewcount_store1',
            'klevu_simple_with_rating_without_reviewcount_allstores',
            'klevu_simple_with_rating_without_reviewcount_store1',
            'klevu_simple_without_rating_with_reviewcount_allstores',
            'klevu_simple_without_rating_with_reviewcount_store1',
            'klevu_simple_with_rating_with_reviewcount_store2',
            'klevu_simple_with_rating_without_reviewcount_store2',
            'klevu_simple_without_rating_with_reviewcount_store2',
            'klevu_simple_without_rating_without_reviewcount_allstores',
            'klevu_simple_without_rating_without_reviewcount_store1',
            'klevu_simple_without_rating_without_reviewcount_store2',
        ]);

        $result = $provider->getProductIdsForStore((int)$store->getId());
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result);
        } else {
            $this->assertTrue(is_array($result), 'Result is array');
        }

        $missingExpectedIds = array_diff($expectedProductIds, $result);
        $unexpectedReturnedIds = array_diff($result, $expectedProductIds);

        $this->assertEmpty(
            $missingExpectedIds,
            'Missing Expected Ids (Global Scope): ' . json_encode($this->getSkusForProductIds($missingExpectedIds))
        );
        $this->assertEmpty(
            $unexpectedReturnedIds,
            'Unexpected Returned Ids (Global Scope): ' . json_encode($this->getSkusForProductIds($unexpectedReturnedIds))
        );
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->productCollectionFactory = $this->objectManager->get(ProductCollectionFactory::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
    }

    private function getProductIdsForSkus(array $skus)
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToFilter('sku', ['in' => $skus]);

        return array_column($productCollection->getData(), 'entity_id');
    }

    private function getSkusForProductIds(array $productIds)
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToFilter('entity_id', ['in' => $productIds]);

        return array_column($productCollection->getData(), 'sku');
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../../../Service/Catalog/_files/productFixtures_ratingAttributes.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../../../Service/Catalog/_files/productFixtures_ratingAttributes_rollback.php';
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../../_files/websiteFixtures_rollback.php';
    }
}
