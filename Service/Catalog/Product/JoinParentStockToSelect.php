<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\JoinParentEntityToSelectInterface;
use Klevu\Search\Api\Service\Catalog\Product\JoinParentStockToSelectInterface;
use Klevu\Search\Repository\MagentoProductSyncRepository;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class JoinParentStockToSelect implements JoinParentStockToSelectInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var JoinParentEntityToSelectInterface
     */
    private $joinParentEntityToSelect;

    /**
     * @param ResourceConnection $resourceConnection
     * @param JoinParentEntityToSelectInterface $joinParentEntityToSelect
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        JoinParentEntityToSelectInterface $joinParentEntityToSelect
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->joinParentEntityToSelect = $joinParentEntityToSelect;
    }

    /**
     * @param Select $select
     * @param int $storeId // only used in the MSI implementation
     * @param bool $includeOosProducts
     * @param bool $returnStock
     * @param bool $joinParentEntity
     *
     * @return Select
     */
    public function execute(
        Select $select,
        $storeId,
        $includeOosProducts = true,
        $returnStock = false,
        $joinParentEntity = true
    ) {
        $isJoinRequired = $this->isJoinRequired($select, $includeOosProducts, $returnStock);
        if ($isJoinRequired && $joinParentEntity) {
            $select = $this->joinParentEntityToSelect->execute($select);
        }

        return $isJoinRequired
            ? $this->joinParentStock($select, $includeOosProducts, $returnStock)
            : $select;
    }

    /**
     * @param Select $select
     * @param bool $includeOosProducts
     * @param bool $returnStock
     *
     * @return bool
     */
    private function isJoinRequired(Select $select, $includeOosProducts, $returnStock)
    {
        $isParentStockJoined = $this->isParentStockJoined($select);

        return !$isParentStockJoined && (!$includeOosProducts || $returnStock);
    }

    /**
     * @param Select $select
     * @param bool $includeOosProducts
     * @param bool $returnStock
     *
     * @return Select
     */
    private function joinParentStock(Select $select, $includeOosProducts, $returnStock)
    {
        $stockStatusTable = $this->resourceConnection->getTableName('cataloginventory_stock_status');
        $joinType = $includeOosProducts
            ? 'joinLeft'
            : 'joinInner';
        $columns = $returnStock
            ? ['stock_status' => MagentoProductSyncRepository::PARENT_STOCK_STATUS_ALIAS . '.stock_status']
            : [];

        $select->{$joinType}(
            [MagentoProductSyncRepository::PARENT_STOCK_STATUS_ALIAS => $stockStatusTable],
            sprintf(
                '%s.product_id = %s.entity_id',
                MagentoProductSyncRepository::PARENT_STOCK_STATUS_ALIAS,
                MagentoProductSyncRepository::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS
            ),
            $columns
        );

        return $this->filterParentStock($select, $includeOosProducts);
    }

    /**
     * @param Select $select
     * @param bool $includeOosProducts
     *
     * @return Select
     */
    private function filterParentStock(Select $select, $includeOosProducts)
    {
        return $includeOosProducts
            ? $select
            : $select->where(
                MagentoProductSyncRepository::PARENT_STOCK_STATUS_ALIAS . '.stock_status = ?',
                1 // StockStatusInterface::STATUS_IN_STOCK this constant does not exist in Magento 2.1
            );
    }

    /**
     * @param Select $select
     *
     * @return bool
     */
    private function isParentStockJoined(Select $select)
    {
        try {
            $selectFrom = $select->getPart(Select::FROM);
        } catch (\Zend_Db_Select_Exception $e) {
            return false;
        }
        $stockTable = $this->resourceConnection->getTableName('cataloginventory_stock_status');
        $joinCondition = sprintf(
            '%s.product_id = %s.entity_id',
            MagentoProductSyncRepository::PARENT_STOCK_STATUS_ALIAS,
            MagentoProductSyncRepository::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS
        );
        $matches = array_filter($selectFrom, static function (array $from) use ($stockTable, $joinCondition) {
            return isset($from['tableName'], $from['joinType'], $from['joinCondition'])
                && (false !== strpos($from['tableName'], $stockTable))
                && in_array($from['joinType'], [Select::INNER_JOIN, Select::LEFT_JOIN], true)
                && $from['joinCondition'] === $joinCondition;
        });

        return (bool)count($matches);
    }
}
