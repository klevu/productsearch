<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Provider\Eav\Attribute;

use Klevu\Search\Api\Provider\Eav\Attribute\ProductAttributeCollectionProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\TypeFactory as EntityTypeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as EntityAttributeCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as EntityAttributeCollectionFactory;

class ProductAttributeCollectionProvider implements ProductAttributeCollectionProviderInterface
{
    /**
     * @var EntityAttributeCollectionFactory
     */
    private $attributeCollectionFactory;
    /**
     * @var EntityTypeFactory
     */
    private $entityTypeFactory;

    /**
     * @param EntityAttributeCollectionFactory $attributeCollectionFactory
     * @param EntityTypeFactory $entityTypeFactory
     */
    public function __construct(
        EntityAttributeCollectionFactory $attributeCollectionFactory,
        EntityTypeFactory $entityTypeFactory
    ) {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->entityTypeFactory = $entityTypeFactory;
    }

    /**
     * @return EntityAttributeCollection
     */
    public function getCollection()
    {
        $entityType = $this->entityTypeFactory->create();
        $entityType = $entityType->loadByCode(Product::ENTITY);
        $attributeCollection = $this->attributeCollectionFactory->create();
        $attributeCollection->setEntityTypeFilter($entityType);

        return $attributeCollection;
    }
}
