<?php

namespace Klevu\Search\Test\Integraton\Observer\Catalog\Product;

use Klevu\Search\Model\Attribute\Rating;
use Klevu\Search\Model\Attribute\ReviewCount;
use Klevu\Search\Model\Product\LoadAttribute;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class EditActionObserverTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @magentoAppArea adminhtml
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testRatingAttributeIsLocked()
    {
        $this->setupPhp5();

        $product = $this->getProduct('klevu_simple_1', 'klevu_test_store_1');
        $product->setData(Rating::ATTRIBUTE_CODE, 80.0);
        $product->setData(ReviewCount::ATTRIBUTE_CODE, 10);

        $this->dispatchEvent($product);

        $this->assertFalse($product->isLockedAttribute(Product::STATUS));
        $this->assertTrue($product->isLockedAttribute(Rating::ATTRIBUTE_CODE));
        $this->assertTrue($product->isLockedAttribute(ReviewCount::ATTRIBUTE_CODE));

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    private function dispatchEvent(ProductInterface $product)
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->objectManager->get(EventManager::class);

        $eventManager->dispatch(
            "catalog_product_edit_action",
            [
                'product' => $product,
            ]
        );
    }

    /**
     * @param string $sku
     * @param string $storeCode
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku, $storeCode)
    {
        $store = $this->getStore($storeCode);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);

        $product = $productRepository->get($sku);
        $loadProduct = $this->objectManager->create(LoadAttribute::class);

        $productCollection = $loadProduct->loadProductDataCollection(
            [$product->getId()],
            $store ? $store->getId() : null
        );

        return $productCollection->getItemById($product->getId());
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
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../../_files/productFixtures.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../../_files/productFixtures_rollback.php';
    }

    /**
     * Loads review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back review creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }
}
