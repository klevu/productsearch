<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\JoinSuperLinkToSelectInterface;
use Klevu\Search\Api\Service\Catalog\Product\JoinParentEntityToSelectInterface;
use Klevu\Search\Repository\MagentoProductSyncRepository;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class JoinParentEntityToSelect implements JoinParentEntityToSelectInterface
{
    /**
     * @var OptionProvider
     */
    private $optionProvider;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var JoinSuperLinkToSelectInterface
     */
    private $joinSuperLinkToSelect;

    /**
     * @param OptionProvider $optionProvider
     * @param ResourceConnection $resourceConnection
     * @param JoinSuperLinkToSelectInterface $joinSuperLinkToSelect
     */
    public function __construct(
        OptionProvider $optionProvider,
        ResourceConnection $resourceConnection,
        JoinSuperLinkToSelectInterface $joinSuperLinkToSelect
    ) {
        $this->optionProvider = $optionProvider;
        $this->resourceConnection = $resourceConnection;
        $this->joinSuperLinkToSelect = $joinSuperLinkToSelect;
    }

    /**
     * @param Select $select
     *
     * @return Select
     */
    public function execute(Select $select)
    {
        $select = $this->joinSuperLinkToSelect->execute($select);

        return $this->isParentEntityJoined($select)
            ? $select
            : $this->joinParentEntity($select);
    }

    /**
     * @param Select $select
     *
     * @return Select
     */
    private function joinParentEntity(Select $select)
    {
        $connection = $select->getConnection();
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $select->joinInner(
            [MagentoProductSyncRepository::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS => $productTable],
            sprintf(
                '%s.%s = %s.parent_id',
                MagentoProductSyncRepository::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS,
                $connection->quoteIdentifier($this->optionProvider->getProductEntityLinkField()),
                MagentoProductSyncRepository::CATALOG_PRODUCT_SUPER_LINK_ALIAS
            ),
            []
        );

        return $select;
    }

    /**
     * @param Select $select
     *
     * @return bool
     */
    private function isParentEntityJoined(Select $select)
    {
        try {
            $selectFrom = $select->getPart(Select::FROM);
        } catch (\Zend_Db_Select_Exception $e) {
            return false;
        }
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $connection = $select->getConnection();
        $entityField = $connection->quoteIdentifier($this->optionProvider->getProductEntityLinkField());

        $matches = array_filter($selectFrom, function (array $from) use ($entityField, $productTable) {
            $joinCondition = sprintf(
                '%s.%s = %s.parent_id',
                MagentoProductSyncRepository::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS,
                $entityField,
                MagentoProductSyncRepository::CATALOG_PRODUCT_SUPER_LINK_ALIAS
            );

            return isset($from['tableName'], $from['joinType'], $from['joinCondition'])
                && (false !== strpos($from['tableName'], $productTable))
                && $from['joinType'] === Select::INNER_JOIN
                && $from['joinCondition'] === $joinCondition;
        });

        return (bool)count($matches);
    }
}
