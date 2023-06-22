<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\JoinSuperLinkToSelectInterface;
use Klevu\Search\Repository\MagentoProductSyncRepository;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class JoinSuperLinkToSelect implements JoinSuperLinkToSelectInterface
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
     * @param Select $select
     *
     * @return Select
     */
    public function execute(Select $select)
    {
        return $this->isCollectionJoinedToSuperLink($select)
            ? $select
            : $this->joinSuperLinkTable($select);
    }

    /**
     * @param Select $select
     *
     * @return bool
     */
    private function isCollectionJoinedToSuperLink(Select $select)
    {
        try {
            $selectFrom = $select->getPart(Select::FROM);
        } catch (\Zend_Db_Select_Exception $e) {
            return false;
        }
        $linkTable = $this->resourceConnection->getTableName('catalog_product_super_link');
        $matches = array_filter($selectFrom, static function (array $from, $key) use ($linkTable) {
            return isset($from['tableName'], $from['joinType'], $from['joinCondition'])
                && (false !== strpos($from['tableName'], $linkTable))
                && $from['joinType'] === Select::INNER_JOIN
                && $from['joinCondition'] === 'e.entity_id = ' . $key . '.product_id';
        }, ARRAY_FILTER_USE_BOTH);

        return (bool)count($matches);
    }

    /**
     * @param Select $select
     *
     * @return Select
     */
    private function joinSuperLinkTable(Select $select)
    {
        $linkTable = $this->resourceConnection->getTableName('catalog_product_super_link');

        $select->joinInner(
            [MagentoProductSyncRepository::CATALOG_PRODUCT_SUPER_LINK_ALIAS => $linkTable],
            sprintf(
                '%s.entity_id = %s.product_id',
                MagentoProductSyncRepository::CATALOG_PRODUCT_ENTITY_ALIAS,
                MagentoProductSyncRepository::CATALOG_PRODUCT_SUPER_LINK_ALIAS
            ),
            []
        );

        return $select;
    }
}
