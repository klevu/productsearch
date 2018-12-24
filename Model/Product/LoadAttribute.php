<?php
/**
 * Class \Klevu\Search\Model\Product\MagentoProductActionsInterface
 */
namespace Klevu\Search\Model\Product;
use \Klevu\Search\Model\Product\ProductInterface as Klevu_ProductData;
use \Magento\Framework\Model\AbstractModel as AbstractModel;
use \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as Klevu_Product_Attribute_Collection;

class LoadAttribute extends  AbstractModel implements LoadAttributeInterface
{


    protected $_storeModelStoreManagerInterface;
    protected $_frameworkModelResource;
    protected $_productData;
    protected $_searchHelperConfig;
    protected $_productAttributeCollection;
    protected $_searchHelperCompat;
    protected $_klevuSync;
    protected $_stockHelper;

    public function __construct(
        \Klevu\Search\Model\Context $context,
        Klevu_ProductData $productdata,
        Klevu_Product_Attribute_Collection $productAttributeCollection

    ){
        $this->_storeModelStoreManagerInterface = $context->getStoreManagerInterface();
        $this->_frameworkModelResource = $context->getResourceConnection();
        $this->_productData = $productdata;
        $this->_searchHelperConfig = $context->getHelperManager()->getConfigHelper();
        $this->_productAttributeCollection = $productAttributeCollection;
        $this->_searchHelperCompat = $context->getHelperManager()->getCompatHelper();
        $this->_searchHelperData = $context->getHelperManager()->getDataHelper();
        $this->_klevuSync = $context->getSync();
        $this->_stockHelper = $context->getHelperManager()->getStockHelper();

    }

