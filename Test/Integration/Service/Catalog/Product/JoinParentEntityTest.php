<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\JoinParentEntityToSelectInterface;
use Klevu\Search\Service\Catalog\Product\JoinParentEntityToSelect;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class JoinParentEntityTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testImplements_JoinParentEntityToSelectInterface()
    {
        $this->setUpPhp5();
        $this->assertInstanceOf(
            JoinParentEntityToSelectInterface::class,
            $this->instantiateJoinParentEntitySelectService()
        );
    }

    public function testPreferenceFor_JoinParentEntityToSelectInterface()
    {
        $this->setUpPhp5();
        $this->assertInstanceOf(
            JoinParentEntityToSelect::class,
            $this->objectManager->create(JoinParentEntityToSelectInterface::class)
        );
    }

    public function testExecute_AddsParentEntityToSelect()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $select = $collection->getSelect();
        $originalSelect = $select->__toString();

        $service = $this->instantiateJoinParentEntitySelectService();
        $newSelect = $service->execute($select);

        $updatedSelect = $newSelect->__toString();

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
    }

    public function testExecute_DoesNotAddEntityTwice()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $select = $collection->getSelect();

        $service = $this->instantiateJoinParentEntitySelectService();
        $select = $service->execute($select);
        $originalSelect = $select->__toString();

        $service = $this->instantiateJoinParentEntitySelectService();
        $newSelect = $service->execute($select);

        $updatedSelect = $newSelect->__toString();

        $this->assertSame($originalSelect, $updatedSelect);

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
     * @param mixed[]|null $arguments
     *
     * @return JoinParentEntityToSelect
     */
    private function instantiateJoinParentEntitySelectService(array $arguments = [])
    {
        return $this->objectManager->create(
            JoinParentEntityToSelect::class,
            $arguments
        );
    }
}
