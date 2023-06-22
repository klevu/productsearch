<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\JoinParentStockToSelectInterface;
use Klevu\Search\Service\Catalog\Product\JoinParentStockToSelect;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleList;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class JoinParentStockToSelectTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testImplements_JoinParentStockToSelectInterface()
    {
        $this->setUpPhp5();
        $this->assertInstanceOf(
            JoinParentStockToSelectInterface::class,
            $this->instantiateJoinParentStockToSelectService()
        );
    }

    public function testPreference_ForAddParentProductStockToCollectionInterface()
    {
        $this->setUpPhp5();
        if ($this->isMsiModuleConfiguredAndEnabled()) {
            $this->markTestSkipped('MSI module is installed and preferences this interface');
        }

        $this->assertInstanceOf(
            JoinParentStockToSelect::class,
            $this->objectManager->create(JoinParentStockToSelectInterface::class)
        );
    }

    public function testExecute_ReturnsOriginalSelect_ForExcludeOutOfStock_ReturnStockFalse()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $storeId = 1;
        $select = $collection->getSelect();
        $expectedSelect = $select->__toString();

        $service = $this->instantiateJoinParentStockToSelectService();
        $newSelect = $service->execute($select, $storeId, false);

        $updatedSelect = $newSelect->__toString();

        $this->commonAssertions($expectedSelect, $updatedSelect, 1);

        $pattern = $this->isOpenSourceEdition()
            ? '#INNER JOIN `.*cataloginventory_stock_status` AS `parent_stock_status` ON parent_stock_status.product_id = parent.entity_id WHERE \(parent_stock_status.stock_status = 1\)#'
            : '#INNER JOIN `.*cataloginventory_stock_status` AS `parent_stock_status` ON parent_stock_status.product_id = parent.entity_id WHERE \(e.created_in <= \d*\) AND \(e.updated_in > \d*\) AND \(parent_stock_status.stock_status = 1\)#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Parent Stock');

        $pattern = '#SELECT.*`parent_stock_status`.`stock_status`.*FROM#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(0, $matches, 'Join Parent Stock Column Not Present');
    }

    public function testExecute_ReturnsSelect_ForExcludeOutOfStock_ReturnStockTrue()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $storeId = 1;
        $select = $collection->getSelect();
        $expectedSelect = $select->__toString();

        $service = $this->instantiateJoinParentStockToSelectService();
        $newSelect = $service->execute($select, $storeId, false, true);

        $updatedSelect = $newSelect->__toString();

        $this->commonAssertions($expectedSelect, $updatedSelect, 1);

        $pattern = $this->isOpenSourceEdition()
            ? '#INNER JOIN `.*cataloginventory_stock_status` AS `parent_stock_status` ON parent_stock_status.product_id = parent.entity_id WHERE \(parent_stock_status.stock_status = 1\)#'
            : '#INNER JOIN `.*cataloginventory_stock_status` AS `parent_stock_status` ON parent_stock_status.product_id = parent.entity_id WHERE \(e.created_in <= \d*\) AND \(e.updated_in > \d*\) AND \(parent_stock_status.stock_status = 1\)#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Parent Stock');

        $pattern = '#SELECT.*`parent_stock_status`.`stock_status`.*FROM#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Parent Stock Column');
    }

    public function testExecute_ReturnsSelect_ForExcludeOutOfStock_ReturnStockTrue_JoinParentEntityFalse()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $storeId = 1;
        $select = $collection->getSelect();
        $expectedSelect = $select->__toString();

        $service = $this->instantiateJoinParentStockToSelectService();
        $newSelect = $service->execute($select, $storeId, false, true, false);

        $updatedSelect = $newSelect->__toString();

        $this->commonAssertions($expectedSelect, $updatedSelect, 0);

        $pattern = $this->isOpenSourceEdition()
            ? '#INNER JOIN `.*cataloginventory_stock_status` AS `parent_stock_status` ON parent_stock_status.product_id = parent.entity_id WHERE \(parent_stock_status.stock_status = 1\)#'
            : '#INNER JOIN `.*cataloginventory_stock_status` AS `parent_stock_status` ON parent_stock_status.product_id = parent.entity_id WHERE \(e.created_in <= \d*\) AND \(e.updated_in > \d*\) AND \(parent_stock_status.stock_status = 1\)#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Parent Stock');

        $pattern = '#SELECT.*`parent_stock_status`.`stock_status`.*FROM#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Parent Stock Column');
    }

    public function testExecute_ReturnsSelect_ForIncludeOutOfStock()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $storeId = 1;
        $select = $collection->getSelect();
        $expectedSelect = $select->__toString();

        $service = $this->instantiateJoinParentStockToSelectService();
        $newSelect = $service->execute($select, $storeId, true);

        $updatedSelect = $newSelect->__toString();

        $this->assertSame($expectedSelect, $updatedSelect);
    }

    public function testExecute_ReturnsSelect_ForIncludeOutOfStock_ReturnStockTrue()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $storeId = 1;
        $select = $collection->getSelect();
        $expectedSelect = $select->__toString();

        $service = $this->instantiateJoinParentStockToSelectService();
        $newSelect = $service->execute($select, $storeId, true, true);

        $updatedSelect = $newSelect->__toString();

        $this->commonAssertions($expectedSelect, $updatedSelect, 1);

        $pattern = $this->isOpenSourceEdition()
            ? '#LEFT JOIN `.*cataloginventory_stock_status` AS `parent_stock_status` ON parent_stock_status.product_id = parent.entity_id#'
            : '#LEFT JOIN `.*cataloginventory_stock_status` AS `parent_stock_status` ON parent_stock_status.product_id = parent.entity_id WHERE \(e.created_in <= \d*\) AND \(e.updated_in > \d*\)#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Parent Stock');

        $pattern = '#SELECT.*`parent_stock_status`.`stock_status`.*FROM#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Parent Stock Column Not Present');
    }

    public function testExecute_ReturnsSelect_ForIncludeOutOfStock_ReturnStockTrue_JoinParentEntityFalse()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $storeId = 1;
        $select = $collection->getSelect();
        $expectedSelect = $select->__toString();

        $service = $this->instantiateJoinParentStockToSelectService();
        $newSelect = $service->execute($select, $storeId, true, true, false);

        $updatedSelect = $newSelect->__toString();

        $this->commonAssertions($expectedSelect, $updatedSelect, 0);

        $pattern = $this->isOpenSourceEdition()
            ? '#LEFT JOIN `.*cataloginventory_stock_status` AS `parent_stock_status` ON parent_stock_status.product_id = parent.entity_id#'
            : '#LEFT JOIN `.*cataloginventory_stock_status` AS `parent_stock_status` ON parent_stock_status.product_id = parent.entity_id WHERE \(e.created_in <= \d*\) AND \(e.updated_in > \d*\)#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Parent Stock');

        $pattern = '#SELECT.*`parent_stock_status`.`stock_status`.*FROM#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Parent Stock Column Not Present');
    }

    public function testSelect_ParentStockIsNotAddedTwice()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $storeId = 1;
        $select = $collection->getSelect();

        $service = $this->instantiateJoinParentStockToSelectService();
        $select = $service->execute($select, $storeId, false);
        $originalSelect = $select->__toString();

        $newSelect = $service->execute($select, $storeId, false);

        $updatedSelect = $newSelect->__toString();

        $this->assertSame($originalSelect, $updatedSelect);
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
    private function isMsiModuleConfiguredAndEnabled()
    {
        $moduleList = $this->objectManager->create(ModuleList::class);
        $moduleName = 'Klevu_Msi';

        return $moduleList->has($moduleName);
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
     * @param mixed[]|null $arguments
     *
     * @return JoinParentStockToSelect
     */
    private function instantiateJoinParentStockToSelectService(array $arguments = [])
    {
        return $this->objectManager->create(
            JoinParentStockToSelect::class,
            $arguments
        );
    }

    /**
     * @param string $expectedSelect
     * @param string $updatedSelect
     * @param int $count
     *
     * @return void
    */
    private function commonAssertions(string $expectedSelect, string $updatedSelect, $count): void
    {
        $this->assertNotSame($expectedSelect, $updatedSelect);

        $pattern = '#INNER JOIN `.*catalog_product_super_link` AS `l` ON e.entity_id = l.product_id#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount($count, $matches, 'Join Super Link');

        $pattern = $this->isOpenSourceEdition()
            ? '#INNER JOIN `.*catalog_product_entity` AS `parent` ON parent.`entity_id` = l.parent_id#'
            : '#INNER JOIN `.*catalog_product_entity` AS `parent` ON parent.`row_id` = l.parent_id AND \(parent.created_in <= \d* AND parent.updated_in > \d*\)#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount($count, $matches, 'Join Parent Entity');
    }
}
