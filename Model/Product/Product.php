<?php

namespace Klevu\Search\Model\Product;

use Klevu\Search\Api\Service\Catalog\Product\GetReviewCountInterface;
use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Api\Service\Catalog\Product\Review\ConvertRatingToStarsInterface;
use Klevu\Search\Api\Service\Catalog\Product\Review\GetAverageRatingInterface;
use Klevu\Search\Helper\Compat as CompatHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as DataHelper;
use Klevu\Search\Helper\Image as ImageHelper;
use Klevu\Search\Helper\Price as PriceHelper;
use Klevu\Search\Model\Context;
use Magento\Catalog\Api\Data\ProductInterface as MagentoProductInterface;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\Group as CustomerGroup;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class Product extends DataObject implements ProductInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var DataHelper
     */
    protected $_searchHelperData;
    /**
     * @var ImageHelper
     */
    protected $_imageHelper;
    /**
     * @var PriceHelper
     */
    protected $_priceHelper;
    /**
     * @var ConfigHelper
     */
    protected $_configHelper;
    /**
     * @var CompatHelper
     */
    protected $_searchHelperCompat;
    /**
     * @var CustomerGroup
     */
    protected $_customerModelGroup;
    /**
     * @var GetReviewCountInterface
     */
    private $getRatingsCount;
    /**
     * @var GetAverageRatingInterface
     */
    private $getAverageRating;
    /**
     * @var ConvertRatingToStarsInterface
     */
    private $convertRatingToStars;
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @param Context $context
     * @param array $data
     * @param GetReviewCountInterface $getRatingsCount
     * @param GetAverageRatingInterface $getAverageRating
     * @param ConvertRatingToStarsInterface $convertRatingToStars
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        Context $context,
        array $data = [],
        GetReviewCountInterface $getRatingsCount = null,
        GetAverageRatingInterface $getAverageRating = null,
        ConvertRatingToStarsInterface $convertRatingToStars = null,
        ProductCollectionFactory $productCollectionFactory = null
    ) {
        $this->_storeModelStoreManagerInterface = $context->getStoreManagerInterface();
        $this->_searchHelperData = $context->getHelperManager()->getDataHelper();
        $this->_imageHelper = $context->getHelperManager()->getImageHelper();
        $this->_priceHelper = $context->getHelperManager()->getPriceHelper();
        $this->_configHelper = $context->getHelperManager()->getConfigHelper();
        $this->_searchHelperCompat = $context->getHelperManager()->getCompatHelper();
        $this->_customerModelGroup = $context->getKlevuCustomerGroup();
        $objectManager = ObjectManager::getInstance();
        $this->getRatingsCount = $getRatingsCount ?: $objectManager->get(GetReviewCountInterface::class);
        $this->getAverageRating = $getAverageRating ?: $objectManager->get(GetAverageRatingInterface::class);
        $this->convertRatingToStars = $convertRatingToStars ?:
            $objectManager->get(ConvertRatingToStarsInterface::class);
        $this->productCollectionFactory = $productCollectionFactory ?:
            $objectManager->get(ProductCollectionFactory::class);
        parent::__construct($data);
    }

    /**
     * @param StoreInterface|int|string $store
     *
     * @return mixed
     */
    public function getBaseUrl($store)
    {
        $baseUrl = '';
        try {
            $storeObject = $this->_storeModelStoreManagerInterface->getStore($store);
            $storeId = $storeObject->getId();
        } catch (NoSuchEntityException $exception) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $exception->getMessage());

            return $baseUrl;
        }
        try {
            $baseUrl = $storeObject->getBaseUrl(
                UrlInterface::URL_TYPE_LINK,
                $this->_configHelper->isSecureUrlEnabled($storeId)
            );
        } catch (\InvalidArgumentException $exception) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $exception->getMessage());
        }

        return $baseUrl;
    }

    /**
     * Returns Base Currency Code
     *
     * @return string|null
     */
    public function getCurrency()
    {
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore();
        } catch (NoSuchEntityException $exception) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $exception->getMessage());

            return null;
        }

        return $store->getBaseCurrencyCode();
    }

    /**
     * @param string $key
     * @param array $attributes
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     *
     * @return float|int|string
     */
    public function getBoostingAttribute($key, $attributes, $parent, $item, $product)
    {
        foreach ($attributes as $attribute) {
            if ($parent && $parent->getData($attribute)) {
                $product[$key] = $this->checkBoostingAttributeValue($attribute, $parent);
            } else {
                $product[$key] = $this->checkBoostingAttributeValue($attribute, $item);
            }
        }

        return $product[$key];
    }

    /**
     * check boosting attribute is integer or decimal if it is string then send the blank value
     *
     * @param string $attribute
     * @param object $product
     *
     * @return int|string|float|null
     */
    public function checkBoostingAttributeValue($attribute, $product)
    {
        $productAttribute = $product->getResource()->getAttribute($attribute);
        if ($productAttribute) {
            $attributeFrontend = $productAttribute->getFrontend();
            if (null !== $attributeFrontend) {
                $productBoostingAttributeValue = $attributeFrontend->getValue($product);
                if (!is_numeric($productBoostingAttributeValue)) {
                    $productBoostingAttributeValue = "";
                }

                return $productBoostingAttributeValue;
            }
        }

        return null;
    }

    /**
     * @param string $key
     * @param array $attributes
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     *
     * @return float
     *
     * @deprecated extracted to service
     * @see \Klevu\Search\Api\Service\Catalog\Product\GetRatingStarsInterface
     */
    public function getRating($key, $attributes, $parent, $item, $product)
    {
        // Ideally here we'd call GetRatingStarsInterface::execute() directly,
        // however we still need to call self::convertToRatingStar()
        // as merchants may have extended/plugged into that public method.

        $prod = $parent ?: $item;
        // if, for a third party rating module, you need to pass more products, say a grouped product and its children
        // then use a before plugin on `getAverageRating->execute` to add products into the array
        $averageRating = $this->getAverageRating->execute([$prod]);

        return (null === $averageRating)
            ? null
            : $this->convertToRatingStar($averageRating);
    }

    /**
     * Convert percent to rating star
     *
     * @param float|int $percentage
     *
     * @return float
     *
     * @deprecated extracted to service
     * @see \Klevu\Search\Api\Service\Catalog\Product\Review\ConvertRatingToStarsInterface
     */
    public function convertToRatingStar($percentage)
    {
        try {
            $ratingStars = $this->convertRatingToStars->execute($percentage);
        } catch (LocalizedException $exception) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $exception->getMessage());
            $ratingStars = null;
        }

        return $ratingStars;
    }

    /**
     * @param MagentoProductInterface $item
     * @param MagentoProductInterface|null $parent
     *
     * @return int
     *
     * @deprecated extracted to service
     * @see \Klevu\Search\Api\Service\Catalog\Product\GetRatingsCountInterface
     */
    public function getRatingCount(MagentoProductInterface $item, $parent = null)
    {
        $product = $parent ?: $item;
        // if, for a third party rating module you need to pass more products, say a grouped product and the children
        // then use a before plugin on `getRatingsCount->execute` to add to the products in the array
        $ratingCount = $this->getRatingsCount->execute([$product]);

        return ($ratingCount <= 0) ? null : $ratingCount;
    }

    /**
     * @param string $key
     * @param array $attributes
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     *
     * @return string|null
     */
    public function getSku($key, $attributes, $parent, $item, $product)
    {
        foreach ($attributes as $attribute) {
            if ($parent && $parent->getData($attribute)) {
                $item_sku = $item->getData($attribute);
                $parent_sku = $parent->getData($attribute);
                $product[$key] = $this->_searchHelperData->getKlevuProductSku($item_sku, $parent_sku);
            } else {
                $product[$key] = $item->getData($attribute);
            }
        }

        return isset($product[$key]) ? (string)$product[$key] : null;
    }

    /**
     * @param string $key
     * @param array $attributes
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     *
     * @return string|null
     */
    public function getName($key, $attributes, $parent, $item, &$product)
    {
        foreach ($attributes as $attribute) {
            if ($parent && $parent->getData($attribute)) {
                $product[$key] = $parent->getData($attribute);
            } elseif ($item->getData($attribute)) {
                $product[$key] = $item->getData($attribute);
            }
        }

        return isset($product[$key]) ? (string)$product[$key] : null;
    }

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
    public function getImage($key, $attributes, $parent, $item, $product, $store)
    {
        $keys = array_keys($attributes);
        $attribute = $attributes[$keys[0]];
        if ($this->_configHelper->isUseConfigImage($store->getId())) {
            $product[$key] = $this->_imageHelper->getParentProductImage($parent, $item, $attribute);
        } else {
            $product[$key] = $this->_imageHelper->getSimpleProductImage($parent, $item, $attribute);
        }

        $product[$key] = isset($product[$key]) ? (string)$product[$key] : null;
        if ($product[$key] && $product[$key] !== "" && strpos($product[$key], "http") !== 0) {
            if (!empty($product[$key]) &&
                $product[$key] !== "no_selection" &&
                strpos($product[$key], "/", 0) !== 0
            ) {
                $product[$key] = "/" . $product[$key];
            }
            $product[$key] = $this->_imageHelper->getImagePath($product[$key]);
        }

        return isset($product[$key]) ? (string)$product[$key] : null;
    }

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     * @param StoreInterface $store
     *
     * @return float
     */
    public function getSalePriceData($parent, $item, $product, $store)
    {
        // Default to 0 if price can't be determined
        $product['salePrice'] = 0.0;
        $salePrice = $this->_priceHelper->getKlevuSalePrice($parent, $item, $store);
        if ($parent) {
            $childSalePrice = $this->_priceHelper->getKlevuSalePrice(null, $item, $store);
            // also send sale price for sorting and filters for klevu
            $product['salePrice'] = (float)$childSalePrice['salePrice'];
        } else {
            $product['salePrice'] = (float)$salePrice['salePrice'];
        }

        return $product['salePrice'];
    }

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     * @param StoreInterface $store
     *
     * @return float|null
     */
    public function getToPriceData($parent, $item, $product, $store)
    {
        $salePrice = $this->_priceHelper->getKlevuSalePrice($parent, $item, $store);

        $product['toPrice'] = isset($salePrice['toPrice'])
            ? (float)$salePrice['toPrice']
            : null;

        return $product['toPrice'] ?: null;
    }

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     * @param StoreInterface $store
     *
     * @return float|null
     */
    public function getStartPriceData($parent, $item, $product, $store)
    {
        $salePrice = $this->_priceHelper->getKlevuSalePrice($parent, $item, $store);
        if ($parent) {
            $childSalePrice = $this->_priceHelper->getKlevuSalePrice(null, $item, $store);
            // show low price for config products
            $product['startPrice'] = (float)$salePrice['salePrice'];
        } else {
            $product['startPrice'] = (float)$salePrice['salePrice'];
        }

        return $product['startPrice'] ?: null;
    }

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     * @param StoreInterface $store
     *
     * @return float
     */
    public function getPriceData($parent, $item, $product, $store)
    {
        $product['price'] = 0.0;
        if ($parent) {
            $childSalePrice = $this->_priceHelper->getKlevuPrice($item, $item, $store);
            $product['price'] = (float)$childSalePrice['price'];
        } else {
            $price = $this->_priceHelper->getKlevuPrice($parent, $item, $store);
            $product['price'] = (float)$price['price'];
        }

        return $product['price'];
    }

    /**
     * @param string $key
     * @param array $attributes
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $product
     * @param StoreInterface $store
     *
     * @return false|string
     */
    public function getDateAdded($key, $attributes, $parent, $item, $product, $store)
    {
        foreach ($attributes as $attribute) {
            $product[$key] = substr($item->getData($attribute), 0, 10);
        }

        return $product[$key];
    }

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return string|null
     */
    public function getProductType($parent, $item)
    {
        if ($parent) {
            $product['product_type'] = $parent->getData('type_id');
        } else {
            $product['product_type'] = $item->getData('type_id');
        }

        return isset($product['product_type']) ? (string)$product['product_type'] : null;
    }

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
    public function getVisibility($key, $attributes, $parent, $item, $product, $store)
    {
        if ($parent) {
            $product['visibility'] = $parent->getData('visibility');
        } else {
            $product['visibility'] = $item->getData('visibility');
        }

        return isset($product['visibility']) ? (string)$product['visibility'] : null;
    }

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return string
     */
    public function isCustomOptionsAvailable($parent, $item)
    {
        $productType = ["grouped", "configurable", "bundle", "downloadable"];
        if ($parent) {
            $product['isCustomOptionsAvailable'] = "yes";
        } elseif (in_array($item->getData('type_id'), $productType, true)) {
            $product['isCustomOptionsAvailable'] = "yes";
        } elseif ($item->getData('has_options')) {
            $product['isCustomOptionsAvailable'] = "yes";
        } else {
            $product['isCustomOptionsAvailable'] = "no";
        }

        return $product['isCustomOptionsAvailable'];
    }

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return string
     */
    public function getCategory($parent, $item)
    {
        if ($parent) {
            $product['category'] = $this->getLongestPathCategoryName($parent->getCategoryIds());
        } elseif ($item->getCategoryIds()) {
            $product['category'] = $this->getLongestPathCategoryName($item->getCategoryIds());
        } else {
            $product['category'] = "";
        }

        return $product['category'];
    }

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return array
     */
    public function getListCategory($parent, $item)
    {
        if ($parent) {
            $product['listCategory'] = $this->getCategoryNames($parent->getCategoryIds());
        } elseif ($item->getCategoryIds()) {
            $product['listCategory'] = $this->getCategoryNames($item->getCategoryIds());
        } else {
            $product['listCategory'] = ["KLEVU_PRODUCT"];
        }

        return $product['listCategory'];
    }

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return string
     */
    public function getAllCategoryId($parent, $item)
    {
        if ($parent) {
            //category ids parent
            $product['categoryIds'] = $this->getAllCategoryIdProcessed($parent);
        } elseif ($item->getCategoryIds()) {
            $product['categoryIds'] = $this->getAllCategoryIdProcessed($item);
        } else {
            $product['categoryIds'] = "";
        }

        return $product['categoryIds'];
    }

    /**
     * @param MagentoProductInterface $item
     *
     * @return string
     */
    private function getAllCategoryIdProcessed($item)
    {
        $itemCategories = $item->getCategoryIds();
        $category_paths_ids = $this->getCategoryPathIds();
        if ($category_paths_ids) {
            $itemCategories = array_intersect($itemCategories, array_keys($category_paths_ids));
        }

        $category_anchors = $this->getCategoryAnchors();
        $return = [];
        $storeId = null;
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore();
            $storeId = $store->getId();
        } catch (NoSuchEntityException $exception) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $exception->getMessage());
        }
        $isCatAnchorSingle = $this->_configHelper->getTreatCategoryAnchorAsSingle($storeId);
        if ($isCatAnchorSingle && is_array($itemCategories)) {
            foreach ($itemCategories as $id) {
                if (!isset($category_paths_ids[$id]) || !count($category_paths_ids[$id])) {
                    continue;
                }
                foreach ($category_paths_ids[$id] as $catIsAnchor) {
                    if (!isset($category_anchors[$catIsAnchor], $category_paths_ids[$catIsAnchor])) {
                        continue;
                    }
                    $return[] = end($category_paths_ids[$catIsAnchor]);
                }
            }
            $return = array_merge($return, $itemCategories);
            $itemCategories = array_unique($return);
        }

        return implode(";", (is_array($itemCategories) ? $itemCategories : []));
    }

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return string|null
     */
    public function getAllCategoryPaths($parent, $item)
    {
        if ($parent) {
            $product['categoryPaths'] = $this->getCategoryNamesAndPath($parent->getCategoryIds());
        } elseif ($item->getCategoryIds()) {
            $product['categoryPaths'] = $this->getCategoryNamesAndPath($item->getCategoryIds());
        } else {
            $product['categoryPaths'] = "";
        }

        return isset($product['categoryPaths']) ? (string)$product['categoryPaths'] : null;
    }

    /**
     * @param MagentoProductInterface $item
     *
     * @return array|null
     */
    public function getGroupPricesData($item)
    {
        if ($item) {
            $product['groupPrices'] = $this->getGroupPrices($item);
        } else {
            $product['groupPrices'] = "";
        }

        return $product['groupPrices'];
    }

    /**
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     * @param array $url_rewrite_data
     * @param array $product
     * @param string $base_url
     *
     * @return string
     */
    public function getProductUrlData($parent, $item, $url_rewrite_data, $product, $base_url)
    {
        if ($parent) {
            if (isset($url_rewrite_data[$product['parent_id']])) {
                if ($url_rewrite_data[$product['parent_id']][0] === "/") {
                    $product['url'] = $base_url . substr($url_rewrite_data[$product['parent_id']], 1);
                } else {
                    $product['url'] = $base_url . $url_rewrite_data[$product['parent_id']];
                }
            } else {
                $product['url'] = $base_url . "catalog/product/view/id/" . $product['parent_id'];
            }
        } elseif (isset($url_rewrite_data[$product['product_id']])) {
            if ($url_rewrite_data[$product['product_id']][0] === "/") {
                $product['url'] = $base_url . substr($url_rewrite_data[$product['product_id']], 1);
            } else {
                $product['url'] = $base_url . $url_rewrite_data[$product['product_id']];
            }
        } else {
            $product['url'] = $base_url . "catalog/product/view/id/" . $product['product_id'];
        }

        return $product['url'];
    }

    /**
     * @param string|int $parent_id
     * @param array $product
     *
     * @return int|null
     */
    public function getItemGroupId($parent_id, $product)
    {
        $product['itemGroupId'] = '';
        if ((int)$parent_id !== 0) {
            $product['itemGroupId'] = (int)$parent_id;
        }

        return $product['itemGroupId'];
    }

    /**
     * @param int $product_id
     * @param int|null $parent_id
     *
     * @return string
     */
    public function getId($product_id, $parent_id)
    {
        return $this->_searchHelperData->getKlevuProductId($product_id, $parent_id);
    }

    /**
     * Given a list of category IDs, return the name of the category
     * in that list that has the longest path.
     *
     * @param array $categories
     *
     * @return string
     */
    public function getLongestPathCategoryName(array $categories)
    {
        $storeId = null;
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore();
            $storeId = $store->getId();
        } catch (NoSuchEntityException $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $e->getMessage());
        }
        $category_paths = $this->getCategoryPaths();
        $category_anchors = $this->getCategoryAnchors();
        $category_paths_ids = $this->getCategoryPathIds();
        $isCatAnchorSingle = $this->_configHelper->getTreatCategoryAnchorAsSingle($storeId);
        $name = [];
        foreach ($categories as $id) {
            if (!isset($category_paths[$id])) {
                continue;
            }
            $name[] = end($category_paths[$id]) . ";";
            //added to support category anchors
            if (!$isCatAnchorSingle || empty($category_paths_ids) || !count($category_paths[$id])) {
                continue;
            }
            foreach ($category_paths_ids[$id] as $catIsAnchor) {
                if (!isset($category_anchors[$catIsAnchor], $category_paths[$catIsAnchor])) {
                    continue;
                }
                $name[] = end($category_paths[$catIsAnchor]) . ";";
            }
        }
        $name = array_unique($name);
        $name = implode("", $name);

        return substr($name, 0, strrpos($name, ";") + 1 - 1);
    }

    /**
     * Return a list of the names of all the categories in the
     * paths of the given categories (including the given categories)
     * up to, but not including the store root.
     *
     * @param array $categories
     *
     * @return array
     */
    public function getCategoryNames(array $categories)
    {
        $storeId = null;
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore();
            $storeId = $store->getId();
        } catch (NoSuchEntityException $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $e->getMessage());
        }
        $category_paths = $this->getCategoryPaths();
        $category_paths_ids = $this->getCategoryPathIds();
        $category_anchors = $this->getCategoryAnchors();
        $isCatAnchorSingle = $this->_configHelper->getTreatCategoryAnchorAsSingle($storeId);
        $catPaths = [];
        foreach ($categories as $category) {
            if (!isset($category_paths[$category])) {
                continue;
            }
            if (count($category_paths[$category]) > 0) {
                $catPaths[$category][] = implode(";", $category_paths[$category]);
                //added to support category anchors
                if (!$isCatAnchorSingle) {
                    continue;
                }
                foreach ($category_paths_ids[$category] as $catIsAnchor) {
                    if (!isset($category_anchors[$catIsAnchor], $category_paths[$catIsAnchor])) {
                        continue;
                    }
                    if (count($category_paths[$catIsAnchor]) > 0) {
                        $catPaths[$catIsAnchor][] = implode(";", $category_paths[$catIsAnchor]);
                    } else {
                        $catPaths[$catIsAnchor] = $category_paths[$catIsAnchor];
                    }
                }
            } else {
                $catPaths[$category] = $category_paths[$category];
            }
        }
        $result = array_merge(["KLEVU_PRODUCT"], ...$catPaths);

        return array_values(
            array_unique($result)
        );
    }

    /**
     * Return a list of the names of all the categories in the
     * paths of the given categories (including the given categories)
     * up to, but not including the store root.
     *
     * @param array $categories
     *
     * @return string
     */
    public function getCategoryNamesAndPath(array $categories)
    {
        $storeId = null;
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore();
            $storeId = $store->getId();
        } catch (NoSuchEntityException $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $e->getMessage());
        }
        $categoryPaths = $this->getCategoryPathsAndIds();
        $categoryIds = $this->getCategoryPathIds();
        $categoryAnchors = $this->getCategoryAnchors();
        $isCatAnchorSingle = $this->_configHelper->getTreatCategoryAnchorAsSingle($storeId);
        $catPath = [];

        foreach ($categories as $category) {
            if (!isset($categoryPaths[$category])) {
                continue;
            }
            if (count($categoryPaths[$category]) > 0) {
                $catName = implode(";", $categoryPaths[$category]);
                $catId = implode("/", $categoryIds[$category]);
                $catPath[$category][] = $catName . '::' . $catId;
                // check if need to treat anchors as standalone
                if (!$isCatAnchorSingle) {
                    continue;
                }
                foreach ($categoryIds[$category] as $isCatAnchor) {
                    if (!isset($categoryAnchors[$isCatAnchor], $categoryPaths[$isCatAnchor])) {
                        continue;
                    }
                    if (count($categoryPaths[$isCatAnchor]) > 0) {
                        $catName = implode(";", $categoryPaths[$isCatAnchor]);
                        $catId = implode("/", $categoryIds[$isCatAnchor]);
                        $catPath[$isCatAnchor][] = $catName . '::' . $catId;
                    } else {
                        $catName = $categoryPaths[$isCatAnchor];
                        $catId = $categoryIds[$isCatAnchor];
                        $catPath[$isCatAnchor] = (!is_array($catName) && !is_array($catId)) ?
                            $catName . '::' . $catId :
                            [];
                    }
                }
            } else {
                $catName = $categoryPaths[$category];
                $catId = $categoryIds[$category];
                if (!is_array($catName) && !is_array($catId)) {
                    $catPath[$category] = $catName . '::' . $catId;
                } else {
                    $catPath[$category] = [];
                }
            }
        }
        $result = (count($catPath)) ? array_merge(...$catPath) : [];

        return implode(";;", array_unique($result));
    }

    /**
     * Return an array of category paths for all the categories in the
     * current store, not including the store root.
     *
     * @return array A list of category paths where each key is a category
     *               ID and each value is an array of category names for
     *               each category in the path, the last element being the
     *               name of the category referenced by the ID.
     */
    public function getCategoryPaths()
    {
        $categoryPaths = $this->getData('category_paths');
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore();
            $storeId = $store->getId();
            $rootId = $store->getRootCategoryId();
        } catch (NoSuchEntityException $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $e->getMessage());

            return $categoryPaths;
        }
        if (($categoryPaths) && ((int)$storeId === (int)$this->getData('catFieldStoreID'))) {
            return $categoryPaths;
        }
        $this->setData('catFieldStoreID', $storeId);
        $categoryPaths = [];
        $categoryIds = [];
        $categoryPathsAndIds = [];

        try {
            $collection = $this->productCollectionFactory->create();
            $collection->setStoreId($storeId);
            $collection->addAttributeToSelect('is_exclude_cat');
            $collection->addAttributeToSelect('is_anchor');
            $collection->addAttributeToSelect('is_active');
            $collection->addFieldToFilter('level', ['gt' => 1]);
            $collection->addFieldToFilter('path', ['like' => "1/$rootId/%"]);
            $collection->addNameToResult();
        } catch (LocalizedException $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $e->getMessage());

            return $categoryPaths;
        }

        $categoryAnchors = [];
        foreach ($collection as $category) {
            if ($category->getIsExcludedCat() || !(int)$category->getIsActive()) {
                continue;
            }
            if ($category->getIsAnchor()) {
                $categoryAnchors[$category->getId()] = $category->getId();
            }
            $categoryPaths[$category->getId()] = [];
            $categoryPathsAndIds[$category->getId()] = [];
            $categoryIds[$category->getId()] = [];
            $pathIds = $category->getPathIds();
            foreach ($pathIds as $id) {
                $item = $collection->getItemById($id);
                if (!$item) {
                    continue;
                }
                $categoryIds[$category->getId()][] = $item->getId();
                $categoryPathsAndIds[$category->getId()][] = $item->getName();
                $categoryPaths[$category->getId()][] = $item->getName();
            }
        }
        $this->setData('category_anchors', $categoryAnchors);
        $this->setData('category_paths_and_ids', $categoryPathsAndIds);
        $this->setData('category_path_ids', $categoryIds);
        $this->setData('category_paths', $categoryPaths);

        return $categoryPaths;
    }

    /**
     * Ref: KS-7557 Added to triage temporal coupling with getCategoryPaths()
     *  setting additional data
     *
     * @return array
     */
    public function getCategoryAnchors()
    {
        if (!$this->hasData('category_anchors')) {
            $this->getCategoryPaths();
        }

        return $this->getData('category_anchors');
    }

    /**
     * Ref: KS-7557 Added to triage temporal coupling with getCategoryPaths()
     *  setting additional data
     *
     * @return array
     */
    public function getCategoryPathsAndIds()
    {
        if (!$this->hasData('category_paths_and_ids')) {
            $this->getCategoryPaths();
        }

        return $this->getData('category_paths_and_ids');
    }

    /**
     * Ref: KS-7557 Added to triage temporal coupling with getCategoryPaths()
     *  setting additional data
     *
     * @return array
     */
    public function getCategoryPathIds()
    {
        if (!$this->hasData('category_path_ids')) {
            $this->getCategoryPaths();
        }

        return $this->getData('category_path_ids');
    }

    /**
     * Get the list of prices based on customer group
     *
     * @param MagentoProductInterface $proData
     *
     * @return array|null
     */
    protected function getGroupPrices($proData)
    {
        $websiteId = null;
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore();
            $websiteId = (int)$store->getWebsiteId();
        } catch (NoSuchEntityException $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $e->getMessage());
        }
        $groupPrices = $proData->getData('tier_price');
        if (null === $groupPrices) {
            $resource = $proData->getResource();
            $attribute = $resource->getAttribute('tier_price');
            if ($attribute) {
                $attribute->getBackend()->afterLoad($proData);
                $groupPrices = $proData->getData('tier_price');
            }
        }

        if (empty($groupPrices) || !is_array($groupPrices)) {
            return null;
        }
        $priceGroupData = [];
        foreach ($groupPrices as $groupPrice) {
            $groupWebsiteId = (int)$groupPrice['website_id'];
            if (($groupWebsiteId === 0 || $websiteId === $groupWebsiteId) &&
                (int)$groupPrice['price_qty'] === 1
            ) {
                $groupPriceKey = $groupPrice['cust_group'];
                $customerGroup = $this->_customerModelGroup->load($groupPrice['cust_group']);
                $groupName = $customerGroup->getCustomerGroupCode();
                $result['label'] = $groupName;
                $result['values'] = $groupPrice['website_price'];
                $priceGroupData[$groupPriceKey] = $result;
            }
        }

        return $priceGroupData;
    }

    /**
     * Get the list of prices based on customer group
     *
     * @param MagentoProduct $proData
     * @param string $currency
     *
     * @return string
     * @todo Replace with centralised service for retrieving / formatting price data
     */
    public function getOtherPrices($proData, $currency)
    {
        $otherPrices = $proData->getData('tier_price');
        if (null === $otherPrices) {
            /** @var \Magento\Catalog\Model\ResourceModel\Product $productResource */
            $productResource = $proData->getResource();
            try {
                $attribute = $productResource->getAttribute('tier_price');
                if ($attribute) {
                    $attributeBackend = $attribute->getBackend();
                    $attributeBackend->afterLoad($proData);
                    $otherPrices = $proData->getData('tier_price');
                }
            } catch (LocalizedException $e) {
                $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $e->getMessage());

                return '';
            }
        }
        if (!$otherPrices || !is_array($otherPrices)) {
            return '';
        }

        try {
            $store = $this->_storeModelStoreManagerInterface->getStore();
            $websiteId = (int)$store->getWebsiteId();
        } catch (NoSuchEntityException $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, $e->getMessage());
            $websiteId = 0;
        }
        if (!$websiteId) {
            return '';
        }

        $result = array_filter(array_map(function (array $otherPrice) use ($currency, $websiteId) {
            $otherPrice = array_merge([
                'website_id' => 0,
                'price_qty' => 0,
                'cust_group' => null,
                'website_price' => null,
            ], $otherPrice);

            switch (true) {
                case null === $otherPrice['cust_group']:
                case !is_numeric($otherPrice['website_price']):
                    $this->_searchHelperData->log(
                        LoggerConstants::ZEND_LOG_WARN,
                        'Invalid data received for otherPrice: ' . json_encode($otherPrice)
                    );
                    $return = null;
                    break;
                // Intentional cascade
                case (int)$otherPrice['price_qty'] !== 1:
                    $return = null;
                    break;
                default:
                    $return = sprintf(
                        'salePrice_%s-%s:%s',
                        $currency,
                        $otherPrice['cust_group'],
                        $otherPrice['website_price']
                    );
                    break;
            }

            return $return;
        }, $otherPrices));

        return implode(';', $result);
    }
}