    /**
     * Add the Product Sync data to each product in the given list. Updates the given
     * list directly to save memory.
     *
     * @param array $products An array of products. Each element should be an array with
     *                        containing an element with "id" as the key and the product
     *                        ID as the value.
     *
     * @return $this
     */
    public function addProductSyncData(&$products)
    {

        $product_ids = [];
        $parent_ids = [];
        $product_stock_ids = []; //modification in config product stock management
        foreach ($products as $product) {
            $product_ids[] = $product['product_id'];
            $product_stock_ids[$product['product_id']] = $product['parent_id'];
            if ($product['parent_id'] != 0) {
                $product_ids[] = $product['parent_id'];
                $parent_ids[] = $product['parent_id'];
                $product_stock_ids[$product['parent_id']] = $product['parent_id'];
            }

        }
        $product_ids = array_unique($product_ids);
        $parent_ids = array_unique($parent_ids);
        
        if ($this->_searchHelperConfig->isCollectionMethodEnabled()) {
            $data = $this->loadProductDataCollection($product_ids);
        }

        // Get url product from database
        $url_rewrite_data = $this->getUrlRewriteData($product_ids);
        $attribute_map = $this->getAttributeMap();
        $base_url = $this->_productData->getBaseUrl($this->_storeModelStoreManagerInterface->getStore());
        $currency = $this->_productData->getCurrency($this->_storeModelStoreManagerInterface->getStore());
        $rejectedProducts = array();
        $rc = 0;
        $rp = 0;

        foreach ($products as $index => &$product) {

            try {
                if ($rc % 5 == 0) {
                    if ($this->_klevuSync->rescheduleIfOutOfMemory()) {
                        return $rc;
                    }
                }

                if ($this->_searchHelperConfig->isCollectionMethodEnabled()) {
                    $item = $data->getItemById($product['product_id']);
                    $parent = ($product['parent_id'] != 0) ?  $data->getItemById($product['parent_id']) : null;
                    $this->_searchHelperData->log(\Zend\Log\Logger::DEBUG, sprintf("Load by collection method for product ID %d", $product['product_id']));
                } else {
                    $item = \Magento\Framework\App\ObjectManager::getInstance()->create('\Magento\Catalog\Model\Product')->load($product['product_id']);
                    $item->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
                    $parent = ($product['parent_id'] != 0) ?  \Magento\Framework\App\ObjectManager::getInstance()->create('\Magento\Catalog\Model\Product')->load($product['parent_id'])->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID): null;
                }

                if (!$item) {
                    // Product data query did not return any data for this product
                    // Remove it from the list to skip syncing it
                    $rejectedProducts[$rp]['product_id'] = $product['product_id'];
                    $rejectedProducts[$rp]['parent_id'] = $product['parent_id'];
                    $this->_searchHelperData->log(\Zend\Log\Logger::WARN, sprintf("Failed to retrieve data for product ID %d", $product['product_id']));
                    unset($products[$index]);
                    $rp++;
                    continue;
                }
                if((!isset($parent) || is_null($parent)) && $product['parent_id'] != 0){
                    $rejectedProducts[$rp]['product_id'] = $product['product_id'];
                    $rejectedProducts[$rp]['parent_id'] = $product['parent_id'];
                    $this->_searchHelperData->log(\Zend\Log\Logger::WARN, sprintf("Failed to retrieve data for parent ID %d", $product['parent_id']));
                    unset($products[$index]);
                    $rp++;
                    continue;
                }


                $this->processProductBefore($product,$parent,$item);
                // Add data from mapped attributes
                foreach ($attribute_map as $key => $attributes) {
                    $product[$key] = null;
                    switch ($key) {
                        case "boostingAttribute":
                            $product[$key] = $this->_productData->getBoostingAttribute($key,$attributes,$parent,$item,$product);
                            break;
                        case "rating":
                            $product[$key] = $this->_productData->getRating($key,$attributes,$parent,$item,$product);
                            break;
                        case "otherAttributeToIndex":
                        case "other":
                            $product[$key] = [];
                            foreach ($attributes as $attribute) {
                                if ($item && $item->getData($attribute)) {
                                    $product[$key][$attribute] = $this->getAttributeData($attribute, $item->getData($attribute));
                                } elseif ($parent) {
                                    if($parent->getData($attribute)) {
                                        $product[$key][$attribute] = $this->getAttributeData($attribute, $parent->getData($attribute));
                                    }
                                }
                            }
                            break;
                        case "sku":
                            $product[$key] = $this->_productData->getSku($key,$attributes,$parent,$item,$product);
                            break;
                        case "name":
                            $product[$key] = $this->_productData->getName($key,$attributes,$parent,$item,$product);
                            break;
                        case "image":
                            $product[$key] = $this->_productData->getImage($key,$attributes,$parent,$item,$product,$this->_storeModelStoreManagerInterface->getStore());
                            break;
                        case "salePrice":
                            // Default to 0 if price can't be determined
                            $product[$key] = $this->_productData->getSalePriceData($parent,$item,$product,$this->_storeModelStoreManagerInterface->getStore());
                            $product['startPrice'] = $this->_productData->getStartPriceData($parent,$item,$product,$this->_storeModelStoreManagerInterface->getStore());
                            $product['toPrice'] = $this->_productData->getToPriceData($parent,$item,$product,$this->_storeModelStoreManagerInterface->getStore());
                            break;
                        case "price":
                            $product[$key] = $this->_productData->getPriceData($parent,$item,$product,$this->_storeModelStoreManagerInterface->getStore());
                            break;
                        case "dateAdded":
                            $product[$key] = $this->_productData->getDateAdded($key,$attributes,$parent,$item,$product,$this->_storeModelStoreManagerInterface->getStore());
                            break;

                        default:
                            foreach ($attributes as $attribute) {
                                if ($item->getData($attribute)) {
                                    $product[$key] = $this->getAttributeData($attribute, $item->getData($attribute));
                                    break;
                                } elseif ($parent && $parent->getData($attribute)) {
                                    $product[$key] = $this->getAttributeData($attribute, $parent->getData($attribute));
                                    break;
                                }
                            }
                    }
                }

                $product['product_type'] = $this->_productData->getProductType($parent,$item);
                $product['currency'] = $currency;
                $product['category'] =  $this->_productData->getCategory($parent,$item);
                $product['listCategory'] = $this->_productData->getListCategory($parent,$item);
                $product['categoryIds'] =  $this->_productData->getAllCategoryId($parent,$item);
                $product['categoryPaths'] = $this->_productData->getAllCategoryPaths($parent,$item);
                $product['groupPrices'] = $this->_productData->getGroupPricesData($item);
                $product['url'] = $this->_productData->getProductUrlData($parent,$item,$url_rewrite_data,$product,$base_url);
                $product['inStock'] = $this->_stockHelper->getKlevuStockStatus($parent,$item);
                $product['itemGroupId'] = $this->_productData->getItemGroupId($product['parent_id'],$product)?$this->_productData->getItemGroupId($product['parent_id'],$product):0;
                $product['id'] = $this->_productData->getId($product['product_id'],$product['parent_id']);
                $this->processProductAfter($product,$parent,$item);
                if ($item) {
                    if (!$this->_searchHelperConfig->isCollectionMethodEnabled()) {
                        $item->clearInstance();
                    }
                    $item = null;
                }
                if ($parent) {
                    if (!$this->_searchHelperConfig->isCollectionMethodEnabled()) {
                        $parent->clearInstance();
                    }
                    $parent = null;
                }
            } catch (\Exception $e) {
                $this->_searchHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
                $markAsSync = [];
                if (!empty($product['parent_id']) && !empty($product['product_id'])) {
                    $markAsSync[] = [$product['product_id'],$product['parent_id'],$this->_storeModelStoreManagerInterface->getStore()->getId(),0,$this->_searchHelperCompat->now(),"products"];
                    $write =  $this->_frameworkModelResource->getConnection("core_write");
                    $query = "replace into ".$this->_frameworkModelResource->getTableName('klevu_product_sync')
                        . "(product_id, parent_id, store_id, last_synced_at, type,error_flag) values "
                        . "(:product_id, :parent_id, :store_id, :last_synced_at, :type,:error_flag)";
                    $binds = [
                        'product_id' => $markAsSync[0][0],
                        'parent_id' => $markAsSync[0][1],
                        'store_id' => $markAsSync[0][2],
                        'last_synced_at'  => $markAsSync[0][4],
                        'type' => $markAsSync[0][5],
                        'error_flag' => 1
                    ];
                    $write->query($query, $binds);
                }
                //unset($products[$index]);
                continue;
            }

            unset($product['product_id']);
            unset($product['parent_id']);
        }

