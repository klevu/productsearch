<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Test\Integration\Provider\CatalogRule;

use Klevu\Search\Api\Provider\CatalogRule\GetProductIdsProviderInterface;
use Klevu\Search\Provider\CatalogRule\GetProductIdsProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogRule\Model\Indexer\IndexBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetProductIdsProviderTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testImplements_GetProductIdsProviderInterface()
    {
        $this->setUpPhp5();

        $this->assertInstanceOf(
            GetProductIdsProviderInterface::class,
            $this->instantiateGetProductIdsProvider()
        );
    }

    public function testPreference_GetProductIdsProviderInterface()
    {
        $this->setUpPhp5();

        $this->assertInstanceOf(
            GetProductIdsProvider::class,
            $this->objectManager->get(GetProductIdsProviderInterface::class)
        );
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @magentoDataFixtureBeforeTransaction loadAttributeFixtures
     * @magentoDataFixtureBeforeTransaction loadCatalogRuleByAttributeFixtures
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testGet_ReturnsProductIds()
    {
        $this->setUpPhp5();

        $product1 = $this->getProduct('klevu_simple_1');
        $indexerBuilder = Bootstrap::getObjectManager()->get(IndexBuilder::class);
        $indexerBuilder->reindexById((int)$product1->getId());

        $provider = $this->instantiateGetProductIdsProvider();
        $productIds = $provider->get();

        $this->assertContains((int)$product1->getId(), $productIds);

        self::loadCatalogRuleByAttributeFixturesRollback();
        self::loadAttributeFixturesRollback();
        self::loadWebsiteFixturesRollback();
    }

    /**
     * @return GetProductIdsProvider
     */
    private function instantiateGetProductIdsProvider()
    {
        return $this->objectManager->get(GetProductIdsProvider::class);
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
        $product = $productRepository->get($sku);
        $product->setData('klevu_test_attribute', 'test_attribute_value')->save();

        return $productRepository->save($product);
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
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../_files/productFixtures.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../_files/productFixtures_rollback.php';
    }

    /**
     * Loads attribute creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadAttributeFixtures()
    {
        include __DIR__ . '/_files/attributeFixtures.php';
    }

    /**
     * Rolls back attribute creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadAttributeFixturesRollback()
    {
        include __DIR__ . '/_files/attributeFixtures_rollback.php';
    }

    /**
     * Loads catalog rule creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadCatalogRuleByAttributeFixtures()
    {
        include __DIR__ . '/_files/ruleByAttributeFixtures.php';
    }

    /**
     * Rolls back catalog rule creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadCatalogRuleByAttributeFixturesRollback()
    {
        include __DIR__ . '/_files/ruleByAttributeFixtures_rollback.php';
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }
}
