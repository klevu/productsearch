<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\JoinSuperLinkToSelectInterface;
use Klevu\Search\Service\Catalog\Product\JoinSuperLinkToSelect;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class JoinSuperLinkToSelectTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;


    public function testImplements_JoinSuperLinkToSelectInterface()
    {
        $this->setUpPhp5();
        $this->assertInstanceOf(
            JoinSuperLinkToSelectInterface::class,
            $this->instantiateJoinSuperLinkToSelectService()
        );
    }

    public function testPreference_JoinSuperLinkToSelectInterface()
    {
        $this->setUpPhp5();

        $this->assertInstanceOf(
            JoinSuperLinkToSelect::class,
            $this->objectManager->create(JoinSuperLinkToSelectInterface::class)
        );
    }

    public function testSuperLink_IsJoined_WhenMissing()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $select = $collection->getSelect();
        $originalSelect = $select->__toString();

        $service = $this->instantiateJoinSuperLinkToSelectService();
        $newSelect = $service->execute($select);

        $updatedSelect = $newSelect->__toString();

        $this->assertNotSame($originalSelect, $updatedSelect);

        $pattern = '#INNER JOIN `.*catalog_product_super_link` AS `l` ON e.entity_id = l.product_id#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Super Link');
    }

    public function testSuperLink_IsNotJoined_WhenPresent()
    {
        $this->setUpPhp5();

        $collection = $this->objectManager->create(ProductCollection::class);
        $select = $collection->getSelect();
        $service = $this->instantiateJoinSuperLinkToSelectService();
        $select = $service->execute($select);

        $originalSelect = $select->__toString();

        $service = $this->instantiateJoinSuperLinkToSelectService();
        $newSelect = $service->execute($select);

        $updatedSelect = $newSelect->__toString();

        $this->assertSame($originalSelect, $updatedSelect);

        $pattern = '#INNER JOIN `.*catalog_product_super_link` AS `l` ON e.entity_id = l.product_id#';
        $matches = [];
        preg_match($pattern, $updatedSelect, $matches);
        $this->assertCount(1, $matches, 'Join Super Link');
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
     * @param mixed[] $arguments
     *
     * @return JoinSuperLinkToSelect
     */
    private function instantiateJoinSuperLinkToSelectService(array $arguments = [])
    {
        return $this->objectManager->create(
            JoinSuperLinkToSelect::class,
            $arguments
        );
    }
}
