<?php

namespace Klevu\Search\Helper;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Api as ApiHelper;
use \Klevu\Search\Model\Product\Sync;
use \Klevu\Search\Model\Api\Action\Features;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Model\Store;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_frameworkAppRequestInterface;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_appConfigScopeConfigInterface;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_magentoFrameworkUrlInterface;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $_frameworkModelStore;
    /**
     * @var \Magento\Framework\Config\Data
     */
    protected $_modelConfigData;
    protected $_klevu_features_response;

    protected $_klevu_enabled_feature_response;


     /**
     * @var \Klevu\Search\Helper\VersionReader
     */
    protected $_versionReader;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $appConfigScopeConfigInterface,
        \Magento\Framework\UrlInterface $magentoFrameworkUrlInterface,
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Magento\Framework\App\RequestInterface $frameworkAppRequestInterface,
        \Magento\Store\Model\Store $frameworkModelStore,
        \Magento\Framework\App\Config\Value $modelConfigData,
        \Magento\Framework\App\ResourceConnection $frameworkModelResource,
        \Klevu\Search\Helper\VersionReader $versionReader

    ) {

        $this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
        $this->_magentoFrameworkUrlInterface = $magentoFrameworkUrlInterface;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_frameworkAppRequestInterface = $frameworkAppRequestInterface;
        $this->_frameworkModelStore = $frameworkModelStore;
        $this->_modelConfigData = $modelConfigData;
        $this->_frameworkModelResource = $frameworkModelResource;
		$this->_versionReader = $versionReader;
    }

    const XML_PATH_EXTENSION_ENABLED = "klevu_search/general/enabled";
    //const XML_PATH_TAX_ENABLED       = "klevu_search/tax_setting/enabled";
    const XML_PATH_TAX_ENABLED       = "tax/display/typeinsearch";
    const XML_PATH_SECUREURL_ENABLED = "klevu_search/secureurl_setting/enabled";
    const XML_PATH_LANDING_ENABLED   = "klevu_search/searchlanding/landenabled";
    const XML_PATH_JS_API_KEY        = "klevu_search/general/js_api_key";
    const XML_PATH_REST_API_KEY      = "klevu_search/general/rest_api_key";
    const XML_PATH_PRODUCT_SYNC_ENABLED   = "klevu_search/product_sync/enabled";
    const XML_PATH_PRODUCT_SYNC_FREQUENCY = "klevu_search/product_sync/frequency";
    const XML_PATH_PRODUCT_SYNC_LAST_RUN = "klevu_search/product_sync/last_run";
    const XML_PATH_ATTRIBUTES_ADDITIONAL  = "klevu_search/attributes/additional";
    const XML_PATH_ATTRIBUTES_AUTOMATIC  = "klevu_search/attributes/automatic";
    const XML_PATH_ATTRIBUTES_OTHER       = "klevu_search/attributes/other";
    const XML_PATH_ATTRIBUTES_BOOSTING       = "klevu_search/attributes/boosting";
    const XML_PATH_CATEGORY_ANCHOR   = "klevu_search/attributes/categoryanchor";
    const XML_PATH_ORDER_SYNC_ENABLED   = "klevu_search/product_sync/order_sync_enabled";
    const XML_PATH_ORDER_SYNC_FREQUENCY = "klevu_search/product_sync/order_sync_frequency";
    const XML_PATH_ORDER_SYNC_FREQUENCY_CUSTOM = "klevu_search/product_sync/order_sync_frequency_custom";
    const XML_PATH_ORDER_SYNC_MAX_BATCH_SIZE = 'klevu_search/product_sync/order_sync_max_batch_size';
    const XML_PATH_ORDER_SYNC_LAST_RUN = "klevu_search/order_sync/last_run";
    const XML_PATH_FORCE_LOG = "klevu_search/developer/force_log";
    const XML_PATH_LOG_LEVEL = "klevu_search/developer/log_level";
    const XML_PATH_STORE_ID = "stores/%s/system/store/id";
    const XML_PATH_HOSTNAME = "klevu_search/general/hostname";
    const XML_PATH_RESTHOSTNAME = "klevu_search/general/rest_hostname";
    const XML_PATH_CLOUD_SEARCH_URL = "klevu_search/general/cloud_search_url";
    const XML_PATH_CLOUD_SEARCH_V2_URL = "klevu_search/general/cloud_search_v2_url";
    const XML_PATH_ANALYTICS_URL = "klevu_search/general/analytics_url";
    const XML_PATH_JS_URL = "klevu_search/general/js_url";
    const KLEVU_PRODUCT_FORCE_OLDERVERSION = 2;
    const XML_PATH_SYNC_OPTIONS = "klevu_search/product_sync/sync_options";
    const XML_PATH_UPGRADE_PREMIUM = "klevu_search/general/premium";
    const XML_PATH_RATING = "klevu_search/general/rating_flag";
    const XML_PATH_UPGRADE_FEATURES = "klevu_search/general/upgrade_features";
    const XML_PATH_UPGRADE_TIRES_URL = "klevu_search/general/tiers_url";
    const XML_PATH_COLLECTION_METHOD = "klevu_search/developer/collection_method";
    const XML_PATH_CONFIG_IMAGE_FLAG = "klevu_search/image_setting/enabled";
    const XML_PATH_TRIGGER_OPTIONS = "klevu_search/developer/trigger_options_info";
    const XML_PATH_CONFIG_IMAGE_HEIGHT = "klevu_search/image_setting/image_height";
    const XML_PATH_CONFIG_IMAGE_WIDHT = "klevu_search/image_setting/image_width";
    const DATETIME_FORMAT = "Y-m-d H:i:s T"; // deprecated, do not use DATETIME_FORMAT
    const XML_PATH_CONFIG_SYNC_FREQUENCY = "klevu_search/product_sync/frequency";
    const XML_PATH_PRICE_INCLUDES_TAX = "tax/calculation/price_includes_tax";
    const XML_PATH_PRICE_DISPLAY_METHOD = "tax/display/type";
    const XML_PATH_PRICE_TYPEINSEARCH_METHOD = "tax/display/typeinsearch";
    const XML_PATH_CATALOGINVENTRY_OPTIONS_STOCK ="cataloginventory/options/show_out_of_stock";
    const XML_PATH_CATALOG_SEARCH_RELEVANCE = "klevu_search/searchlanding/klevu_search_relevance";
    const XML_PATH_CATALOG_SEARCH_SORT_ORDERS = 'klevu_search/searchlanding/klevu_search_sort_orders';
	const XML_PATH_PRODUCT_SYNC_CATALOGVISIBILITY   = "klevu_search/product_sync/catalogvisibility";
	const  XML_PATH_SEARCHENGINE = 'catalog/search/engine';
	const XML_PATH_PRICE_PER_CUSTOMER_GROUP_METHOD = "klevu_search/price_per_customer_group/enabled";
    const XML_PATH_CATALOG_SEARCH_RELEVANCE_LABEL = "klevu_search/searchlanding/relevance_label";
    const XML_PATH_SYNC_LOCKFILE_OPTION = "klevu_search/product_sync/lockfile";
    const XML_PATH_NOTIFICATION_ORDERS_WITH_SAME_IP = "klevu_search/notification/orders_with_same_ip";
    const XML_PATH_DEVELOPER_ORDERS_PERCENTAGE = "klevu_search/developer/orders_percentage";
    const XML_PATH_DEVELOPER_DAYS_CALCULATE_ORDERS = "klevu_search/developer/days_to_calculate_orders";
 	const XML_PATH_CATEGORY_SYNC_ENABLED   = "klevu_search/product_sync/category_sync_enabled";
    const XML_PATH_NOTIFICATION_OBJECT_VS_COLLECTION   = "klevu_search/notification/object_vs_collection";
    const XML_PATH_NOTIFICATION_LOCK_FILE = "klevu_search/notification/lock_file";
	const XML_PATH_PRESERVE_LAYOUT_LOG_ENABLED = "klevu_search/developer/preserve_layout_log_enabled";
	const XML_PATH_PRESERVE_LAYOUT_MIN_LOG_LEVEL = "klevu_search/developer/preserve_layout_log_level";
    const XML_PATH_THEME_VERSION = 'klevu_search/developer/theme_version';
    const XML_PATH_QUICKSEARCH_SELECTOR = 'klevu_search/developer/quicksearch_selector';
	const ADMIN_RESOURCE_CONFIG = 'Klevu_Search::config_search';
	const XML_PATH_ORDER_IP = 'klevu_search/developer/orderip';
	const XML_PATH_RATING_SYNC_ENABLED = 'klevu_search/product_sync/rating_sync_enabled';

    /**
     * Set the Enable on Frontend flag in System Configuration for the given store.
     *
     * @param      $flag
     * @param \Magento\Framework\Model\Store|int|null $store Store to set the flag for. Defaults to current store.
     *
     * @return $this
     */
    public function setExtensionEnabledFlag($flag, $store = null)
    {
        $flag = ($flag) ? 1 : 0;
        $this->setStoreConfig(static::XML_PATH_EXTENSION_ENABLED, $flag, $store);
        return $this;
    }

    /**
     * Check if the \Klevu\Search extension is enabled in the system configuration for the current store.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isExtensionEnabled($store_id = null)
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(static::XML_PATH_EXTENSION_ENABLED, ScopeInterface::SCOPE_STORE, $store_id);
    }

    /**
     * Check if the Tax is enabled in the system configuration for the current store.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isTaxEnabled($store_id = null)
    {
        $flag = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_TAX_ENABLED, ScopeInterface::SCOPE_STORE, $store_id);
        return in_array($flag, [
            \Klevu\Search\Model\System\Config\Source\Taxoptions::YES,
            \Klevu\Search\Model\System\Config\Source\Taxoptions::ADMINADDED
        ]);
    }

    /**
     * Check if the Secure url is enabled in the system configuration for the current store.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isSecureUrlEnabled($store_id = null)
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(static::XML_PATH_SECUREURL_ENABLED, ScopeInterface::SCOPE_STORE, $store_id);
    }

    /**
     * Return the configuration flag for sending config image.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return bool
     */
    public function isUseConfigImage($store_id = null)
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(static::XML_PATH_CONFIG_IMAGE_FLAG, ScopeInterface::SCOPE_STORE, $store_id);
    }
    /**
     * Check if the Landing is enabled in the system configuration for the current store.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isLandingEnabled()
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_LANDING_ENABLED, ScopeInterface::SCOPE_STORE, $this->_storeModelStoreManagerInterface->getStore());
    }

    /**
     * Set the Tax mode in System Configuration for the given store.
     *
     * @param      $flag
     * @param null $store Store to use. If not specified, uses the current store.
     *
     * @return $this
     */
    public function setTaxEnabledFlag($flag, $store = null)
    {

        $this->setStoreConfig(static::XML_PATH_TAX_ENABLED, $flag, $store);
        return $this;
    }

    /**
     * Set the Secure Url mode in System Configuration for the given store.
     *
     * @param      $flag
     * @param null $store Store to use. If not specified, uses the current store.
     *
     * @return $this
     */
    public function setSecureUrlEnabledFlag($flag, $store = null)
    {

        $flag = ($flag) ? 1 : 0;
        $this->setStoreConfig(static::XML_PATH_SECUREURL_ENABLED, $flag, $store);
        return $this;
    }

    /**
     * Set the JS API key in System Configuration for the given store.
     *
     * @param string                    $key
     * @param \Magento\Framework\Model\Store|int $store     Store to use. If not specified, will use the current store.
     *
     * @return $this
     */
    public function setJsApiKey($key, $store = null)
    {
        $path = static::XML_PATH_JS_API_KEY;
        $this->setStoreConfig($path, $key, $store);
        return $this;
    }

    /**
     * Return the JS API key configured for the specified store.
     *
     * @param \Magento\Framework\Model\Store|int $store
     *
     * @return string
     */
    public function getJsApiKey($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_JS_API_KEY, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Set the REST API key in System Configuration for the given store.
     *
     * @param string                    $key
     * @param \Magento\Framework\Model\Store|int $store     Store to use. If not specified, will use the current store.
     *
     * @return $this
     */
    public function setRestApiKey($key, $store = null)
    {
        $path = static::XML_PATH_REST_API_KEY;
        $this->setStoreConfig($path, $key, $store);
        return $this;
    }

    /**
     * Return the REST API key configured for the specified store.
     *
     * @param \Magento\Framework\Model\Store|int $store
     *
     * @return mixed
     */
    public function getRestApiKey($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_REST_API_KEY, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Set the API Hostname value in System Configuration for a given store
     * @param $hostname
     * @param null $store
     * @return $this
     */
    public function setHostname($hostname, $store = null)
    {
        $path = static::XML_PATH_HOSTNAME;
        $this->setStoreConfig($path, $hostname, $store);
        return $this;
    }


    /**
     * Return the API Hostname configured, used for API requests, for a specified store
     * @param \Magento\Framework\Model\Store|int|null $store
     * @return string
     */
    public function getHostname($store = null)
    {
	if ($store == null) {
            $store = $this->_storeModelStoreManagerInterface->getStore();
        }
        $hostname = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_HOSTNAME, ScopeInterface::SCOPE_STORE, $store->getId());
        return ($hostname) ? $hostname : ApiHelper::ENDPOINT_DEFAULT_HOSTNAME;
    }

    /**
     * Return the API Rest Hostname configured, used for API requests, for a specified store
     * @return string
     */
    public function getRestHostname($store = null)
    {
	if ($store == null) {
            $store = $this->_storeModelStoreManagerInterface->getStore();
        }
        $hostname = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_RESTHOSTNAME, ScopeInterface::SCOPE_STORE, $store->getId());
        return ($hostname) ? $hostname : ApiHelper::ENDPOINT_DEFAULT_HOSTNAME;
    }

    /**
     * Set the Rest Hostname value in System Configuration for a given store
     * @param $url
     * @param null $store
     * @return $this
     */
    public function setRestHostname($url, $store = null)
    {
        $path = static::XML_PATH_RESTHOSTNAME;
        $this->setStoreConfig($path, $url, $store);
        return $this;
    }

    /**
     * @param string $url
     * @param int|StoreInterface|null $store
     * @return $this
     */
    public function setCloudSearchUrl($url, $store = null)
    {
        $path = static::XML_PATH_CLOUD_SEARCH_URL;
        $this->setStoreConfig($path, $url, $store);
        return $this;
    }

    /**
     * @param int|StoreInterface|null $store
     * @return string
     */
    public function getCloudSearchUrl($store = null)
    {
        $url = $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_CLOUD_SEARCH_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return ($url) ?: ApiHelper::ENDPOINT_CLOUD_SEARCH_URL;
    }

    /**
     * @param string $url
     * @param int|StoreInterface|null $store
     * @return $this
     */
    public function setCloudSearchV2Url($url, $store = null)
    {
        $path = static::XML_PATH_CLOUD_SEARCH_V2_URL;
        $this->setStoreConfig($path, $url, $store);
        return $this;
    }

    /**
     * @param int|StoreInterface|null $store
     * @return string
     */
    public function getCloudSearchV2Url($store = null)
    {
        $url = $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_CLOUD_SEARCH_V2_URL,
            ScopeInterface::SCOPE_STORES,
            $store
        );

        return $url ?: ApiHelper::ENDPOINT_CLOUD_SEARCH_V2_URL;
    }

    /**
     * @param $url
     * @param null $store
     * @return $this
     */
    public function setAnalyticsUrl($url, $store = null)
    {
        $path = static::XML_PATH_ANALYTICS_URL;
        $this->setStoreConfig($path, $url, $store);
        return $this;
    }

    /**
     * @param int|StoreInterface|null $store
     * @return string
     */
    public function getAnalyticsUrl($store = null)
    {
        $url = $this->_appConfigScopeConfigInterface->getValue(
        	static::XML_PATH_ANALYTICS_URL,
        	\Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        	$store
        );
        return ($url) ? $url : ApiHelper::ENDPOINT_DEFAULT_ANALYTICS_HOSTNAME;
    }

    /**
     * @param $url
     * @param null $store
     * @return $this
     */
    public function setJsUrl($url, $store = null)
    {
        $path = static::XML_PATH_JS_URL;
        $this->setStoreConfig($path, $url, $store);
        return $this;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getJsUrl()
    {
        $url = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_JS_URL, ScopeInterface::SCOPE_STORE);
        return ($url) ? $url : ApiHelper::ENDPOINT_DEFAULT_HOSTNAME;
    }

    /**
     * Check if the Klevu Search extension is configured for the given store.
     *
     * @param null $store_id
     *
     * @return bool
     */
    public function isExtensionConfigured($store_id = null)
    {
        $js_api_key = $this->getJsApiKey($store_id);
        $rest_api_key = $this->getRestApiKey($store_id);
        return (
            $this->isExtensionEnabled($store_id)
            && !empty($js_api_key)
            && !empty($rest_api_key)
        );
    }

    /**
     * Return the system configuration setting for enabling Product Sync for the specified store.
     * The returned value can have one of three possible meanings: Yes, No and Forced. The
     * values mapping to these meanings are available as constants on
     * \Klevu\Search\Model\System\Config\Source\Yesnoforced.
     *
     * @param $store_id
     *
     * @return int
     */
    public function getProductSyncEnabledFlag($store_id = 0)
    {
        return intval($this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_PRODUCT_SYNC_ENABLED, ScopeInterface::SCOPE_STORE, $store_id));
    }

    /**
     * Check if Product Sync is enabled for the specified store and domain.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isProductSyncEnabled($store_id = 0)
    {
        $flag = $this->getProductSyncEnabledFlag($store_id);
        return in_array($flag, [
            \Klevu\Search\Model\System\Config\Source\Yesnoforced::YES,
            static::KLEVU_PRODUCT_FORCE_OLDERVERSION
        ]);
    }

    /**
     * Return the configured frequency expression for Product Sync.
     *
     * @return string
     */
    public function getProductSyncFrequency()
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_PRODUCT_SYNC_FREQUENCY);
    }

    /**
     * Get the last sync time from klevu sync table.
     *
     * @param int|null $store
     * @return string
     */
    public function getLastProductSyncRun($store = null)
    {
        $resource = $this->_frameworkModelResource;
        $connection = $resource->getConnection();
        $select = $connection
            ->select()
            ->from(['k' => $resource->getTableName("klevu_product_sync")])
            ->order(['k.last_synced_at DESC'])
            ->limit(1);

        // retrieve last sync across all stores if none specified
        if ($store !== null) {
            $select->where("k.store_id = ?", $store);
        }

        $result = $connection->fetchAll($select);
        if (!empty($result)) {
            $datetime = new \DateTime($result[0]['last_synced_at']);
            return $datetime->format('Y-m-d H:i:s');
        }
    }

    /**
     * Check if Product Sync has ever run for the given store.
     *
     * @param \Magento\Framework\Model\Store|int|null $store
     *
     * @return bool
     */
    public function hasProductSyncRun($store = null)
    {
        $config = $this->_appConfigScopeConfigInterface;
        if (!$config->getValue(static::XML_PATH_PRODUCT_SYNC_LAST_RUN, ScopeInterface::SCOPE_STORE, $store)) {
            return false;
        }

        return true;
    }

    public function setAdditionalAttributesMap($map, $store = null)
    {
        unset($map["__empty"]);
        $this->setStoreConfig(static::XML_PATH_ATTRIBUTES_ADDITIONAL, serialize($map), $store);
        return $this;
    }

    /**
     * Return the map of additional Klevu attributes to Magento attributes.
     *
     * @param int|\Magento\Framework\Model\Store $store
     *
     * @return array
     */
    public function getAdditionalAttributesMap($store = null)
    {
        $map = unserialize($this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_ATTRIBUTES_ADDITIONAL, $store));

        return (is_array($map)) ? $map : [];
    }

    /**
     * Set the automatically mapped attributes
     * @param array $map
     * @param int|\Magento\Framework\Model\Store $store
     * @return $this
     */
    public function setAutomaticAttributesMap($map, $store = null)
    {
        unset($map["__empty"]);
        $this->setStoreConfig(static::XML_PATH_ATTRIBUTES_AUTOMATIC, serialize($map), $store);
        return $this;
    }

    /**
     * Returns the automatically mapped attributes
     * @param int|\Magento\Framework\Model\Store $store
     * @return array
     */
    public function getAutomaticAttributesMap($store = null)
    {
        $map = unserialize($this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_ATTRIBUTES_AUTOMATIC, $store));

        return (is_array($map)) ? $map : [];
    }

    /**
     * Return the System Configuration setting for enabling Order Sync for the given store.
     * The returned value can have one of three possible meanings: Yes, No and Forced. The
     * values mapping to these meanings are available as constants on
     * \Klevu\Search\Model\System\Config\Source\Yesnoforced.
     *
     * @param \Magento\Framework\Model\Store|int $store
     *
     * @return int
     */
    public function getOrderSyncEnabledFlag($store = 0)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_ORDER_SYNC_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if Order Sync is enabled for the given store on the current domain.
     *
     * @param \Magento\Framework\Model\Store|int $store
     *
     * @return bool
     */
    public function isOrderSyncEnabled($store = null)
    {
        $flag = $this->getOrderSyncEnabledFlag($store);
        return in_array($flag, [
            \Klevu\Search\Model\System\Config\Source\Yesnoforced::YES,
            static::KLEVU_PRODUCT_FORCE_OLDERVERSION
        ]);
    }

    /**
     * Return the configured frequency expression for Order Sync.
     *
     * @return string
     */
    public function getOrderSyncFrequency()
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_ORDER_SYNC_FREQUENCY);
    }

    /**
     * @param int|null $storeId
     * @return int|null
     */
    public function getOrderSyncMaxBatchSize($storeId = null)
    {
        $configValue = (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_ORDER_SYNC_MAX_BATCH_SIZE,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        return $configValue ?: null;
    }

    /**
     * Set the last Order Sync run time in System Configuration.
     *
     * @param DateTime|string $datetime If string is passed, it will be converted to DateTime.
     *
     * @return $this
     */
    public function setLastOrderSyncRun($datetime = "now")
    {
        if (!$datetime instanceof DateTime) {
            $datetime = new DateTime($datetime);
        }
        $this->setGlobalConfig(static::XML_PATH_ORDER_SYNC_LAST_RUN, $datetime->format('Y-m-d H:i:s T'));
        return $this;
    }

    /**
     * Check if default Magento log settings should be overridden to force logging for this module.
     *
     * @param int $store
     * @return bool
     * @deprecated
     */
    public function isLoggingForced($store = null)
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(
            static::XML_PATH_FORCE_LOG,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Return the minimum log level configured. Default to LoggerConstants::ZEND_LOG_WARN.
     *
     * @return int
     */
    public function getLogLevel()
    {
        $log_level = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_LOG_LEVEL, ScopeInterface::SCOPE_STORE);
        return ($log_level !== null) ? (int)$log_level : LoggerConstants::ZEND_LOG_INFO;
    }

    /**
     * @param $url
     * @param null $store
     * @return $this
     */
    public function setTiresUrl($url, $store = null)
    {
        $path = static::XML_PATH_UPGRADE_TIRES_URL;
        $this->setStoreConfig($path, $url, $store);
        return $this;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getTiresUrl($store = null)
    {
        $url = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_UPGRADE_TIRES_URL, ScopeInterface::SCOPE_STORE, $store);
        return ($url) ? $url : ApiHelper::ENDPOINT_DEFAULT_HOSTNAME;
    }

    /**
     * Return an multi-dimensional array of magento and klevu attributes that are mapped by default.
     * @return array
     */
    public function getDefaultMappedAttributes()
    {
        return [
            "magento_attribute" => [
                "name",
                "sku",
                "image",
                "small_image",
                "media_gallery",
                "status",
                "description",
                "short_description",
                "price",
                "price",
                "tax_class_id",
                "weight",
                "rating",
                "special_price",
                "special_from_date",
                "special_to_date",
                "visibility",
                "created_at"
            ],
            "klevu_attribute" => [
                "name",
                "sku",
                "image",
                "small_image",
                "media_gallery",
                "status",
                "desc",
                "shortDesc",
                "price",
                "salePrice",
                "salePrice",
                "weight",
                "rating",
                "special_price",
                "special_from_date",
                "special_to_date",
                "visibility",
                "dateAdded"
            ]
        ];
    }

    /**
     * Returns array of other attributes map from store configuration.
     *
     * @param \Magento\Framework\Model\Store|int|null $store
     * @return array
     */
    public function getOtherAttributesToIndex($store = null)
    {
        if ($this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_ATTRIBUTES_OTHER, ScopeInterface::SCOPE_STORE, $store)) {
            return explode(",", $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_ATTRIBUTES_OTHER, ScopeInterface::SCOPE_STORE, $store));
        }

        return [];
    }

    /**
     * Return the boosting attribute defined in store configuration.
     *
     * @param \Magento\Framework\Model\Store|int|null $store
     * @return array
     */
    public function getBoostingAttribute($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_ATTRIBUTES_BOOSTING, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Set the global scope System Configuration value for the given key.
     *
     * @param string $key
     * @param string $value
     *
     * @return $this
     */
    public function setGlobalConfig($key, $value)
    {
        $saveconfig = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Config\Model\ResourceModel\Config');
        $saveconfig->saveConfig($key, $value, "default", 0);

        $config = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Config\ReinitableConfigInterface');
        $config->reinit();
        return $this;
    }

    /**
     * Set the store scope System Configuration value for the given key.
     *
     * @param string                         $key
     * @param string                         $value
     * @param \Magento\Framework\Model\Store|int|null $store If not given, current store will be used.
     *
     * @return $this
     */
    public function setStoreConfig($key, $value, $store = null)
    {

        $config = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Config\Model\ResourceModel\Config');
        $scope_id = $this->_storeModelStoreManagerInterface->getStore($store)->getId();
        if ($scope_id !== null) {
            $config->saveConfig($key, $value, "stores", $scope_id);
        }
        return $this;
    }

    /**
     * Clear config cache
     */
    public function resetConfig()
    {
        \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Config\ReinitableConfigInterface')->reinit();
    }
    /**
     * Return the configuration flag for sync options.
     *
     *
     * @return int
     */
    public function getSyncOptionsFlag()
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_SYNC_OPTIONS);
    }

    /**
     * save sync option value
     *
     * @param string $value
     *
     * @return
     */
    public function saveSyncOptions($value)
    {
        $this->setGlobalConfig(static::XML_PATH_SYNC_OPTIONS, $value);
        return $this;
    }

    /**
     * save upgrade button value
     *
     * @param string $value
     *
     * @return
     */
    public function saveUpgradePremium($value)
    {
        $this->setGlobalConfig(static::XML_PATH_UPGRADE_PREMIUM, $value);
        return $this;
    }

    /**
     * save upgrade rating value
     *
     * @param string $value
     *
     * @return
     */
    public function saveRatingUpgradeFlag($value,$store)
    {
		$this->setStoreConfig(static::XML_PATH_RATING,$value,$store);
        //$this->setGlobalConfig(static::XML_PATH_RATING, $value);
        return $this;
    }

    /**
     * get upgrade rating value
     *
     * @return int
     */
    public function getRatingUpgradeFlag($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_RATING, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * get feature update
     *
     * @return bool
     */
    public function getFeaturesUpdate($elemnetID)
    {

        try {
            if (!$this->_klevu_features_response) {
                $this->_klevu_features_response = $this->getFeatures();
            }
            $features = $this->_klevu_features_response;

            if (!empty($features) && !empty($features['disabled'])) {
                $checkStr = explode("_", $elemnetID);
                $disable_features =  explode(",", $features['disabled']);
                $code = $this->_frameworkAppRequestInterface->getParam('store');// store level
                $store = $this->_frameworkModelStore->load($code);

                if (in_array("preserves_layout", $disable_features) && $this->_frameworkAppRequestInterface->getParam('section')=="klevu_search") {
                    // when some upgrade plugin if default value set to 1 means preserve layout
                    // then convert to klevu template layout
                    if ($this->_appConfigScopeConfigInterface->getValue(\Klevu\Search\Helper\Config::XML_PATH_LANDING_ENABLED, ScopeInterface::SCOPE_STORE, $store) == 1) {
                        $this->setStoreConfig(\Klevu\Search\Helper\Config::XML_PATH_LANDING_ENABLED, 2, $store);
                    }
                }

                if (in_array($checkStr[count($checkStr)-1], $disable_features) && $this->_frameworkAppRequestInterface->getParam('section')=="klevu_search") {
                    $check = $checkStr[count($checkStr)-1];
                    if (!empty($check)) {
                        $configs = $this->_modelConfigData->getCollection()
                            ->addFieldToFilter('path', ["like" => '%/'.$check.'%'])->load();
                        $data = $configs->getData();
                        if (!empty($data)) {
                            $this->setStoreConfig($data[0]['path'], 0, $store);
                        }
                        return $features;
                    }
                }
            }
        } catch (\Exception $e) {
            $dataHelper = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Helper\Data');
            $dataHelper->log(LoggerConstants::ZEND_LOG_CRIT, sprintf("Error occured while getting features based on account %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
        return;
    }

    /**
     * Save the upgrade features defined in store configuration.
     *
     * @param \Magento\Framework\Model\Store|int|null $store
     */

    public function saveUpgradeFetaures($value, $store = null)
    {
        $savedValue = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_UPGRADE_FEATURES, ScopeInterface::SCOPE_STORE, $store);
        if (isset($savedValue) && !empty($savedValue)) {
            if ($savedValue != $value) {
                $this->setStoreConfig(static::XML_PATH_UPGRADE_FEATURES, $value, $store);
            }
        } else {
            $this->setStoreConfig(static::XML_PATH_UPGRADE_FEATURES, $value, $store);
        }
    }

    /**
     * Return the upgrade features defined in store configuration.
     *
     * @param \Magento\Framework\Model\Store|int|null $store
     * @return array
     */
    public function getUpgradeFetaures($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_UPGRADE_FEATURES, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Check if default Magento log settings should be overridden to force logging for this module.
     *
     * @return bool
     */
    public function isCollectionMethodEnabled()
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(static::XML_PATH_COLLECTION_METHOD);
    }



    /**
     * Return the configuration flag for sync options.
     *
     *
     * @return int
     */
    public function getTriggerOptionsFlag()
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_TRIGGER_OPTIONS);
    }

    /**
     * save Trigger option value
     *
     * @param string $value
     *
     * @return
     */
    public function saveTriggerOptions($value)
    {
        $this->setGlobalConfig(static::XML_PATH_TRIGGER_OPTIONS, $value);
        return $this;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getImageHeight($store = null)
    {
        $image_height = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_CONFIG_IMAGE_HEIGHT, ScopeInterface::SCOPE_STORE, $store);
        return  $image_height;
    }


    /**
     * @param null $store
     * @return string
     */
    public function getImageWidth($store = null)
    {
        $image_width = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_CONFIG_IMAGE_WIDHT, ScopeInterface::SCOPE_STORE, $store);
        return  $image_width;
    }

    /**
     * Return the klevu cron stettings.
     *
     * @return bool
     * @deprecated Method name does not match logic, please use isExternalCronActive() instead.
     */
    public function isExternalCronEnabled(){
        if($this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_CONFIG_SYNC_FREQUENCY) == "0 5 31 2 *") {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Replaces isExternalCronEnabled().
     * Determine whether the store is using external cron,
     * ie. whether the internal cron schedule is set as 'Never'.
     *
     * @return bool
     */
    public function isExternalCronActive()
    {
        $configFrequency = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_CONFIG_SYNC_FREQUENCY);
        $isExternalCronActive = $configFrequency == \Klevu\Search\Model\System\Config\Source\Frequency::CRON_NEVER;

        return $isExternalCronActive;
    }

    /**
     * Return admin already included tax in price or not.
     *
     * @return bool
     */
    public function getPriceIncludesTax($store = null)
    {
        return (bool)$this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_PRICE_INCLUDES_TAX, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Check if the Tax is include/exclude in the system configuration for the current store.
     *
     * @param $store_id
     *
     * @return bool
     */
    public function isTaxCalRequired($store = null)
    {
        $flag = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_PRICE_TYPEINSEARCH_METHOD, ScopeInterface::SCOPE_STORE, $store);
        if(in_array($flag, [
            \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX
        ])){
            return true;
        } else {
            return false;
        }
        return;
    }

    /**
     * Return the Price Include/Exclude Tax.
     *
     * @return bool
     */
    public function getPriceDisplaySettings($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_PRICE_DISPLAY_METHOD, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Return display out of stock on or off in magento setting at global level.
     *
     * @return bool
     */
    public function displayOutofstock()
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(static::XML_PATH_CATALOGINVENTRY_OPTIONS_STOCK);
    }

    /**
     * Get curernt store features based on klevu search account
     *
     * @return string
     */
    public function getFeatures()
    {
		$dataHelper = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Helper\Data');
		try{
			if (strlen($code = $this->_frameworkAppRequestInterface->getParam('store'))) { // store level
				$code = $this->_frameworkAppRequestInterface->getParam('store');
				if (!$this->_klevu_features_response) {
					$store = $this->_frameworkModelStore->load($code);
					$store_id = $store->getId();
					$restapi = $this->getRestApiKey($store_id);
					$param =  ["restApiKey" => $restapi];
					if (!empty($restapi)) {
						$this->_klevu_features_response = $this->executeFeatures($restapi, $store);
					} else {
						return;
					}
				}
				return $this->_klevu_features_response;
			}
		} catch (\Zend\Http\Client\Exception\RuntimeException $re) {
            $dataHelper->log(LoggerConstants::ZEND_LOG_INFO, sprintf("Unable to get Klevu Features list (%s)", $re->getMessage()));
            return;
        } catch (\Exception $e) {
            $dataHelper->log(LoggerConstants::ZEND_LOG_INFO, sprintf("Uncaught Exception thrown while getting Klevu Features list (%s)", $e->getMessage()));
            return;
        }
    }

    /**
     * Get the features from config value if not get any response from api
     *
     * @param sting $restApi , int $store
     *
     * @return string
     */
    public function executeFeatures($restApi, $store)
    {
        if (!$this->_klevu_enabled_feature_response) {
            $param =  ["restApiKey" => $restApi,"store" => $store->getId()];
            $features_request = \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Model\Api\Action\Features')->execute($param);
            if ($features_request->isSuccess()) {
                $this->_klevu_enabled_feature_response = $features_request->getData();
                $this->saveUpgradeFetaures(serialize($this->_klevu_enabled_feature_response), $store);
            } else {
                if (!empty($restApi)) {
                    $this->_klevu_enabled_feature_response = unserialize($this->getUpgradeFetaures($store));
                }
                $dataHelper = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Helper\Data');
                $dataHelper->log(LoggerConstants::ZEND_LOG_INFO, sprintf("failed to fetch feature details (%s)", $features_request->getMessage()));
            }
        }
        return $this->_klevu_enabled_feature_response;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getCatalogSearchRelevance($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_CATALOG_SEARCH_RELEVANCE, ScopeInterface::SCOPE_STORE, $store);

    }


	/**
     * Return Catalog Visibility Sync.
     *
     * @param Mage_Core_Model_Store|int $store
     *
     * @return bool
     */
    public function useCatalogVisibitySync($store_id = null)
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(static::XML_PATH_PRODUCT_SYNC_CATALOGVISIBILITY, ScopeInterface::SCOPE_STORE, $store_id);
    }

    /**
     * Return Current Configured Catalog search engine.
     *
     * @return bool
     */
    public function getCurrentEngine()
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_SEARCHENGINE);
    }

    /**
     * Check if default isCustomerGroupPriceEnabled.
     *
     * @return bool
     */
    public function isCustomerGroupPriceEnabled()
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(static::XML_PATH_PRICE_PER_CUSTOMER_GROUP_METHOD);
    }

	/**
     * It will return Klevu Search version info
     *
     * @return mixed
     */
    public function getModuleInfo()
    {
        return $this->_versionReader->getVersionString('Klevu_Search');
    }

    /**
     * It will return Klevu Category Navigation version info
     *
     * @return mixed
     */
    public function getModuleInfoCatNav()
    {
        return $this->_versionReader->getVersionString('Klevu_Categorynavigation');
    }


    /**
     * Retrieve default per page values
     *
     * @return string (comma separated)
     */

    public function getCatalogGridPerPageValues()
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            'catalog/frontend/grid_per_page_values',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve default per page
     *
     * @return int
     */
    public function getCatalogGridPerPage()
    {
        return (int) $this->_appConfigScopeConfigInterface->getValue(
            'catalog/frontend/grid_per_page',
            ScopeInterface::SCOPE_STORE
        );
    }

	/**
     * Save UseCollectionMethodFlag
     *
     * @param bool $value
     *
     * @return $this
     */
	public function saveUseCollectionMethodFlag($value)
	{
		$this->setGlobalConfig(static::XML_PATH_COLLECTION_METHOD, $value);
        return $this;
	}


    /**
     * Return the Relevance label
     *
     * @param null $store
     * @return string
     */
    public function getCatalogSearchRelevanceLabel($store = null)
    {
        $sortLabel = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_CATALOG_SEARCH_RELEVANCE_LABEL, ScopeInterface::SCOPE_STORE, $store);
        return $sortLabel ? $sortLabel : __('Relevance');
    }

    /**
     * Returns selection lockfile option
     * @param null $store
     * @return mixed
     */
    public function getSelectedLockFileOption($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_SYNC_LOCKFILE_OPTION, ScopeInterface::SCOPE_STORE, $store);
    }

	/**
     * Returns the category sync status
     * @param int $store
     * @return int
     */
    public function getCategorySyncEnabledFlag($store = 0)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_CATEGORY_SYNC_ENABLED, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns notification flag for object method
     *
     * @param int $store
     * @return int
     */
    public function getObjMethodNotificationOption($store=0){

        return (int)$this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_NOTIFICATION_OBJECT_VS_COLLECTION, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns notification flag for lock file warning
     *
     * @param int $store
     * @return int
     */
    public function getLockFileNotificationOption($store=0){

        return (int)$this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_NOTIFICATION_LOCK_FILE, ScopeInterface::SCOPE_STORE, $store);
    }
    /**
     * Check if Klevu preserve layout log enabled in settings
     *
     * @param int $store
     * @return bool
     */
    public function isPreserveLayoutLogEnabled($store = null)
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(
            static::XML_PATH_PRESERVE_LAYOUT_LOG_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    /**
     * Returns notification flag for lock file warning
     *
     * @param int $store
     * @return int
     */
    public function getTreatCategoryAnchorAsSingle($store=0){

        return (int)$this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_CATEGORY_ANCHOR, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * Returns whether orders with same ip notification flag on or off
     *
     * @param int $store
     * @return int
     */
    public function isOrdersWithSameIPNotificationOptionEnabled($store = 0)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_NOTIFICATION_ORDERS_WITH_SAME_IP,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns % value for orders calculation
     *
     * @param int $store
     * @return int
     */
    public function getPercentageOfOrders($store = 0)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_DEVELOPER_ORDERS_PERCENTAGE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns days to calculate orders
     *
     * @param int $store
     * @return int
     */
    public function getDaysToCalculateOrders($store = 0)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_DEVELOPER_DAYS_CALCULATE_ORDERS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

	/**
     * @param int|null $storeId
     * @return int|null
     */
    public function getConfiguredOrderIP($storeId = null)
    {
        $configValue = $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_ORDER_IP,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        return $configValue ?: null;
    }

	/**
     * Check rating sync enabled or not.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isRatingSyncEnable($store_id = null)
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(static::XML_PATH_RATING_SYNC_ENABLED, ScopeInterface::SCOPE_STORE, $store_id);

    }
}
