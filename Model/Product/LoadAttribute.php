<?php

namespace Klevu\Search\Model\Product;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Api\Provider\Sync\ReservedAttributeCodesProviderInterface;
use Klevu\Search\Api\Service\Catalog\Product\StockServiceInterface;
use Klevu\Search\Model\Attribute\Rating;
use Klevu\Search\Model\Attribute\ReviewCount;
use Klevu\Search\Model\Context as KlevuContext;
use Klevu\Search\Model\Klevu\KlevuFactory;
use Klevu\Search\Model\Klevu\ResourceModel\Klevu\Collection as KlevuProductSyncCollection;
use Klevu\Search\Model\Product\ProductInterface as Klevu_ProductData;
use Magento\Catalog\Api\Data\ProductInterface as MagentoProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\Group as CustomerGroup;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as Klevu_Product_Attribute_Collection;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class LoadAttribute extends AbstractModel implements LoadAttributeInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var ResourceConnection
     */
    protected $_frameworkModelResource;
    /**
     * @var ProductInterface
     */
    protected $_productData;
    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;
    /**
     * @var Klevu_Product_Attribute_Collection
     */
    protected $_productAttributeCollection;
    /**
     * @var \Klevu\Search\Helper\Compat
     */
    protected $_searchHelperCompat;
    /**
     * @var \Klevu\Search\Model\Sync
     */
    protected $_klevuSync;
    /**
     * @var \Klevu\Search\Helper\Stock
     */
    protected $_stockHelper;
    /**
     * @var KlevuFactory
     */
    protected $_klevuFactory;
    /**
     * @var StockServiceInterface
     */
    private $stockService;
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var ReservedAttributeCodesProviderInterface
     */
    private $reservedAttributeCodesProvider;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param KlevuContext $context
     * @param ProductInterface $productdata
     * @param Klevu_Product_Attribute_Collection $productAttributeCollection
     * @param KlevuFactory $klevuFactory
     * @param StockServiceInterface|null $stockService
     * @param ProductCollectionFactory|null $productCollectionFactory
     * @param ReservedAttributeCodesProviderInterface|null $reservedAttributeCodesProvider
     * @param ProductRepositoryInterface|null $productRepository
     */
    public function __construct(
        KlevuContext $context,
        Klevu_ProductData $productdata,
        Klevu_Product_Attribute_Collection $productAttributeCollection,
        KlevuFactory $klevuFactory,
        StockServiceInterface $stockService = null,
        ProductCollectionFactory $productCollectionFactory = null,
        ReservedAttributeCodesProviderInterface $reservedAttributeCodesProvider = null,
        ProductRepositoryInterface $productRepository = null
    ) {
        $this->_storeModelStoreManagerInterface = $context->getStoreManagerInterface();
        $this->_frameworkModelResource = $context->getResourceConnection();
        $this->_productData = $productdata;
        $this->_searchHelperConfig = $context->getHelperManager()->getConfigHelper();
        $this->_productAttributeCollection = $productAttributeCollection;
        $this->_searchHelperCompat = $context->getHelperManager()->getCompatHelper();
        $this->_searchHelperData = $context->getHelperManager()->getDataHelper();
        $this->_klevuSync = $context->getSync();
        $this->_stockHelper = $context->getHelperManager()->getStockHelper();
        $this->_klevuFactory = $klevuFactory;
        $objectManager = ObjectManager::getInstance();
        $this->stockService = $stockService ?:
            $objectManager->get(StockServiceInterface::class);
        $this->productCollectionFactory = $productCollectionFactory ?:
            $objectManager->get(ProductCollectionFactory::class);
        // We don't use OM here as there is no explicit preference for the ReservedAttributeCodesProviderInterface
        //  The implementation depends on context and is added through di.xml
        if (null !== $reservedAttributeCodesProvider) {
            $this->reservedAttributeCodesProvider = $reservedAttributeCodesProvider;
        }
        $this->productRepository = $productRepository ?:
            $objectManager->get(ProductRepositoryInterface::class);
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
            if ((int)$product['parent_id'] !== 0) {
                $product_ids[] = $product['parent_id'];
                $parent_ids[] = $product['parent_id'];
                $product_stock_ids[$product['parent_id']] = $product['parent_id'];
            }
        }
        $product_ids = array_unique($product_ids);
        $parent_ids = array_unique($parent_ids);
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore();
            $website = $store->getWebsite();
        } catch (NoSuchEntityException $exception) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf('Website Could not be loaded: %s', $exception->getMessage())
            );

            return $this;
        }

        $this->stockService->clearCache();
        $this->stockService->preloadKlevuStockStatus(array_merge($product_ids, $parent_ids), $website->getId());

        $isCollectionMethod = $this->_searchHelperConfig->isCollectionMethodEnabled();

        if ($isCollectionMethod) {
            $data = $this->loadProductDataCollection($product_ids);
        }

        // Get url product from database
        $url_rewrite_data = $this->getUrlRewriteData($product_ids);
        $attribute_map = $this->getAttributeMap();
        $baseUrl = $this->_productData->getBaseUrl($store);
        $currency = $this->_productData->getCurrency();
        try {
            $store->setCurrentCurrencyCode($currency);
        } catch (LocalizedException $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf('Currency could not be set on store: %s', $e->getMessage())
            );

            return $this;
        }
        $rejectedProducts = [];
        $rp = 0;

        foreach ($products as $index => &$product) {
            try {
                if ($isCollectionMethod) {
                    $item = $data->getItemById($product['product_id']);
                    $parent = ((int)$product['parent_id'] !== 0) ? $data->getItemById($product['parent_id']) : null;
                    $this->logLoadByMessage($product, true);
                } else {
                    $origItem = $this->productRepository->getById($product['product_id']);
                    $item = clone $origItem;
                    $item->setData('customer_group_id', CustomerGroup::NOT_LOGGED_IN_ID);
                    $parent = null;
                    if ((int)$product['parent_id'] !== 0) {
                        $origParent = $this->productRepository->getById($product['parent_id']);
                        $parent = clone $origParent;
                        $parent->setData('customer_group_id', CustomerGroup::NOT_LOGGED_IN_ID);
                    }
                    $this->logLoadByMessage($product);
                }

                if (!$item) {
                    // Product data query did not return any data for this product
                    // Remove it from the list to skip syncing it
                    $rejectedProducts[$rp]['product_id'] = $product['product_id'];
                    $rejectedProducts[$rp]['parent_id'] = $product['parent_id'];
                    $this->_searchHelperData->log(
                        LoggerConstants::ZEND_LOG_WARN,
                        sprintf("Failed to retrieve data for product ID %d", $product['product_id'])
                    );
                    unset($products[$index]);
                    $rp++;
                    continue;
                }
                if ((!isset($parent)) && isset($product['parent_id']) && (int)$product['parent_id'] !== 0) {
                    $rejectedProducts[$rp]['product_id'] = $product['product_id'];
                    $rejectedProducts[$rp]['parent_id'] = $product['parent_id'];
                    $this->_searchHelperData->log(
                        LoggerConstants::ZEND_LOG_WARN,
                        sprintf("Failed to retrieve data for parent ID %d", $product['parent_id'])
                    );
                    unset($products[$index]);
                    $rp++;
                    continue;
                }

                $this->processProductBefore($product, $parent, $item);
                // Add data from mapped attributes
                foreach ($attribute_map as $key => $attributes) {
                    $product = $this->mapProductAttributes($item, $store, $product, $attributes, $key, $parent);
                }

                $product['product_type'] = $this->_productData->getProductType($parent, $item);
                $product['isCustomOptionsAvailable'] = $this->_productData->isCustomOptionsAvailable($parent, $item);
                $product['currency'] = $currency;
                $product['otherPrices'] = $this->_productData->getOtherPrices($item, $currency);
                $product['category'] = $this->_productData->getCategory($parent, $item);
                $product['listCategory'] = $this->_productData->getListCategory($parent, $item);
                $product['categoryIds'] = $this->_productData->getAllCategoryId($parent, $item);
                $product['categoryPaths'] = $this->_productData->getAllCategoryPaths($parent, $item);
                $product['groupPrices'] = $this->_productData->getGroupPricesData($item);
                $product['url'] = $this->_productData->getProductUrlData(
                    $parent,
                    $item,
                    $url_rewrite_data,
                    $product,
                    $baseUrl
                );
                $product['inStock'] = $this->_stockHelper->getKlevuStockStatus($parent, $item, $website->getId());
                $product['itemGroupId'] = $this->_productData->getItemGroupId($product['parent_id'], $product) ?: '';
                if ((int)$product['itemGroupId'] === 0) {
                    $product['itemGroupId'] = ''; // Ref: KS-15006
                }
                $product['id'] = $this->_productData->getId($product['product_id'], $product['parent_id']);
                $this->processProductAfter($product, $parent, $item);
                if ($item) {
                    if (!$isCollectionMethod) {
                        $item->clearInstance();
                    }
                    $item = null;
                }
                if ($parent) {
                    if (!$isCollectionMethod) {
                        $parent->clearInstance();
                    }
                    $parent = null;
                }
            } catch (\Exception $e) {
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_CRIT,
                    sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage())
                );
                $markAsSync = [];
                if (!empty($product['parent_id']) && !empty($product['product_id'])) {
                    $markAsSync[] = [
                        $product['product_id'],
                        $product['parent_id'],
                        $store->getId(),
                        0,
                        $this->_searchHelperCompat->now(),
                        "products"
                    ];
                    $write = $this->_frameworkModelResource->getConnection("core_write");
                    $query = "replace into " . $this->_frameworkModelResource->getTableName('klevu_product_sync')
                        . "(product_id, parent_id, store_id, last_synced_at, type,error_flag) values "
                        . "(:product_id, :parent_id, :store_id, :last_synced_at, :type,:error_flag)";
                    $binds = [
                        'product_id' => $markAsSync[0][0],
                        'parent_id' => $markAsSync[0][1],
                        'store_id' => $markAsSync[0][2],
                        'last_synced_at' => $markAsSync[0][4],
                        'type' => $markAsSync[0][5],
                        'error_flag' => 1
                    ];
                    $write->query($query, $binds);
                }
                continue;
            }
            unset($product['product_id'], $product['parent_id']);
        }

        if (count($rejectedProducts) > 0) {
            // Can not be injected via construct due to circular dependency
            $magentoProductActions = ObjectManager::getInstance()->get(MagentoProductActionsInterface::class);
            if (!$this->_searchHelperConfig->displayOutofstock()) {
                $rejectedProducts_data = [];
                $r = 0;
                foreach ($rejectedProducts as $rvalue) {
                    $idData = $this->checkIdexitsInDb(
                        $store->getId(),
                        $rvalue["product_id"],
                        $rvalue["parent_id"]
                    );
                    $ids = $idData->getData();
                    if (count($ids) > 0) {
                        $rejectedProducts_data[$r]["product_id"] = $rvalue["product_id"];
                        $rejectedProducts_data[$r]["parent_id"] = $rvalue["parent_id"];
                        $r++;
                    }
                }
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_WARN,
                    sprintf(
                        "Because of indexing issue or invalid data we cannot synchronize product IDs %s",
                        implode(
                            ',',
                            array_map(
                                static function ($el) {
                                    return $el['product_id'];
                                },
                                $rejectedProducts_data
                            )
                        )
                    )
                );
                $magentoProductActions->deleteProducts($rejectedProducts_data);
            } else {
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_WARN,
                    sprintf(
                        "Because of indexing issue or invalid data we cannot synchronize product IDs %s",
                        implode(
                            ',',
                            array_map(
                                static function ($el) {
                                    return $el['product_id'];
                                },
                                $rejectedProducts
                            )
                        )
                    )
                );
                $magentoProductActions->deleteProducts($rejectedProducts);
            }
        }

        return $this;
    }

    /**
     * Process product data if wannt to add any extra information from third party module
     *
     * @param array $product
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return $this
     */
    public function processProductBefore(&$product, &$parent, &$item)
    {
        return $this;
    }

    /**
     * Process product data if wannt to add any extra information from third party module
     *
     * @param array $product
     * @param MagentoProductInterface|null $parent
     * @param MagentoProductInterface $item
     *
     * @return $this
     */
    public function processProductAfter(&$product, &$parent, &$item)
    {
        return $this;
    }

    /**
     * Load product data uisng magento collection method
     *
     * @param array $productIds
     * @param int|null $storeId
     *
     * @return ProductCollection|null
     */
    public function loadProductDataCollection($productIds, $storeId = null)
    {
        $collection = $this->productCollectionFactory->create();
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore($storeId);
        } catch (\Exception $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, sprintf("Store not found %d", $storeId));

            return null;
        }
        $collection->addAttributeToSelect($this->getUsedMagentoAttributes());
        $collection->addAttributeToSelect('price_type');
        $collection->addIdFilter($productIds);
        $collection->setStore($store);
        $collection->addStoreFilter();
        $collection->setFlag('has_stock_status_filter', true);

        $collection->load();
        $collection->addCategoryIds();

        return $collection;
    }

    /**
     * Return the attribute codes for all attributes currently used in
     * configurable products.
     *
     * @return array
     */
    public function getConfigurableAttributes()
    {
        $connection = $this->_frameworkModelResource->getConnection("core_write");
        $select = $connection->select();
        $select->from(
            ["a" => $this->_frameworkModelResource->getTableName("eav_attribute")],
            ["attribute" => "a.attribute_code"]
        );
        $select->join(
            ["s" => $this->_frameworkModelResource->getTableName("catalog_product_super_attribute")],
            "a.attribute_id = s.attribute_id",
            ""
        );
        $select->group(["a.attribute_code"]);

        return $connection->fetchCol($select);
    }

    /**
     * Return a list of all Magento attributes that are used by Product Sync
     * when collecting product data.
     *
     * @return array
     */
    public function getUsedMagentoAttributes()
    {
        $attributeMapValues = array_values($this->getAttributeMap());
        $flatAttributesArray = array_merge(...$attributeMapValues);
        $result = array_merge($flatAttributesArray, $this->getConfigurableAttributes());

        return array_unique($result);
    }

    /**
     * Return the URL rewrite data for the given products for the current store.
     *
     * @param array $productIds A list of product IDs.
     *
     * @return array A list with product IDs as keys and request paths as values.
     */
    protected function getUrlRewriteData($productIds)
    {
        $data = [];
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore();
        } catch (NoSuchEntityException $e) {
            $this->_logger->error($e->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);

            return $data;
        }
        $connection = $this->_frameworkModelResource->getConnection("core_write");
        $stmt = $connection->query(
            $this->_searchHelperCompat->getProductUrlRewriteSelect($productIds, $store->getId())
        );

        try {
            while ($row = $stmt->fetch()) {
                if (!isset($data[$row['entity_id']])) {
                    $data[$row['entity_id']] = $row['request_path'];
                }
            }
        } catch (\Zend_Db_Statement_Exception $e) {
            $this->_logger->error($e->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);
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
            $attributeMap = [];
            try {
                $store = $this->_storeModelStoreManagerInterface->getStore();
            } catch (NoSuchEntityException $e) {
                $this->_logger->error($e->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);

                return $attributeMap;
            }
            $automaticAttributes = $this->getAutomaticAttributes();
            $attributeMap = $this->prepareAttributeMap($attributeMap, $automaticAttributes);

            // Add otherAttributeToIndex to $attribute_map.
            $otherAttributeToIndex = $this->_searchHelperConfig->getOtherAttributesToIndex($store);

            if (!empty($otherAttributeToIndex)) {
                $attributeMap['otherAttributeToIndex'] = $otherAttributeToIndex;
            }

            $reservedAttributeCodes = $this->reservedAttributeCodesProvider
                ? $this->reservedAttributeCodesProvider->execute()
                : [];
            if ($reservedAttributeCodes) {
                if (isset($attributeMap['other']) && is_array($attributeMap['other'])) {
                    $attributeMap['other'] = array_diff($attributeMap['other'], $reservedAttributeCodes);
                }
                if (isset($attributeMap['otherAttributeToIndex']) && is_array($attributeMap['otherAttributeToIndex'])) {
                    $attributeMap['otherAttributeToIndex'] = array_diff(
                        $attributeMap['otherAttributeToIndex'],
                        $reservedAttributeCodes
                    );
                }
            }

            // Add boostingAttribute to $attribute_map.
            $boosting_value = $this->_searchHelperConfig->getBoostingAttribute($store);
            if (($boosting_value !== "use_boosting_rule") &&
                ($boosting_attribute = $this->_searchHelperConfig->getBoostingAttribute($store)) &&
                null !== $boosting_attribute
            ) {
                $attributeMap['boostingAttribute'][] = $boosting_attribute;
            }
            $this->setData('attribute_map', $attributeMap);
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
            $attributes = [];
            try {
                $store = $this->_storeModelStoreManagerInterface->getStore();
            } catch (NoSuchEntityException $e) {
                $this->_logger->error($e->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);

                return $attributes;
            }
            $defaultAttributes = $this->_searchHelperConfig->getDefaultMappedAttributes();
            $iMaxDefaultAttrCnt = count($defaultAttributes['klevu_attribute']);
            for ($i = 0; $i < $iMaxDefaultAttrCnt; $i++) {
                $attributes[] = [
                    'klevu_attribute' => $defaultAttributes['klevu_attribute'][$i],
                    'magento_attribute' => $defaultAttributes['magento_attribute'][$i]
                ];
            }
            // Get all layered navigation / filterable in search attributes
            foreach ($this->getLayeredNavigationAttributes() as $layeredAttribute) {
                $attributes[] = [
                    'klevu_attribute' => 'other',
                    'magento_attribute' => $layeredAttribute
                ];
            }
            $this->setData('automatic_attributes', $attributes);
            // Update the store system config with the updated automatic attributes map.
            $this->_searchHelperConfig->setAutomaticAttributesMap($attributes, $store);
        }

        return $this->getData('automatic_attributes');
    }

    /**
     * Takes system configuration attribute data and adds to $attribute_map
     *
     * @param array $attributeMap
     * @param array $additionalAttributes
     *
     * @return array
     */
    protected function prepareAttributeMap($attributeMap, $additionalAttributes)
    {
        foreach ($additionalAttributes as $mapping) {
            if (!isset($attributeMap[$mapping['klevu_attribute']])) {
                $attributeMap[$mapping['klevu_attribute']] = [];
            }
            $attributeMap[$mapping['klevu_attribute']][] = $mapping['magento_attribute'];
        }

        return $attributeMap;
    }

    /**
     * Return the attribute codes for all filterable in search attributes.
     *
     * @return array
     */
    protected function getLayeredNavigationAttributes()
    {
        $attributes = $this->_searchHelperConfig->getDefaultMappedAttributes();
        $connection = $this->_frameworkModelResource->getConnection("core_write");
        $select = $connection->select();
        $select->from(
            ["a" => $this->_frameworkModelResource->getTableName("eav_attribute")],
            ["attribute" => "a.attribute_code"]
        );
        $select->join(
            ["ca" => $this->_frameworkModelResource->getTableName("catalog_eav_attribute")],
            "ca.attribute_id = a.attribute_id",
            ""
        );
        // Only if the attribute is filterable in search, i.e. attribute appears in search layered navigation.
        $select->where("ca.is_filterable_in_search = ?", "1");
        // Make sure we exclude the attributes thar synced by default.
        $select->where("a.attribute_code NOT IN(?)", array_unique($attributes['magento_attribute']));
        $select->group(["attribute_code"]);

        return $connection->fetchCol($select);
    }

    /**
     * Returns either array containing the label and value(s) of an attribute, or just the given value
     *
     * In the case that there are multiple options selected, all values are returned
     *
     * @param string $code
     * @param mixed $value
     *
     * @return array|string|null
     */
    protected function getAttributeData($code, $value = null)
    {
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore();
        } catch (NoSuchEntityException $e) {
            $this->_logger->error($e->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);

            return null;
        }
        $currentStoreID = $store->getId();
        if (empty($value)) {
            return null;
        }
        //If store ID changes then fetch facets title
        if ((!$attributeData = $this->getData('attribute_data')) ||
            ($currentStoreID !== $this->getData('attributeStoreID'))
        ) {
            $this->setData('attributeStoreID', $store->getId());
            $attributeData = $this->getAttributeDataForStore($store);
            $this->setData('attribute_data', $attributeData);
        }
        // make sure the attribute exists
        if (isset($attributeData[$code])) {
            // was $value passed a parameter?
            if (null !== $value) {
                // If not values are set on attribute_data for the attribute, return just the value passed.
                // (attributes like: name, description etc)
                if (empty($attributeData[$code]['values'])) {
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
                    // if there is a value on the attribute_data use that value
                    // (it will be the label for a dropdown select attribute)
                    if (isset($attributeData[$code]['values'][$valueOption])) {
                        $values[$key] = $attributeData[$code]['values'][$valueOption];
                    } else { // If no label was found, log an error and unset the value.
                        $this->_searchHelperData->log(
                            LoggerConstants::ZEND_LOG_WARN,
                            sprintf(
                                "Attribute: %s option label was not found, option ID provided: %s",
                                $code,
                                $valueOption
                            )
                        );
                        unset($values[$key]);
                    }
                }
                // If there was only one value in the array, return the first (select menu, single option),
                // or if there was more, return them all (multi-select).
                if (is_array($values) && count($values) === 1) {
                    $valuesAll = array_values($values);
                    $attributeData[$code]['values'] = array_shift($valuesAll);
                } else {
                    $attributeData[$code]['values'] = $values;
                }
            }

            return $attributeData[$code];
        }
        $result['label'] = $code;
        $result['values'] = $value;

        return $result;
    }

    /**
     * Check product_id exits in klevu sync table.
     *
     * @param string|int $store_id
     * @param string|int $product_id
     * @param string|int $parent_id
     *
     * @return KlevuProductSyncCollection
     */
    protected function checkIdexitsInDb($store_id, $product_id, $parent_id)
    {
        $klevu = $this->_klevuFactory->create();
        /** @var KlevuProductSyncCollection $klevuCollection */
        $klevuCollection = $klevu->getCollection();
        $klevuCollection->addFieldToFilter($klevu->getKlevuField('type'), $klevu->getKlevuType('product'));
        $klevuCollection->addFieldToFilter($klevu->getKlevuField('product_id'), $product_id);
        $klevuCollection->addFieldToFilter($klevu->getKlevuField('parent_id'), $parent_id);
        $klevuCollection->addFieldToFilter($klevu->getKlevuField('store_id'), $store_id);

        return $klevuCollection->load();
    }

    /**
     * Logs load by message
     *
     * @param array $product
     * @param bool $isCollectionMethodFlag
     *
     * @return void
     */
    protected function logLoadByMessage($product, $isCollectionMethodFlag = false)
    {
        $id = $product['parent_id'] ? $product['parent_id'] . '-' : null;
        if ($isCollectionMethodFlag) {
            $msg = "Load by collection method for product ID " . $id . $product['product_id'];
        } else {
            $msg = "Load by object method for product ID " . $id . $product['product_id'];
        }
        $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_DEBUG, $msg);
    }

    /**
     * @param array $attributes
     * @param MagentoProductInterface $item
     * @param MagentoProductInterface|null $parent
     *
     * @return array
     */
    private function getOtherAttributes($attributes, $item, $parent)
    {
        $data = [];
        foreach ($attributes as $attribute) {
            if ($item && $item->getData($attribute)) {
                $data[$attribute] = $this->getAttributeData($attribute, $item->getData($attribute));
                continue;
            }
            if ($parent && $parent->getData($attribute)) {
                $data[$attribute] = $this->getAttributeData($attribute, $parent->getData($attribute));
            }
        }

        return $data;
    }

    /**
     * @param MagentoProductInterface $item
     * @param StoreInterface $store
     * @param array $product
     * @param array $attributes
     * @param string $key
     * @param MagentoProductInterface|null $parent
     *
     * @return array
     */
    private function mapProductAttributes(
        MagentoProductInterface $item,
        StoreInterface $store,
        array $product,
        array $attributes,
        $key,
        $parent = null
    ) {
        $product[$key] = null;
        switch ($key) {
            case "boostingAttribute":
                $product[$key] = $this->_productData->getBoostingAttribute(
                    $key,
                    $attributes,
                    $parent,
                    $item,
                    $product
                );
                break;
            case Rating::ATTRIBUTE_CODE:
                $rating = $this->_productData->getRating($key, $attributes, $parent, $item, $product);
                if (null !== $rating) {
                    $product[$key] = $rating;
                }
                break;
            case ReviewCount::ATTRIBUTE_CODE:
                // switch to rating_count rather than ReviewCount::ATTRIBUTE_CODE, this what Klevu API expects
                unset($product[ReviewCount::ATTRIBUTE_CODE]);
                // Intentional cascade
            case 'rating_count':
                $ratingCount = $this->_productData->getRatingCount($item, $parent);
                if (null !== $ratingCount) {
                    $product['rating_count'] = $ratingCount;
                }
                break;
            case "otherAttributeToIndex":
            case "other":
                $product[$key] = $this->getOtherAttributes($attributes, $item, $parent);
                break;
            case "sku":
                $product[$key] = $this->_productData->getSku($key, $attributes, $parent, $item, $product);
                break;
            case "name":
                $product[$key] = $this->_productData->getName(
                    $key,
                    $attributes,
                    $parent,
                    $item,
                    $product
                );
                break;
            case "image":
                $product[$key] = $this->_productData->getImage(
                    $key,
                    $attributes,
                    $parent,
                    $item,
                    $product,
                    $store
                );
                break;
            case "salePrice":
                // Default to 0 if price can't be determined
                $product[$key] = $this->_productData->getSalePriceData($parent, $item, $product, $store);
                $product['startPrice'] = $this->_productData->getStartPriceData(
                    $parent,
                    $item,
                    $product,
                    $store
                );
                $product['toPrice'] = $this->_productData->getToPriceData($parent, $item, $product, $store);
                break;
            case "price":
                $product[$key] = $this->_productData->getPriceData($parent, $item, $product, $store);
                break;
            case "dateAdded":
                $product[$key] = $this->_productData->getDateAdded(
                    $key,
                    $attributes,
                    $parent,
                    $item,
                    $product,
                    $store
                );
                break;
            case "visibility":
                //param values will be catalog, catalog-search, search after processing
                foreach ($attributes as $attribute) {
                    if ($parent) {

                        $product[$key] = $this->getAttributeData($attribute, $parent->getData($attribute));
                        $product[$key] = str_replace(
                            [' ', ","],
                            ['', "-"],
                            strtolower($product[$key]['values']->getText())
                        );
                        break;
                    }
                    if ($item->getData($attribute)) {
                        $product[$key] = $this->getAttributeData($attribute, $item->getData($attribute));
                        $product[$key] = str_replace(
                            [' ', ","],
                            ['', "-"],
                            strtolower($product[$key]['values']->getText())
                        );
                        break;
                    }
                }
                break;
            default:
                foreach ($attributes as $attribute) {
                    if ($item->getData($attribute)) {
                        $product[$key] = $this->getAttributeData($attribute, $item->getData($attribute));
                        break;
                    }
                    if ($parent && $parent->getData($attribute)) {
                        $product[$key] = $this->getAttributeData($attribute, $parent->getData($attribute));
                        break;
                    }
                }
        }

        return $product;
    }

    /**
     * @param StoreInterface $store
     *
     * @return array
     */
    private function getAttributeDataForStore(StoreInterface $store)
    {
        $attributeData = [];
        $attributeCollection = $this->_productAttributeCollection->addFieldToFilter(
            'attribute_code',
            ['in' => $this->getUsedMagentoAttributes()]
        );

        foreach ($attributeCollection as $attr) {
            $attributeData[$attr->getAttributeCode()] = [
                'label' => $attr->getStoreLabel($store->getId()),
                'values' => [] // compatibility with php 7.1.x versions
            ];
            if (!$attr->usesSource()) {
                continue;
            }
            $attr->setStoreId($store->getId());
            $source = $attr->getSource();
            $options = $source->getAllOptions(false);
            $data = [];
            foreach ($options as $option) {
                if (is_array($option['value'])) {
                    foreach ($option['value'] as $sub_option) {
                        if (!empty($sub_option)) {
                            $data[$sub_option['value']] = $sub_option['label'];
                        }
                    }
                } else {
                    $data[$option['value']] = $option['label'];
                }
            }
            $attributeData[$attr->getAttributeCode()]['values'] = $data;
        }

        return $attributeData;
    }
}