        if(count($rejectedProducts) > 0) {
            $this->_searchHelperData->log(\Zend\Log\Logger::WARN, sprintf("Because of indexing issue or invalid data we cannot synchronize product IDs %d", implode(',', array_map(function($el){ return $el['product_id']; }, $rejectedProducts))));
           \Magento\Framework\App\ObjectManager::getInstance()->create('Klevu\Search\Model\Product\MagentoProductActionsInterface')->deleteProducts($rejectedProducts);
        }
        return $this;
    }

    /**
     * Process product data if wannt to add any extra information from third party module
     * @param $product
     * @param $parent
     * @param $item
     * @return $this|mixed
     */
    public function processProductBefore(&$product ,&$parent,&$item){
        return $this;
    }

    /**
     * Process product data if wannt to add any extra information from third party module
     * @param $product
     * @param $parent
     * @param $item
     * @return $this|mixed
     */
    public function processProductAfter(&$product ,&$parent,&$item){
        return $this;
    }


    /**
     * Load product data uisng magento collection method
     * @param $product_ids
     * @return array
     *
     */
    public function loadProductDataCollection($product_ids){

        $data = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Catalog\Model\ResourceModel\Product\Collection')
            ->addAttributeToSelect($this->getUsedMagentoAttributes())
            ->addIdFilter($product_ids)
            ->setStore($this->_storeModelStoreManagerInterface->getStore())
            ->addStoreFilter()
            ->addMinimalPrice()
            ->addFinalPrice();
        $data->setFlag('has_stock_status_filter', false);
        $data->load()
            ->addCategoryIds();
        return  $data;
    }


    /**
     * Return the attribute codes for all attributes currently used in
     * configurable products.
     *
     * @return array
     */
    public function getConfigurableAttributes()
    {
        $select = $this->_frameworkModelResource->getConnection("core_write")
            ->select()
            ->from(
                ["a" => $this->_frameworkModelResource->getTableName("eav_attribute")],
                ["attribute" => "a.attribute_code"]
            )
            ->join(
                ["s" => $this->_frameworkModelResource->getTableName("catalog_product_super_attribute")],
                "a.attribute_id = s.attribute_id",
                ""
            )
            ->group(["a.attribute_code"]);
        return $this->_frameworkModelResource->getConnection("core_write")->fetchCol($select);
    }


    /**
     * Return a list of all Magento attributes that are used by Product Sync
     * when collecting product data.
     *
     * @return array
     */
    public function getUsedMagentoAttributes()
    {
        $result = [];
        foreach ($this->getAttributeMap() as $attributes) {
            $result = array_merge($result, $attributes);
        }
        $result = array_merge($result, $this->getConfigurableAttributes());
        return array_unique($result);
    }

    /**
     * Return the URL rewrite data for the given products for the current store.
     *
     * @param array $product_ids A list of product IDs.
     *
     * @return array A list with product IDs as keys and request paths as values.
     */
    protected function getUrlRewriteData($product_ids)
    {
        $stmt = $this->_frameworkModelResource->getConnection("core_write")->query(
            $this->_searchHelperCompat->getProductUrlRewriteSelect($product_ids, $this->_storeModelStoreManagerInterface->getStore()->getId())
        );
        $data = [];

        while ($row = $stmt->fetch()) {
            if (!isset($data[$row['entity_id']])) {
                $data[$row['entity_id']] = $row['request_path'];
            }
        }
        return $data;
    }



    /**
     * Return a map of Klevu attributes to Magento attributes.
     *
     * @return array
     */
    protected function getAttributeMap()
    {
        if (!$this->hasData('attribute_map')) {
            $attribute_map = [];
            $automatic_attributes = $this->getAutomaticAttributes();
            $attribute_map = $this->prepareAttributeMap($attribute_map, $automatic_attributes);

            // Add otherAttributeToIndex to $attribute_map.
            $otherAttributeToIndex = $this->_searchHelperConfig->getOtherAttributesToIndex($this->_storeModelStoreManagerInterface->getStore());

            if (!empty($otherAttributeToIndex)) {
                $attribute_map['otherAttributeToIndex'] = $otherAttributeToIndex;
            }
            // Add boostingAttribute to $attribute_map.
            $boosting_value = $this->_searchHelperConfig->getBoostingAttribute($this->_storeModelStoreManagerInterface->getStore());
            if ($boosting_value != "use_boosting_rule") {
                if (($boosting_attribute = $this->_searchHelperConfig->getBoostingAttribute($this->_storeModelStoreManagerInterface->getStore())) && !is_null($boosting_attribute)) {
                    $attribute_map['boostingAttribute'][] = $boosting_attribute;
                }
            }
            $this->setData('attribute_map', $attribute_map);
        }
        return $this->getData('attribute_map');
    }


    /**
     * Returns an array of all automatically matched attributes. Includes defaults and filterable
     * in search attributes.
     *
     * @return array
     */
    public function getAutomaticAttributes()
    {
        if (!$this->hasData('automatic_attributes')) {
            // Default mapped attributes
            $default_attributes = $this->_searchHelperConfig->getDefaultMappedAttributes();
            $attributes = [];
            for ($i = 0; $i < count($default_attributes['klevu_attribute']); $i++) {
                $attributes[] = [
                    'klevu_attribute' => $default_attributes['klevu_attribute'][$i],
                    'magento_attribute' => $default_attributes['magento_attribute'][$i]
                ];
            }
            // Get all layered navigation / filterable in search attributes
            foreach ($this->getLayeredNavigationAttributes() as $layeredAttribute) {
                $attributes[] =  [
                    'klevu_attribute' => 'other',
                    'magento_attribute' => $layeredAttribute
                ];
            }
            $this->setData('automatic_attributes', $attributes);
            // Update the store system config with the updated automatic attributes map.
            $this->_searchHelperConfig->setAutomaticAttributesMap($attributes, $this->_storeModelStoreManagerInterface->getStore());
        }
        return $this->getData('automatic_attributes');
    }
    /**
     * Takes system configuration attribute data and adds to $attribute_map
     *
     * @param $attribute_map
     * @param $additional_attributes
     *
     * @return array
     */
    protected function prepareAttributeMap($attribute_map, $additional_attributes)
    {
        foreach ($additional_attributes as $mapping) {
            if (!isset($attribute_map[$mapping['klevu_attribute']])) {
                $attribute_map[$mapping['klevu_attribute']] = [];
            }
            $attribute_map[$mapping['klevu_attribute']][] = $mapping['magento_attribute'];
        }
        return $attribute_map;
    }


    /**
     * Return the attribute codes for all filterable in search attributes.
     *
     * @return array
     */
    protected function getLayeredNavigationAttributes()
    {
        $attributes = $this->_searchHelperConfig->getDefaultMappedAttributes();
        $select = $this->_frameworkModelResource->getConnection("core_write")
            ->select()
            ->from(
                ["a" => $this->_frameworkModelResource->getTableName("eav_attribute")],
                ["attribute" => "a.attribute_code"]
            )
            ->join(
                ["ca" => $this->_frameworkModelResource->getTableName("catalog_eav_attribute")],
                "ca.attribute_id = a.attribute_id",
                ""
            )
            // Only if the attribute is filterable in search, i.e. attribute appears in search layered navigation.
            ->where("ca.is_filterable_in_search = ?", "1")
            // Make sure we exclude the attributes thar synced by default.
            ->where("a.attribute_code NOT IN(?)", array_unique($attributes['magento_attribute']))
            ->group(["attribute_code"]);
        return $this->_frameworkModelResource->getConnection("core_write")->fetchCol($select);
    }


    /**
     * Returns either array containing the label and value(s) of an attribute, or just the given value
     *
     * In the case that there are multiple options selected, all values are returned
     *
     * @param string $code
     * @param null   $value
     *
     * @return array|string
     */
    protected function getAttributeData($code, $value = null)
    {
        if (!empty($value)) {
            if (!$attribute_data = $this->getData('attribute_data')) {
                $attribute_data = [];
                $collection = $this->_productAttributeCollection
                    ->addFieldToFilter('attribute_code', ['in' => $this->getUsedMagentoAttributes()]);

                foreach ($collection as $attr) {
                    $attr->setStoreId($this->_storeModelStoreManagerInterface->getStore()->getId());
                    $attribute_data[$attr->getAttributeCode()] = [
                        'label' => $attr->getStoreLabel($this->_storeModelStoreManagerInterface->getStore()->getId()),
                        'values' =>  array() // compatibility with php 7.1.x versions
                    ];
                    if ($attr->usesSource()) {
                        foreach ($attr->setStoreId($this->_storeModelStoreManagerInterface->getStore()->getId())->getSource()->getAllOptions(false) as $option) {
                            if (is_array($option['value'])) {
                                foreach ($option['value'] as $sub_option) {
                                    if (!empty($sub_option)) {
                                        $attribute_data[$attr->getAttributeCode()]['values'][$sub_option['value']] =$sub_option['label'];
                                    }
                                }
                            } else {
                                $attribute_data[$attr->getAttributeCode()]['values'][$option['value']] = $option['label'];
                            }
                        }
                    }
                }
                $this->setData('attribute_data', $attribute_data);
            }
            // make sure the attribute exists
            if (isset($attribute_data[$code])) {
                // was $value passed a parameter?
                if (!is_null($value)) {
                    // If not values are set on attribute_data for the attribute, return just the value passed. (attributes like: name, description etc)
                    if (empty($attribute_data[$code]['values'])) {
                        return $value;
                    }

                    // break up our value into an array by a comma, this is for catching multiple select attributes.
                    if (is_array($value)) {
                        $values = $value;
                    } else {
                        $values = explode(",", $value);
                    }
                    // loop over our array of attribute values
                    foreach ($values as $key => $valueOption) {
                        // if there is a value on the attribute_data use that value (it will be the label for a dropdown select attribute)
                        if (isset($attribute_data[$code]['values'][$valueOption])) {
                            $values[$key] = $attribute_data[$code]['values'][$valueOption];
                        } else { // If no label was found, log an error and unset the value.
                            $this->_searchHelperData->log(\Zend\Log\Logger::WARN, sprintf("Attribute: %s option label was not found, option ID provided: %s", $code, $valueOption));
                            unset($values[$key]);
                        }
                    }
                    // If there was only one value in the array, return the first (select menu, single option), or if there was more, return them all (multi-select).
                    if (count($values) == 1) {
                        if (is_array($values)) {
                            $valuesAll = array_values($values);
                            $attribute_data[$code]['values'] = array_shift($valuesAll);
                            ;
                        } else {
                            $attribute_data[$code]['values'] = $values;
                        }
                    } else {
                        $attribute_data[$code]['values'] =  $values;
                    }
                }
                return $attribute_data[$code];
            }
            $result['label'] = $code;
            $result['values'] = $value;
            return $result;
        }
    }
}