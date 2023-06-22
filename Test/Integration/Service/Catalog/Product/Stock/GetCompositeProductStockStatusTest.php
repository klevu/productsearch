<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Service\Catalog\Product\Stock;

use Klevu\Search\Api\Service\Catalog\Product\Stock\GetCompositeProductStockStatusInterface;
use Klevu\Search\Service\Catalog\Product\Stock\GetCompositeProductStockStatus;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetCompositeProductStockStatusTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function testImplements_GetCompositeProductStockStatusInterface()
    {
        $this->setUpPhp5();

        $this->assertInstanceOf(
            GetCompositeProductStockStatusInterface::class,
            $this->instantiateGetStockIdForWebsite()
        );
    }

    public function testPreference_ForGetStockIdForWebsiteInterface()
    {
        $this->setUpPhp5();

        if ($this->isMsiModuleConfiguredAndEnabled()) {
            $this->markTestSkipped('MSI module is installed and preferences this interface');
        }

        $this->assertInstanceOf(
            GetCompositeProductStockStatus::class,
            $this->objectManager->create(GetCompositeProductStockStatusInterface::class)
        );
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testExecute_DefaultScope()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore($store);

        $product = $this->getProduct('klevu_configurable_synctest_instock_childreninstock', $store->getId());
        $service = $this->instantiateGetStockIdForWebsite();
        $isInStock = $service->execute($product, [], Stock::DEFAULT_STOCK_ID);
        $this->assertTrue($isInStock, 'Configurable Product In Stock, Children In Stock');

        $product = $this->getProduct('klevu_configurable_synctest_instock_childrenoos', $store->getId());
        $service = $this->instantiateGetStockIdForWebsite();
        $isInStock = $service->execute($product, [], Stock::DEFAULT_STOCK_ID);
        $this->assertFalse($isInStock, 'Configurable Product In Stock, Children Out Of Stock');

        $product = $this->getProduct('klevu_configurable_synctest_oos_childreninstock', $store->getId());
        $service = $this->instantiateGetStockIdForWebsite();
        $isInStock = $service->execute($product, [], Stock::DEFAULT_STOCK_ID);
        $this->assertFalse($isInStock, 'Configurable Product Out Of Stock, Children In Stock');

        $product = $this->getProduct('klevu_bundle_synctest_instock_childreninstock', $store->getId());
        $service = $this->instantiateGetStockIdForWebsite();
        $isInStock = $service->execute($product, [], Stock::DEFAULT_STOCK_ID);
        $this->assertTrue($isInStock, 'Bundle Product In Stock, Children In Stock');

        $product = $this->getProduct('klevu_bundle_synctest_instock_childrenoos', $store->getId());
        $service = $this->instantiateGetStockIdForWebsite();
        $isInStock = $service->execute($product, [], Stock::DEFAULT_STOCK_ID);
        $this->assertFalse($isInStock, 'Bundle Product In Stock, Children Out Of Stock');

        $product = $this->getProduct('klevu_bundle_synctest_oos_childreninstock', $store->getId());
        $service = $this->instantiateGetStockIdForWebsite();
        $isInStock = $service->execute($product, [], Stock::DEFAULT_STOCK_ID);
        $this->assertFalse($isInStock, 'Bundle Product Out Of Stock, Children In Stock');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @param $arguments
     *
     * @return GetCompositeProductStockStatus
     */
    private function instantiateGetStockIdForWebsite($arguments = [])
    {
        return $this->objectManager->create(GetCompositeProductStockStatus::class, $arguments);
    }

    /**
     * @return bool
     */
    private function isMsiModuleConfiguredAndEnabled()
    {
        $moduleList = $this->objectManager->create(ModuleList::class);
        $moduleName = 'Klevu_Msi';

        return $moduleList->has($moduleName);
    }

    /**
     * @param string $sku
     * @param int $storeId
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku, $storeId)
    {
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        return $productRepository->get($sku, false, $storeId);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode)
    {
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/_files/productFixtures.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/_files/productFixtures_rollback.php';
    }

    /**
     * Loads store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../../_files/websiteFixtures_rollback.php';
    }
}
