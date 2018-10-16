<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 9/22/2018
 * Time: 3:03 PM
 */

namespace Klevu\Search\Model\Category;

interface LoadAttributeInterface
{
    /**
     * Add the Category Sync data to each Category in the given list. Updates the given
     * list directly to save memory.
     *
     * @param array $categories An array of categories. Each element should be an array with
     *                        containing an element with "id" as the key and the Category
     *                        ID as the value.
     *
     * @return $this
     */
    public function addcategoryData(&$pages);

    public function loadCategoryCollection($storeId, $category_ids);
}