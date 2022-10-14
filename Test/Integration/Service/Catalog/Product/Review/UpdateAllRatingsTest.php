<?php

namespace Klevu\Search\Test\Integration\Service\Catalog\Product\Review;

use Klevu\Search\Api\Service\Catalog\Product\Review\UpdateAllRatingsInterface;
use Klevu\Search\Model\Attribute\Rating as RatingAttribute;
use Klevu\Search\Model\Product\LoadAttribute;
use Klevu\Search\Service\Catalog\Product\Review\UpdateAllRatings;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class UpdateAllRatingsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testUpdateAllRatingsImplementsUpdateRatingInterface()
    {
        $this->setUpPhp5();

        $updateAllRatings = $this->instantiateUpdateAllRatingsService();

        $this->assertInstanceOf(UpdateAllRatingsInterface::class, $updateAllRatings);
    }

    public function testUpdateRatingInterfaceReturnsUpdateRating()
    {
        $this->setUpPhp5();

        $updateAllRatings = $this->objectManager->create(UpdateAllRatingsInterface::class);

        $this->assertInstanceOf(UpdateAllRatings::class, $updateAllRatings);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadReviewFixtures
     */
    public function testReturnsExpectedValue()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1', $store);
        $product->setData(RatingAttribute::ATTRIBUTE_CODE, 0);
        $product->save();

        $updateAllRatings = $this->instantiateUpdateAllRatingsService();
        $updateAllRatings->execute((int)$store->getId());

        $updatedProduct = $this->getProduct('klevu_simple_1', $store);
        $expectedRating = '80';
        $this->assertSame($expectedRating, $updatedProduct->getData(RatingAttribute::ATTRIBUTE_CODE));

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
     * @return UpdateAllRatings
     */
    private function instantiateUpdateAllRatingsService()
    {
        return $this->objectManager->create(UpdateAllRatings::class);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode)
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->create(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @param string $sku
     * @param StoreInterface|null $store
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku, $store = null)
    {
        if (null === $store) {
            $store = $this->getStore('klevu_test_store_1');
        }
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);

        $product = $productRepository->get($sku);
        $loadProduct = $this->objectManager->create(LoadAttribute::class);

        $productCollection = $loadProduct->loadProductDataCollection(
            [$product->getId()],
            $store->getId()
        );

        return $productCollection->getItemById($product->getId());
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadReviewFixtures()
    {
        include __DIR__ . '/../../_files/reviewFixturesWithRating.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadReviewFixturesRollback()
    {
        include __DIR__ . '/../../_files/reviewFixturesWithRating_rollback.php';
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../_files/productFixtures.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../_files/productFixtures_rollback.php';
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
