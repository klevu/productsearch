<?php

namespace Klevu\Search\Model\Category;

interface LoadAttributeInterface
{
    /**
     * Add the Category Sync data to each Category in the given list. Updates the given
     * list directly to save memory.
     *
     * @param $categories An array of categories. Each element should be an array with
     *                        containing an element with "id" as the key and the Category
     *                        ID as the value.
     *
     * @return array
     */
    public function addcategoryData(&$categories);

    /**
     * @param $storeId
     * @param $categoryIds
     *
     * @return mixed
     */
    public function loadCategoryCollection($storeId, $categoryIds);
}
