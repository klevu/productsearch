<?php

namespace Klevu\Search\Api\Service\Catalog\Product;

use Magento\Framework\DB\Select;

interface JoinParentVisibilityToSelectInterface
{
    /**
     * @param Select $select
     * @param int $storeId
     *
     * @return Select
     */
    public function execute(Select $select, $storeId);

    /**
     * @param string $table
     * @param string $alias
     *
     * @return void
     */
    public function setTableAlias($table, $alias);

    /**
     * @param string $table
     *
     * @return string
     */
    public function getTableAlias($table);
}
