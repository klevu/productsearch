<?php

namespace Klevu\Search\Test\Integration\Catalog\Product;

use Klevu\Search\Service\Catalog\Product\GetStockStatusById;
use Klevu\Search\Api\Service\Catalog\Product\GetStockStatusByIdInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\ModuleList;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetStockStatusByIdTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testImplementsGetStockStatusByIdInterface()
    {
        $this->setUpPhp5();
        $getKmcUrlService = $this->instantiateGetStockStatusByIdService();

        $this->assertInstanceOf(GetStockStatusByIdInterface::class, $getKmcUrlService);
    }

    public function testCanBeInstantiated()
    {
        $this->setUpPhp5();
        if ($this->isMsiModuleConfiguredAndEnabled()) {
            $this->markTestSkipped('MSI module is installed and preferences this interface');
        }

        $getKmcUrlService = $this->instantiateGetStockStatusByIdServiceInterface();

        $this->assertInstanceOf(GetStockStatusById::class, $getKmcUrlService);
    }

    public function testEmptyArrayWhenNoProductIdsAreProvided()
    {
        $this->setUpPhp5();
        if ($this->isMsiModuleConfiguredAndEnabled()) {
            $this->markTestSkipped('MSI module is installed and preferences this interface');
        }

        $getStockStatusById = $this->instantiateGetStockStatusByIdService();
        $stockStatus = $getStockStatusById->execute([]);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($stockStatus);
        } else {
            $this->assertTrue(is_array($stockStatus), 'Is Array');
        }
        $this->assertCount(0, $stockStatus);
    }

    /**
     * @magentoDbIsolation disabled
     */
    public function testEmptyArrayIsReturnedWhenProductIdsDoNotExist()
    {
        $this->setUpPhp5();
        if ($this->isMsiModuleConfiguredAndEnabled()) {
            $this->markTestSkipped('MSI module is installed and preferences this interface');
        }

        $getStockStatusById = $this->instantiateGetStockStatusByIdService();
        $stockStatus = $getStockStatusById->execute([9999999999999]);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($stockStatus);
        } else {
            $this->assertTrue(is_array($stockStatus), 'Is Array');
        }
        $this->assertCount(0, $stockStatus);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testReturnsArrayWhenProductsAreSaleable()
    {
        $this->setUpPhp5();
        if ($this->isMsiModuleConfiguredAndEnabled()) {
            $this->markTestSkipped('MSI module is installed and preferences this interface');
        }

        $product1 = $this->getProduct('klevu_simple_1');
        $product2 = $this->getProduct('klevu_simple_2');

        $getStockStatusById = $this->instantiateGetStockStatusByIdService();
        $stockStatus = $getStockStatusById->execute([$product1->getId(), $product2->getId()]);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($stockStatus);
        } else {
            $this->assertTrue(is_array($stockStatus), 'Is Array');
        }
        $this->assertCount(2, $stockStatus);

        $this->assertArrayHasKey($product1->getId(), $stockStatus);
        $this->assertTrue($stockStatus[$product1->getId()]);

        $this->assertArrayHasKey($product2->getId(), $stockStatus);
        $this->assertFalse($stockStatus[$product2->getId()]);

        static::loadProductFixturesRollback();
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
     * @return GetStockStatusByIdInterface
     */
    private function instantiateGetStockStatusByIdService()
    {
        return $this->objectManager->create(GetStockStatusById::class);
    }

    /**
     * @return GetStockStatusByIdInterface
     */
    private function instantiateGetStockStatusByIdServiceInterface()
    {
        return $this->objectManager->create(GetStockStatusByIdInterface::class);
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
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku)
    {
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        return $productRepository->get($sku);
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../_files/productFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../_files/productFixtures_rollback.php';
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }
}
