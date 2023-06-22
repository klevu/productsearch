<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\AddParentProductStockToCollectionInterface;
use Klevu\Search\Service\Catalog\Product\AddParentProductStockToCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\ModuleList;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class AddParentProductStockToCollectionTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testImplements_AddParentProductStockToCollectionInterface()
    {
        $this->setUpPhp5();
        $this->assertInstanceOf(
            AddParentProductStockToCollectionInterface::class,
            $this->instantiateAddParentProductStockToCollectionService()
        );
    }

    public function testPreference_ForAddParentProductStockToCollectionInterface()
    {
        $this->setUpPhp5();

        $this->assertInstanceOf(
            AddParentProductStockToCollection::class,
            $this->objectManager->create(AddParentProductStockToCollectionInterface::class)
        );
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testCollection_IsNotChanged_IncludeOosProductsTrue()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $store = $this->getStore('klevu_test_store_1');
        $includeOosProducts = true;
        $expectedSelect = $collection->getSelect()->__toString();

        $service = $this->instantiateAddParentProductStockToCollectionService();
        $newCollection = $service->execute($collection, $store, $includeOosProducts);

        $actualSelect = $newCollection->getSelect()->__toString();

        $this->assertSame($expectedSelect, $actualSelect);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testCollection_IsChanged_IncludeOosProductsFalse()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $store = $this->getStore('klevu_test_store_1');
        $includeOosProducts = false;

        $originalSelect = $collection->getSelect()->__toString();

        $service = $this->instantiateAddParentProductStockToCollectionService();
        $newCollection = $service->execute($collection, $store, $includeOosProducts);

        $updatedSelect = $newCollection->getSelect()->__toString();

        $this->assertNotSame($originalSelect, $updatedSelect);

        $pattern = '#INNER JOIN `.*catalog_product_super_link` AS `l` ON e.entity_id = l.product_id#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Super Link');

        $pattern = $this->isOpenSourceEdition()
            ? '#INNER JOIN `.*catalog_product_entity` AS `parent` ON parent.`entity_id` = l.parent_id#'
            : '#INNER JOIN `.*catalog_product_entity` AS `parent` ON parent.`row_id` = l.parent_id AND \(parent.created_in <= \d* AND parent.updated_in > \d*\)#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Parent Entity');

        if ($this->isMsiModuleConfiguredAndEnabled()) {
            $pattern = $this->isOpenSourceEdition()
                ? '#INNER JOIN `.*cataloginventory_stock_status` AS `parent_stock_status_index` ON parent.entity_id = parent_stock_status_index.product_id WHERE \(parent_stock_status_index.stock_status = 1\)#'
                : '#INNER JOIN `.*cataloginventory_stock_status` AS `parent_stock_status_index` ON parent.entity_id = parent_stock_status_index.product_id WHERE \(e.created_in <= \d*\) AND \(e.updated_in > \d*\) AND \(parent_stock_status_index.stock_status = 1\)#';
        } else {
            $pattern = $this->isOpenSourceEdition()
                ? '#INNER JOIN `.*cataloginventory_stock_status` AS `parent_stock_status` ON parent_stock_status.product_id = parent.entity_id WHERE \(parent_stock_status.stock_status = 1\)#'
                : '#INNER JOIN `.*cataloginventory_stock_status` AS `parent_stock_status` ON parent_stock_status.product_id = parent.entity_id WHERE \(e.created_in <= \d*\) AND \(e.updated_in > \d*\) AND \(parent_stock_status.stock_status = 1\)#';
        }
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Parent Stock');

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
     * @return bool
     */
    private function isOpenSourceEdition()
    {
        $productMetadata = $this->objectManager->create(ProductMetadataInterface::class);
        $edition = $productMetadata->getEdition();

        return $edition === ProductMetadata::EDITION_NAME;
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
     * @param mixed[]|null $arguments
     *
     * @return AddParentProductStockToCollection
     */
    private function instantiateAddParentProductStockToCollectionService(array $arguments = [])
    {
        return $this->objectManager->create(
            AddParentProductStockToCollection::class,
            $arguments
        );
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore(string $storeCode): StoreInterface
    {
        $storeRepository = $this->objectManager->create(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures(): void
    {
        include __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback(): void
    {
        include __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }
}
