<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Provider\CatalogRule;

use Klevu\Search\Api\Provider\CatalogRule\GetProductIdsProviderInterface;
use Magento\Framework\App\ResourceConnection;

class GetProductIdsProvider implements GetProductIdsProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return int[]
     */
    public function get()
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from(
            $this->resourceConnection->getTableName('catalogrule_product'),
            'product_id'
        );
        $select->distinct();

        return array_map(static function ($id) {
            return (int)$id;
        }, $connection->fetchCol($select));
    }
}
