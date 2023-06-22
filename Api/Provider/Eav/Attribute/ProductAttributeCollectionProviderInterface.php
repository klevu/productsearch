<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Api\Provider\Eav\Attribute;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as EntityAttributeCollection;

interface ProductAttributeCollectionProviderInterface
{
    /**
     * @return EntityAttributeCollection
     */
    public function getCollection();
}
