<?php

namespace Klevu\Search\Model\Product;
use Magento\Framework\DataObject;



class Product extends DataObject implements ProductInterface
{

    protected $_storeModelStoreManagerInterface;
    protected $_searchHelperData;
    protected $_imageHelper;
    protected $_priceHelper;
    protected $_configHelper;
    protected $_searchHelperCompat;
    protected $_customerModelGroup;

    public function __construct(
        \Klevu\Search\Model\Context $context,
        array $data = []
    ){
        $this->_storeModelStoreManagerInterface = $context->getStoreManagerInterface();
        $this->_searchHelperData = $context->getHelperManager()->getDataHelper();
        $this->_imageHelper = $context->getHelperManager()->getImageHelper();
        $this->_priceHelper = $context->getHelperManager()->getPriceHelper();
        $this->_configHelper = $context->getHelperManager()->getConfigHelper();
        $this->_searchHelperCompat = $context->getHelperManager()->getCompatHelper();
        $this->_customerModelGroup = $context->getKlevuCustomerGroup();
        parent::__construct($data);

    }

    public function getBaseUrl($store)
    {
        if ($this->_configHelper->isSecureUrlEnabled($store->getId())) {
             $base_url = $this->_storeModelStoreManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true);
        } else {
             $base_url = $this->_storeModelStoreManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);
        }

        return $base_url;
    }

    public function getCurrency(){
        return $this->_storeModelStoreManagerInterface->getStore()->getDefaultCurrencyCode();
    }

    public function getBoostingAttribute($key,$attributes,$parent,$item,$product)
    {
        foreach ($attributes as $attribute) {
            if ($parent && $parent->getData($attribute)) {
                $product[$key] = $parent->getData($attribute);
            } else {
                $product[$key] = $item->getData($attribute);
            }
        }

        return $product[$key];

    }

    public  function getRating($key,$attributes,$parent,$item,$product)
    {
        foreach ($attributes as $attribute) {
            if ($parent && $parent->getData($attribute)) {
                $product[$key] = $this->convertToRatingStar($parent->getData($attribute));
            } else {
                $product[$key] = $this->convertToRatingStar($item->getData($attribute));
            }
        }
        return $product[$key];
    }

    /**
     * Convert percent to rating star
     *
     * @param int percentage
     *
     * @return float
     */
    public function convertToRatingStar($percentage)
    {
        if (!empty($percentage) && $percentage!=0) {
            $start = $percentage * 5;
            return round($start/100, 2);
        } else {
            return;
        }
    }




    public function getSku($key,$attributes,$parent,$item,$product)
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
        return $product[$key];
    }


    public function getName($key,$attributes,$parent,$item,&$product)
    {
        foreach ($attributes as $attribute) {
            if ($parent && $parent->getData($attribute)) {
                $product[$key] = $parent->getData($attribute);
            } elseif ($item->getData($attribute)) {
                $product[$key] = $item->getData($attribute);
            }
        }
        return $product[$key];
    }

    public function getImage($key,$attributes,$parent,$item,$product,$store)
    {
        foreach ($attributes as $attribute) {
            if ($this->_configHelper->isUseConfigImage($store->getId())) {
                $product[$key] = $this->_imageHelper->getParentProductImage($parent,$item,$attribute);
                break;
            } else {
                $product[$key] = $this->_imageHelper->getSimpleProductImage($parent,$item,$attribute);
                break;
            }
        }

        if ($product[$key] != "" && strpos($product[$key], "http") !== 0) {
            if(strpos($product[$key],"/", 0) !== 0 && !empty($product[$key]) && $product[$key]!= "no_selection" ){
                $product[$key] = "/".$product[$key];
            }
            $product[$key] = $this->_imageHelper->getImagePath($product[$key]);
        }

        return  $product[$key];
    }

    public function getSalePriceData($parent,$item,$product,$store)
    {
        // Default to 0 if price can't be determined
        $product['salePrice'] = 0;
        $salePrice = $this->_priceHelper->getKlevuSalePrice($parent, $item, $store);
        if($parent){
            $childSalePrice = $this->_priceHelper->getKlevuSalePrice(null, $item, $store);
            // also send sale price for sorting and filters for klevu
            $product['salePrice'] = $childSalePrice['salePrice'];
        } else {
            $product['salePrice'] = $salePrice['salePrice'];
        }
        return $product['salePrice'];

    }

    public function getToPriceData($parent,$item,$product,$store)
    {
        $salePrice = $this->_priceHelper->getKlevuSalePrice($parent, $item, $store);
        if(isset($salePrice['toPrice'])){
            $product['toPrice'] = $salePrice['toPrice'];
            return $product['toPrice'];
        }
        return;
    }

    public function getStartPriceData($parent,$item,$product,$store)
    {
        $salePrice = $this->_priceHelper->getKlevuSalePrice($parent, $item, $store);
        if($parent){
            $childSalePrice = $this->_priceHelper->getKlevuSalePrice(null, $item, $store);
            // show low price for config products
            $product['startPrice'] = $salePrice['salePrice'];
        } else {
            $product['startPrice'] = $salePrice['salePrice'];
        }
        return $product['startPrice'];
    }



    public function getPriceData($parent,$item,$product,$store)
    {
        $product['price'] = 0;
        if($parent){
            $childSalePrice = $this->_priceHelper->getKlevuPrice($item, $item, $store);
            $product['price'] = $childSalePrice['price'];
        } else {
            $price = $this->_priceHelper->getKlevuPrice($parent, $item, $store);
            $product['price'] = $price['price'];
        }

        return $product['price'];
    }

    public  function getDateAdded($key,$attributes,$parent,$item,$product,$store){
        foreach ($attributes as $attribute) {
            $product[$key] = substr($item->getData($attribute),0,10);
        }
        return $product[$key];
    }

    public function getProductType($parent,$item){
        if($parent){
            $product['product_type'] = $parent->getData('type_id');
        }else{
            $product['product_type'] = $item->getData('type_id');
        }

        return $product['product_type'];
    }

    public function isCustomOptionsAvailable($parent,$item){
        $productType = array("grouped", "configurable", "bundle", "downloadable");
        if($parent){
            $product['isCustomOptionsAvailable'] = "yes";
        }else if(in_array($item->getData('type_id'), $productType)){
            $product['isCustomOptionsAvailable'] = "yes";
        }else if ($item->getData('has_options')){
            $product['isCustomOptionsAvailable'] = "yes";
        }else{
            $product['isCustomOptionsAvailable'] = "no";
        }

        return $product['isCustomOptionsAvailable'];
    }

    public function getCategory($parent,$item){
        if ($parent) {
            $product['category'] = $this->getLongestPathCategoryName($parent->getCategoryIds());
        } elseif ($item->getCategoryIds()) {
            $product['category'] = $this->getLongestPathCategoryName($item->getCategoryIds());
        } else {
            $product['category'] = "";
        }
        return $product['category'];
    }

    public function getListCategory($parent,$item){
        if ($parent) {
            $product['listCategory'] = $this->getCategoryNames($parent->getCategoryIds());
        } elseif ($item->getCategoryIds()) {
            $product['listCategory'] = $this->getCategoryNames($item->getCategoryIds());
        } else {
            $product['listCategory'] = "KLEVU_PRODUCT";
        }
        return $product['listCategory'];
    }

    public function getAllCategoryId($parent,$item){
        if ($parent) {
            //category ids parent
            $parentCategorys = $parent->getCategoryIds();
            $product['categoryIds'] = implode(";",(is_array($parentCategorys)?$parentCategorys:[]));
        } elseif ($item->getCategoryIds()) {
            $itemCategorys = $item->getCategoryIds();
            $product['categoryIds'] = implode(";",(is_array($itemCategorys)?$itemCategorys:[]));
        } else {
            $product['categoryIds'] = "";
        }
        return $product['categoryIds'];
    }

    public function getAllCategoryPaths($parent,$item){
        if ($parent) {
            $product['categoryPaths'] = $this->getCategoryNamesAndPath($parent->getCategoryIds());
        } elseif ($item->getCategoryIds()) {
            $product['categoryPaths'] = $this->getCategoryNamesAndPath($item->getCategoryIds());
        } else {
            $product['categoryPaths'] = "";
        }
        return $product['categoryPaths'];
    }

    public function getGroupPricesData($item){
        if ($item) {
            $product['groupPrices'] = $this->getGroupPrices($item);
        } else {
            $product['groupPrices'] = "";
        }
        return $product['groupPrices'];
    }

    public function getProductUrlData($parent,$item,$url_rewrite_data,$product,$base_url){

        if ($parent) {
            if (isset($url_rewrite_data[$product['parent_id']])) {
                if ($url_rewrite_data[$product['parent_id']][0] == "/") {
                    $product['url'] = $base_url . (
                        (isset($url_rewrite_data[$product['parent_id']])) ?
                            substr($url_rewrite_data[$product['parent_id']], 1) :
                            "catalog/product/view/id/" . $product['parent_id']
                        );
                } else {
                    $product['url'] = $base_url . (
                        (isset($url_rewrite_data[$product['parent_id']])) ?
                            $url_rewrite_data[$product['parent_id']] :
                            "catalog/product/view/id/" . $product['parent_id']
                        );
                }
            } else {
                $product['url'] = $base_url."catalog/product/view/id/".$product['parent_id'];
            }
        } else {
            if (isset($url_rewrite_data[$product['product_id']])) {
                if ($url_rewrite_data[$product['product_id']][0] == "/") {
                    $product['url'] = $base_url . (
                        (isset($url_rewrite_data[$product['product_id']])) ?
                            substr($url_rewrite_data[$product['product_id']], 1) :
                            "catalog/product/view/id/" . $product['product_id']
                        );
                } else {
                    $product['url'] = $base_url . (
                        (isset($url_rewrite_data[$product['product_id']])) ?
                            $url_rewrite_data[$product['product_id']] :
                            "catalog/product/view/id/" . $product['product_id']
                        );
                }
            } else {
                $product['url'] = $base_url."catalog/product/view/id/".$product['product_id'];
            }
        }

        return $product['url'];

    }

    public function getItemGroupId($parent_id,$product){
        $product['itemGroupId'] = '';
        if ($parent_id != 0) {
            $product['itemGroupId'] = $parent_id;
        }
        return $product['itemGroupId'];
    }

    public function getId($product_id,$parent_id){
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
        $category_paths = $this->getCategoryPaths();
        $length = 0;
        $name = "";
        foreach ($categories as $id) {
            if (isset($category_paths[$id])) {
                //if (count($category_paths[$id]) > $length) {
                //$length = count($category_paths[$id]);
                $name .= end($category_paths[$id]).";";
                //}
            }
        }
        return substr($name, 0, strrpos($name, ";")+1-1);
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
        $category_paths = $this->getCategoryPaths();
        $result = ["KLEVU_PRODUCT"];
        foreach ($categories as $category) {
            if (isset($category_paths[$category])) {
                if(count($category_paths[$category]) > 0) {
                    $cat_path[$category][] = implode(";",$category_paths[$category]);
                } else {
                    $cat_path[$category] = $category_paths[$category];
                }
                $result = array_merge($result, $cat_path[$category]);
            }
        }
        return array_unique($result);
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
    public function getCategoryNamesAndPath(array $categories)
    {
        $category_paths = $this->getCategoryPaths();
        $category_ids = $this->getData('category_path_ids');
        $result = [];
        foreach ($categories as $category) {
            if (isset($category_paths[$category])) {
                if(count($category_paths[$category]) > 0) {
                    $catName = implode(";",$category_paths[$category]);
                    $catId = implode("/",$category_ids[$category]);
                    $cat_path[$category][] = $catName . '::' . $catId;
                } else {
                    $catName = $category_paths[$category];
                    $catId = $category_ids[$category];
                    $cat_path[$category] = $catName . '::' . $catId;
                }
                $result = array_merge($result, $cat_path[$category]);
            }
        }

        return implode(";;",array_unique($result));
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
    protected function getCategoryPaths()
    {
        if (!$category_paths = $this->getData('category_paths')) {
            $category_paths = [];
            $category_ids = [];
            $rootId = $this->_storeModelStoreManagerInterface->getStore()->getRootCategoryId();
            $collection = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('\Magento\Catalog\Model\ResourceModel\Category\Collection')
                ->setStoreId($this->_storeModelStoreManagerInterface->getStore()->getId())
                ->addFieldToFilter('level', ['gt' => 1])
                ->addFieldToFilter('path', ['like'=> "1/$rootId/%"])
                ->addIsActiveFilter()
                ->addNameToResult();
            foreach ($collection as $category) {
                $category_paths[$category->getId()] = [];
                $category_ids[$category->getId()] = [];
                $path_ids = $category->getPathIds();
                foreach ($path_ids as $id) {
                    if ($item = $collection->getItemById($id)) {
                        $category_ids[$category->getId()][] = $item->getId();
                        $category_paths[$category->getId()][] = $item->getName();
                    }
                }
            }
            $this->setData('category_path_ids', $category_ids);
            $this->setData('category_paths', $category_paths);
        }
        return $category_paths;
    }

    /**
     * Get the list of prices based on customer group
     *
     * @param object $item OR $parent
     *
     * @return array
     */
    protected function getGroupPrices($proData)
    {
        $groupPrices = $proData->getData('tier_price');
        if (is_null($groupPrices)) {
            $attribute = $proData->getResource()->getAttribute('tier_price');
            if ($attribute) {
                $attribute->getBackend()->afterLoad($proData);
                $groupPrices = $proData->getData('tier_price');
            }
        }

        if (!empty($groupPrices) && is_array($groupPrices)) {
            $priceGroupData = [];
            foreach ($groupPrices as $groupPrice) {
                if ($this->_storeModelStoreManagerInterface->getStore()->getWebsiteId()== $groupPrice['website_id'] || $groupPrice['website_id']==0) {
                    if ($groupPrice['price_qty'] == 1) {
                        $groupPriceKey = $groupPrice['cust_group'];
                        $groupname = $this->_customerModelGroup->load($groupPrice['cust_group'])->getCustomerGroupCode();
                        $result['label'] =  $groupname;
                        $result['values'] =  $groupPrice['website_price'];
                        $priceGroupData[$groupPriceKey]= $result;
                    }
                }
            }
            return $priceGroupData;
        }
    }

}