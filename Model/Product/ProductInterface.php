<?php

namespace Klevu\Search\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface as MagentoProductInterface;
use Magento\Store\Api\Data\StoreInterface;

interface ProductInterface
{
    /**
     * @param StoreInterface $store
     *
     * @return string|null
     */
    public function getBaseUrl($store);

    /**
     * @return string|null
     */
    public function getCurrency();

    /**
     * @param string $key
     * @param array $attributes
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     *
     * @return float|int|string
     */
    public function getBoostingAttribute($key, $attributes, $parent, $item, $product);

    /**
     * @param string $key
     * @param array $attributes
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     *
     * @return float
     * @deprecated moved to service class
     * @see \Klevu\Search\Api\Service\Catalog\Product\GetRatingStarsInterface
     */
    public function getRating($key, $attributes, $parent, $item, $product);

    /**
     * Convert percent to rating star
     *
     * @param float|int $percentage
     *
     * @return float
     * @deprecated moved to service class
     * @see \Klevu\Search\Api\Service\Catalog\Product\Review\ConvertRatingToStarsInterface
     */
    public function convertToRatingStar($percentage);

    /**
     * @param MagentoProductInterface $item
     * @param MagentoProductInterface|null $parent
     *
     * @return int
     * @deprecated moved to service class
     * @see \Klevu\Search\Api\Service\Catalog\Product\GetRatingsCountInterface
     */
    public function getRatingCount(MagentoProductInterface $item, $parent = null);

    /**
     * @param string $key
     * @param array $attributes
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     *
     * @return string|null
     */
    public function getSku($key, $attributes, $parent, $item, $product);

    /**
     * @param string $key
     * @param array $attributes
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     *
     * @return string|null
     */
    public function getName($key, $attributes, $parent, $item, &$product);

    /**
     * @param string $key
     * @param array $attributes
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     * @param StoreInterface $store
     *
     * @return string|null
     */
    public function getImage($key, $attributes, $parent, $item, $product, $store);

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     * @param StoreInterface $store
     *
     * @return mixed
     */
    public function getSalePriceData($parent, $item, $product, $store);

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     * @param StoreInterface $store
     *
     * @return mixed
     */
    public function getToPriceData($parent, $item, $product, $store);

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     * @param StoreInterface $store
     *
     * @return mixed
     */
    public function getStartPriceData($parent, $item, $product, $store);

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     * @param StoreInterface $store
     *
     * @return mixed
     */
    public function getPriceData($parent, $item, $product, $store);

    /**
     * @param string $key
     * @param array $attributes
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     * @param StoreInterface $store
     *
     * @return string|false
     */
    public function getDateAdded($key, $attributes, $parent, $item, $product, $store);

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return string|null
     */
    public function getProductType($parent, $item);

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return string
     */
    public function isCustomOptionsAvailable($parent, $item);

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return string
     */
    public function getCategory($parent, $item);

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return array
     */
    public function getListCategory($parent, $item);

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return string
     */
    public function getAllCategoryId($parent, $item);

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return string|null
     */
    public function getAllCategoryPaths($parent, $item);

    /**
     * @param MagentoProductInterface $item
     *
     * @return array|null
     */
    public function getGroupPricesData($item);

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $url_rewrite_data
     * @param array $product
     * @param string $base_url
     *
     * @return string
     */
    public function getProductUrlData($parent, $item, $url_rewrite_data, $product, $base_url);

    /**
     * @param string|int $parent_id
     * @param array $product
     *
     * @return int|null
     */
    public function getItemGroupId($parent_id, $product);

    /**
     * @param int $product_id
     * @param int|null $parent_id
     *
     * @return sring
     */
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
