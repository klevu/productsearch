<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 9/22/2018
 * Time: 3:06 PM
 */

namespace Klevu\Search\Model\Category;

interface MagentoCategoryActionsInterface
{
	/**
     * Returns category pages array based on store and action or error message will shown if it failed.
     *
     * @param object instance $store Store 
     * @param string $action delete|update|add
     *
     * @return array| A list with category pages
     */
    public function getCategorySyncDataActions($store, $action);

    /**
     * Update the given categories on Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of categories to update. Each element should be an array
     *                    containing an element with "category_id" as the key and category id as
     *                    the value
     *
     * @return bool|string
     */
    public function updateCategory(array $data);

    /**
     * Delete the given categories from Klevu Search. Returns true if the operation was
     * successful, or the error message if the operation failed.
     *
     * @param array $data List of categories to delete. Each element should be an array
     *                    containing an element with "category_id" as the key and category id as
     *                    the value.
     *
     * @return bool|string
     */
    public function deleteCategory(array $data);

    /**
     * Add the given Categories to Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of Categories to add. Each element should be an array
     *                    containing an element with "category_id" as the key and category id as
     *                    the value.
     *
     * @return bool|string
     */
    public function addCategory(array $data);

    /**
     * Return the URL rewrite data for the given products for the current store.
     *
     * @param array $product_ids A list of product IDs.
     *
     * @return array A list with product IDs as keys and request paths as values.
     */
    public function getCategoryUrlRewriteData($category_ids);
}