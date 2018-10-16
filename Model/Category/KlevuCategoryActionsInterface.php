<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 9/22/2018
 * Time: 3:05 PM
 */

namespace Klevu\Search\Model\Category;

interface KlevuCategoryActionsInterface
{
    /**
     * Delete success processing , separated for easier override
     */
    public function executeDeleteCategorySuccess(array $data, $response);

    /**
     * Update success processing , separated for easier override
     */
    public function executeUpdateCategorySuccess(array $data, $response);

    /**
     * Add success processing , separated for easier override
     */
    public function executeAddCategorySuccess(array $data, $response);
}