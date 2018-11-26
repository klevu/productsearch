<?php
/**
 * ProductInterface
 */

namespace Klevu\Search\Model\Product;

interface ProductInterface
{
    public function getBaseUrl($store);

    public function getCurrency();

    public function getBoostingAttribute($key, $attributes, $parent, $item, $product);

    public function getRating($key, $attributes, $parent, $item, $product);

    /**
     * Convert percent to rating star
     *
     * @param int percentage
     *
     * @return float
     */
    public function convertToRatingStar($percentage);

    public function getSku($key, $attributes, $parent, $item, $product);

    public function getName($key, $attributes, $parent, $item, &$product);

    public function getImage($key, $attributes, $parent, $item, $product, $store);

    public function getSalePriceData($parent, $item, $product, $store);

    public function getToPriceData($parent, $item, $product, $store);

    public function getStartPriceData($parent, $item, $product, $store);

    public function getPriceData($parent, $item, $product, $store);

    public function getDateAdded($key, $attributes, $parent, $item, $product, $store);

    public function getProductType($parent, $item);

    public function isCustomOptionsAvailable($parent, $item);

    public function getCategory($parent, $item);

    public function getListCategory($parent, $item);

    public function getGroupPricesData($item);

    public function getProductUrlData($parent, $item, $url_rewrite_data, $product, $base_url);

    public function getItemGroupId($parent_id, $product);

    public function getId($product_id, $parent_id);

    /**
     * Given a list of category IDs, return the name of the category
     * in that list that has the longest path.
     *
     * @param array $categories
     *
     * @return string
     */
    public function getLongestPathCategoryName(array $categories);

    /**
     * Return a list of the names of all the categories in the
     * paths of the given categories (including the given categories)
     * up to, but not including the store root.
     *
     * @param array $categories
     *
     * @return array
     */
    public function getCategoryNames(array $categories);
}