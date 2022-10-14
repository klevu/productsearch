<?php

namespace Klevu\Search\Helper;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Api as ApiHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Attribute\Rating;
use Klevu\Search\Model\Attribute\ReviewCount;
use Klevu\Search\Model\Api\Action\Features as ApiGetFeatures;
use Klevu\Search\Model\System\Config\Source\Frequency;
use Klevu\Search\Model\System\Config\Source\Landingoptions;
use Klevu\Search\Model\System\Config\Source\Taxoptions;
use Klevu\Search\Model\System\Config\Source\Yesnoforced;
use Klevu\Search\Service\Account\GetFeatures;
use Klevu\Search\Service\Account\KlevuApi\GetAccountDetails;
use Klevu\Search\Service\Account\UpdateEndpoints;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Config\Model\ResourceModel\ConfigFactory as ConfigResourceFactory;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Zend\Http\Client\Exception\RuntimeException;

class Config extends AbstractHelper
{
    /**
     * @var RequestInterface
     */
    protected $_frameworkAppRequestInterface;
    /**
     * @var ScopeConfigInterface
     */
    protected $_appConfigScopeConfigInterface;
    /**
     * @var UrlInterface
     */
    protected $_magentoFrameworkUrlInterface;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var Store
     */
    protected $_frameworkModelStore;
    /**
     * @var \Magento\Framework\Config\Data
     */
    protected $_modelConfigData;
    /**
     * @var array
     */
    protected $_klevu_features_response;
    /**
     * @var array
     */
    protected $_klevu_enabled_feature_response;
    /**
     * @var ResourceConnection
     */
    protected $_frameworkModelResource;
    /**
     * @var VersionReader
     */
    protected $_versionReader;
    /**
     * @var AppState
     */
    private $appState;
    /**
     * @var ConfigResource
     */
    private $configResource;

    /**
     * @param ScopeConfigInterface $appConfigScopeConfigInterface
     * @param UrlInterface $magentoFrameworkUrlInterface
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param RequestInterface $frameworkAppRequestInterface
     * @param Store $frameworkModelStore
     * @param ConfigValue $modelConfigData
     * @param ResourceConnection $frameworkModelResource
     * @param VersionReader $versionReader
     * @param AppState $appState
     * @param ConfigResourceFactory $configResourceFactory
     */
    public function __construct(
        ScopeConfigInterface $appConfigScopeConfigInterface,
        UrlInterface $magentoFrameworkUrlInterface,
        StoreManagerInterface $storeModelStoreManagerInterface,
        RequestInterface $frameworkAppRequestInterface,
        Store $frameworkModelStore,
        ConfigValue $modelConfigData,
        ResourceConnection $frameworkModelResource,
        VersionReader $versionReader,
        AppState $appState = null,
        ConfigResourceFactory $configResourceFactory = null
    ) {
        $this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
        $this->_magentoFrameworkUrlInterface = $magentoFrameworkUrlInterface;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_frameworkAppRequestInterface = $frameworkAppRequestInterface;
        $this->_frameworkModelStore = $frameworkModelStore;
        $this->_modelConfigData = $modelConfigData;
        $this->_frameworkModelResource = $frameworkModelResource;
        $this->_versionReader = $versionReader;
        $objectManager = ObjectManager::getInstance();
        $this->appState = $appState ?: $objectManager->get(AppState::class);
        $configResourceFactory = $configResourceFactory ?: $objectManager->get(ConfigResourceFactory::class);
        $this->configResource = $configResourceFactory->create();
    }

    const XML_PATH_EXTENSION_ENABLED = "klevu_search/general/enabled";
    //const XML_PATH_TAX_ENABLED       = "klevu_search/tax_setting/enabled";
    const XML_PATH_TAX_ENABLED = "tax/display/typeinsearch";
    const XML_PATH_SECUREURL_ENABLED = "klevu_search/secureurl_setting/enabled";
    const XML_PATH_LANDING_ENABLED = "klevu_search/searchlanding/landenabled";
    const XML_PATH_JS_API_KEY = "klevu_search/general/js_api_key";
    const XML_PATH_REST_API_KEY = GetFeatures::XML_PATH_REST_API_KEY;
    const XML_PATH_PRODUCT_SYNC_ENABLED = "klevu_search/product_sync/enabled";
    const XML_PATH_PRODUCT_SYNC_FREQUENCY = "klevu_search/product_sync/frequency";
    const XML_PATH_PRODUCT_SYNC_LAST_RUN = "klevu_search/product_sync/last_run";
    const XML_PATH_ATTRIBUTES_ADDITIONAL = "klevu_search/attributes/additional";
    const XML_PATH_ATTRIBUTES_AUTOMATIC = "klevu_search/attributes/automatic";
    const XML_PATH_ATTRIBUTES_OTHER = "klevu_search/attributes/other";
    const XML_PATH_ATTRIBUTES_BOOSTING = "klevu_search/attributes/boosting";
    const XML_PATH_CATEGORY_ANCHOR = "klevu_search/attributes/categoryanchor";
    const XML_PATH_ORDER_SYNC_ENABLED = "klevu_search/product_sync/order_sync_enabled";
    const XML_PATH_ORDER_SYNC_FREQUENCY = "klevu_search/product_sync/order_sync_frequency";
    const XML_PATH_ORDER_SYNC_FREQUENCY_CUSTOM = "klevu_search/product_sync/order_sync_frequency_custom";
    const XML_PATH_ORDER_SYNC_MAX_BATCH_SIZE = 'klevu_search/product_sync/order_sync_max_batch_size';
    const XML_PATH_ORDER_SYNC_LAST_RUN = "klevu_search/order_sync/last_run";
    const XML_PATH_FORCE_LOG = "klevu_search/developer/force_log";
    const XML_PATH_LOG_LEVEL = "klevu_search/developer/log_level";
    const XML_PATH_STORE_ID = "stores/%s/system/store/id";
    const XML_PATH_HOSTNAME = GetAccountDetails::XML_PATH_HOSTNAME;
    const XML_PATH_API_URL = GetAccountDetails::XML_PATH_API_URL;
    const XML_PATH_RESTHOSTNAME = UpdateEndpoints::XML_PATH_INDEXING_URL;
    const XML_PATH_CLOUD_SEARCH_URL = "klevu_search/general/cloud_search_url";
    const XML_PATH_CLOUD_SEARCH_V2_URL = UpdateEndpoints::XML_PATH_SEARCH_URL;
    const XML_PATH_ANALYTICS_URL = UpdateEndpoints::XML_PATH_ANALYTICS_URL;
    const XML_PATH_JS_URL = UpdateEndpoints::XML_PATH_JS_URL;
    const KLEVU_PRODUCT_FORCE_OLDERVERSION = 2;
    const XML_PATH_SYNC_OPTIONS = "klevu_search/product_sync/sync_options";
    const XML_PATH_UPGRADE_PREMIUM = "klevu_search/general/premium";
    const XML_PATH_RATING = "klevu_search/general/rating_flag";
    const XML_PATH_UPGRADE_FEATURES = "klevu_search/general/upgrade_features";
    const XML_PATH_UPGRADE_TIRES_URL = UpdateEndpoints::XML_PATH_TIRES_URL;
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
    const XML_PATH_CATALOGINVENTRY_OPTIONS_STOCK = "cataloginventory/options/show_out_of_stock";
    const XML_PATH_CATALOG_SEARCH_RELEVANCE = "klevu_search/searchlanding/klevu_search_relevance";
    const XML_PATH_CATALOG_SEARCH_SORT_ORDERS = 'klevu_search/searchlanding/klevu_search_sort_orders';
    const XML_PATH_PRODUCT_SYNC_CATALOGVISIBILITY = "klevu_search/product_sync/catalogvisibility";
    const  XML_PATH_SEARCHENGINE = 'catalog/search/engine';
    const XML_PATH_PRICE_PER_CUSTOMER_GROUP_METHOD = "klevu_search/price_per_customer_group/enabled";
    const XML_PATH_CATALOG_SEARCH_RELEVANCE_LABEL = "klevu_search/searchlanding/relevance_label";
    const XML_PATH_SYNC_LOCKFILE_OPTION = "klevu_search/product_sync/lockfile";
    const XML_PATH_NOTIFICATION_ORDERS_WITH_SAME_IP = "klevu_search/notification/orders_with_same_ip";
    const XML_PATH_DEVELOPER_ORDERS_PERCENTAGE = "klevu_search/developer/orders_percentage";
    const XML_PATH_DEVELOPER_DAYS_CALCULATE_ORDERS = "klevu_search/developer/days_to_calculate_orders";
    const XML_PATH_CATEGORY_SYNC_ENABLED = "klevu_search/product_sync/category_sync_enabled";
    const XML_PATH_NOTIFICATION_OBJECT_VS_COLLECTION = "klevu_search/notification/object_vs_collection";
    const XML_PATH_NOTIFICATION_LOCK_FILE = "klevu_search/notification/lock_file";
    const XML_PATH_PRESERVE_LAYOUT_LOG_ENABLED = "klevu_search/developer/preserve_layout_log_enabled";
    const XML_PATH_PRESERVE_LAYOUT_MIN_LOG_LEVEL = "klevu_search/developer/preserve_layout_log_level";
    const XML_PATH_THEME_VERSION = 'klevu_search/developer/theme_version';
    const XML_PATH_QUICKSEARCH_SELECTOR = 'klevu_search/developer/quicksearch_selector';
    const ADMIN_RESOURCE_CONFIG = 'Klevu_Search::config_search';
    const XML_PATH_ORDER_IP = 'klevu_search/developer/orderip';
    const XML_PATH_RATING_SYNC_ENABLED = 'klevu_search/product_sync/rating_sync_enabled';
    const XML_PATH_LAZYLOAD_QUICK_SEARCH = 'klevu_search/developer/lazyload_js_quick_search';
    const XML_PATH_LAZYLOAD_SEARCH_LANDING = 'klevu_search/developer/lazyload_js_search_landing';
    const XML_PATH_SRLP_CONTENT_MIN_HEIGHT = 'klevu_search/developer/content_min_height_srlp';

    /**
     * Set the Enable on Frontend flag in System Configuration for the given store.
     *
     * @param mixed $flag
     * @param StoreInterface|string|int|null $store Store to set the flag for. Defaults to current store.
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
     * @param StoreInterface|string|int|null $storeId
     *
     * @return bool
     */
    public function isExtensionEnabled($storeId = null)
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(
            static::XML_PATH_EXTENSION_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if the Tax is enabled in the system configuration for the current store.
     *
     * @param StoreInterface|string|int|null $storeId
     *
     * @return bool
     */
    public function isTaxEnabled($storeId = null)
    {
        $displayTax = $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_TAX_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $taxOptions = [
            Taxoptions::YES,
            Taxoptions::ADMINADDED
        ];

        return in_array($displayTax, $taxOptions, true);
    }

    /**
     * Check if the Secure url is enabled in the system configuration for the current store.
     *
     * @param StoreInterface|string|int|null $storeId
     *
     * @return bool
     */
    public function isSecureUrlEnabled($storeId = null)
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(
            static::XML_PATH_SECUREURL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the configuration flag for sending config image.
     *
     * @param StoreInterface|string|int|null $storeId
     *
     * @return bool
     */
    public function isUseConfigImage($storeId = null)
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(
            static::XML_PATH_CONFIG_IMAGE_FLAG,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if the Landing is enabled in the system configuration for the current store.
     *
     * @param StoreInterface|string|int|null $storeId
     *
     * @return int
     */
    public function isLandingEnabled($storeId = null)
    {
        $store = null;
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore($storeId);
        } catch (NoSuchEntityException $exception) {
            $this->_logger->error($exception->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);
        }

        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_LANDING_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Set the Tax mode in System Configuration for the given store.
     *
     * @param string $flag
     * @param StoreInterface|string|int|null $store Store to use. If not specified, uses the current store.
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
     * @param mixed $flag
     * @param StoreInterface|string|int|null $store Store to use. If not specified, uses the current store.
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
     * @param string $key
     * @param StoreInterface|string|int|null $store Store to use. If not specified, will use the current store.
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
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    public function getJsApiKey($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_JS_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Set the REST API key in System Configuration for the given store.
     *
     * @param string $key
     * @param StoreInterface|string|int|null $store Store to use. If not specified, will use the current store.
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
     * @param StoreInterface|string|int|null $store
     *
     * @return mixed
     */
    public function getRestApiKey($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_REST_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Set the API Hostname value in System Configuration for a given store
     *
     * @param string $hostname
     * @param StoreInterface|string|int|null $store
     *
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
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    public function getHostname($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_HOSTNAME,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreIdFromStoreArgument($store)
        ) ?: ApiHelper::ENDPOINT_DEFAULT_HOSTNAME;
    }

    /**
     * @param string $apiUrl
     * @param StoreInterface|string|int|null $store
     *
     * @return $this
     */
    public function setApiUrl($apiUrl, $store = null)
    {
        return $this->setStoreConfig(static::XML_PATH_API_URL, $apiUrl, $store);
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    public function getApiUrl($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_API_URL,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreIdFromStoreArgument($store)
        ) ?: ApiHelper::ENDPOINT_DEFAULT_API_URL;
    }

    /**
     * Return the API Rest Hostname configured, used for API requests, for a specified store
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    public function getRestHostname($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_RESTHOSTNAME,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreIdFromStoreArgument($store)
        ) ?: ApiHelper::ENDPOINT_DEFAULT_HOSTNAME;
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return int|null
     */
    private function getStoreIdFromStoreArgument($store)
    {
        switch (true) {
            case $store instanceof StoreInterface:
                $storeId = (int)$store->getId();
                break;

            case is_int($store):
            case is_string($store) && ctype_digit($store):
                $storeId = (int)$store;
                break;

            case null === $store:
                try {
                    $store = $this->_storeModelStoreManagerInterface->getStore();
                    $storeId = (int)$store->getId();
                } catch (NoSuchEntityException $e) {
                    $this->_logger->error($e->getMessage());
                    $storeId = null;
                }
                break;

            default:
                throw new \InvalidArgumentException(sprintf(
                    'Invalid store parameter. Expected %s|int|null; received %s',
                    StoreInterface::class,
                    is_object($store) ? get_class($store) : gettype($store) //phpcs:ignore
                ));
                break; // phpcs:ignore
        }

        return $storeId;
    }

    /**
     * Set the Rest Hostname value in System Configuration for a given store
     *
     * @param string $url
     * @param StoreInterface|string|int|null $store
     *
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
     * @param StoreInterface|string|int|null $store
     *
     * @return $this
     */
    public function setCloudSearchUrl($url, $store = null)
    {
        $path = static::XML_PATH_CLOUD_SEARCH_URL;
        $this->setStoreConfig($path, $url, $store);

        return $this;
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
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
     * @param StoreInterface|string|int|null $store
     *
     * @return $this
     */
    public function setCloudSearchV2Url($url, $store = null)
    {
        $path = static::XML_PATH_CLOUD_SEARCH_V2_URL;
        $this->setStoreConfig($path, $url, $store);

        return $this;
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
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
     * @param string $url
     * @param StoreInterface|string|int|null $store
     *
     * @return $this
     */
    public function setAnalyticsUrl($url, $store = null)
    {
        $path = static::XML_PATH_ANALYTICS_URL;
        $this->setStoreConfig($path, $url, $store);

        return $this;
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    public function getAnalyticsUrl($store = null)
    {
        $url = $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_ANALYTICS_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $url ?: ApiHelper::ENDPOINT_DEFAULT_ANALYTICS_HOSTNAME;
    }

    /**
     * @param string $url
     * @param StoreInterface|string|int|null $store
     *
     * @return $this
     */
    public function setJsUrl($url, $store = null)
    {
        $path = static::XML_PATH_JS_URL;
        $this->setStoreConfig($path, $url, $store);

        return $this;
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    public function getJsUrl($store = null)
    {
        $url = $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_JS_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $url ?: ApiHelper::ENDPOINT_DEFAULT_HOSTNAME;
    }

    /**
     * Check if the Klevu Search extension is configured for the given store.
     *
     * @param StoreInterface|string|int|null $storeId
     *
     * @return bool
     */
    public function isExtensionConfigured($storeId = null)
    {
        $js_api_key = $this->getJsApiKey($storeId);
        $rest_api_key = $this->getRestApiKey($storeId);

        return (
            $this->isExtensionEnabled($storeId)
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
     * @param StoreInterface|string|int|null $storeId
     *
     * @return int
     */
    public function getProductSyncEnabledFlag($storeId = Store::DEFAULT_STORE_ID)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_PRODUCT_SYNC_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if Product Sync is enabled for the specified store and domain.
     *
     * @param StoreInterface|string|int|null $storeId
     *
     * @return bool
     */
    public function isProductSyncEnabled($storeId = Store::DEFAULT_STORE_ID)
    {
        $flag = $this->getProductSyncEnabledFlag($storeId);

        return in_array(
            $flag,
            [
                Yesnoforced::YES,
                static::KLEVU_PRODUCT_FORCE_OLDERVERSION
            ],
            true
        );
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
     *
     * @return string|null
     */
    public function getLastProductSyncRun($store = null)
    {
        $resource = $this->_frameworkModelResource;
        $connection = $resource->getConnection();
        $select = $connection->select()
            ->from(['k' => $resource->getTableName("klevu_product_sync")])
            ->order(['k.last_synced_at DESC'])
            ->limit(1);

        // retrieve last sync across all stores if none specified
        if ($store !== null) {
            $select->where("k.store_id = ?", $store);
        }

        $result = $connection->fetchAll($select);
        if (empty($result) || !isset($result[0]['last_synced_at'])) {
            return null;
        }
        try {
            $datetime = new \DateTime($result[0]['last_synced_at']);

            return $datetime->format('Y-m-d H:i:s');
        } catch (\Exception $exception) {
            $this->_logger->error($exception->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);
        }

        return null;
    }

    /**
     * Check if Product Sync has ever run for the given store.
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return bool
     */
    public function hasProductSyncRun($store = null)
    {
        $config = $this->_appConfigScopeConfigInterface;

        return (bool)($config->getValue(
            static::XML_PATH_PRODUCT_SYNC_LAST_RUN,
            ScopeInterface::SCOPE_STORE,
            $store
        ));
    }

    /**
     * @param array $map
     * @param StoreInterface|string|int|null $store
     *
     * @return $this
     */
    public function setAdditionalAttributesMap($map, $store = null)
    {
        unset($map["__empty"]);
        $this->setStoreConfig(
            static::XML_PATH_ATTRIBUTES_ADDITIONAL,
            serialize($map), //phpcs:ignore
            $store
        );

        return $this;
    }

    /**
     * Return the map of additional Klevu attributes to Magento attributes.
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return array
     */
    public function getAdditionalAttributesMap($store = null)
    {
        $map = unserialize( // phpcs:ignore
            $this->_appConfigScopeConfigInterface->getValue(
                static::XML_PATH_ATTRIBUTES_ADDITIONAL,
                ScopeInterface::SCOPE_STORES,
                $store
            )
        );

        return (is_array($map)) ? $map : [];
    }

    /**
     * Set the automatically mapped attributes
     *
     * @param array $map
     * @param StoreInterface|string|int|null $store
     *
     * @return $this
     */
    public function setAutomaticAttributesMap($map, $store = null)
    {
        unset($map["__empty"]);
        $this->setStoreConfig(
            static::XML_PATH_ATTRIBUTES_AUTOMATIC,
            serialize($map), // phpcs:ignore
            $store
        );

        return $this;
    }

    /**
     * Returns the automatically mapped attributes
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return array
     */
    public function getAutomaticAttributesMap($store = null)
    {
        $map = unserialize( // phpcs:ignore
            $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_ATTRIBUTES_AUTOMATIC, $store)
        );

        return (is_array($map)) ? $map : [];
    }

    /**
     * Return the System Configuration setting for enabling Order Sync for the given store.
     * The returned value can have one of three possible meanings: Yes, No and Forced. The
     * values mapping to these meanings are available as constants on
     * \Klevu\Search\Model\System\Config\Source\Yesnoforced.
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function getOrderSyncEnabledFlag($store = Store::DEFAULT_STORE_ID)
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
     * @param StoreInterface|string|int|null $store
     *
     * @return bool
     */
    public function isOrderSyncEnabled($store = null)
    {
        $flag = $this->getOrderSyncEnabledFlag($store);

        return in_array($flag, [
            Yesnoforced::YES,
            static::KLEVU_PRODUCT_FORCE_OLDERVERSION
        ], true);
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
     * @param StoreInterface|string|int|null $storeId
     *
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
        if (!$datetime instanceof \DateTime) {
            try {
                $datetime = new \DateTime($datetime);
                $this->setGlobalConfig(
                    static::XML_PATH_ORDER_SYNC_LAST_RUN,
                    $datetime->format('Y-m-d H:i:s T')
                );
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);
            }
        }

        return $this;
    }

    /**
     * Check if default Magento log settings should be overridden to force logging for this module.
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return bool
     * @deprecated
     */
    public function isLoggingForced($store = null) //phpcs:ignore
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(
            static::XML_PATH_FORCE_LOG,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Return the minimum log level configured. Default to LoggerConstants::ZEND_LOG_INFO.
     *
     * @param StoreInterface|string|int|null $scope
     *
     * @return int
     */
    public function getLogLevel($scope = null)
    {
        try {
            if (null === $scope && 'adminhtml' === $this->appState->getAreaCode()) {
                $scope = $this->_frameworkAppRequestInterface->getParam('store');
            }
        } catch (LocalizedException $e) { // phpcs:ignore
            // Intentionally left empty
            // appAreaCode is not set, continue with null $scope
        }
        $log_level = $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_LOG_LEVEL,
            ScopeInterface::SCOPE_STORE,
            $scope
        );

        return is_numeric($log_level) ? (int)$log_level : LoggerConstants::ZEND_LOG_INFO;
    }

    /**
     * @param string $url
     * @param StoreInterface|string|int|null $store
     *
     * @return $this
     */
    public function setTiresUrl($url, $store = null)
    {
        $path = static::XML_PATH_UPGRADE_TIRES_URL;
        $this->setStoreConfig($path, $url, $store);

        return $this;
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    public function getTiresUrl($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_UPGRADE_TIRES_URL,
            ScopeInterface::SCOPE_STORE,
            $store
        ) ?: ApiHelper::ENDPOINT_DEFAULT_HOSTNAME;
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
                Rating::ATTRIBUTE_CODE,
                ReviewCount::ATTRIBUTE_CODE,
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
                Rating::ATTRIBUTE_CODE,
                'rating_count',
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
     * @param StoreInterface|string|int|null $store
     *
     * @return array
     */
    public function getOtherAttributesToIndex($store = null)
    {
        $otherAttributes = $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_ATTRIBUTES_OTHER,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return ($otherAttributes) ? explode(",", $otherAttributes) : [];
    }

    /**
     * Return the boosting attribute defined in store configuration.
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return array|mixed
     */
    public function getBoostingAttribute($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_ATTRIBUTES_BOOSTING,
            ScopeInterface::SCOPE_STORE,
            $store
        );
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
        $this->configResource->saveConfig($key, $value, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        $this->resetConfig();

        return $this;
    }

    /**
     * Set the store scope System Configuration value for the given key.
     *
     * @param string $key
     * @param string $value
     * @param StoreInterface|string|int|null $store If not given, current store will be used.
     *
     * @return $this
     */
    public function setStoreConfig($key, $value, $store = null)
    {
        try {
            $storeObject = $this->_storeModelStoreManagerInterface->getStore($store);
            $scope_id = $storeObject->getId();
            if ($scope_id !== null) {
                $this->configResource->saveConfig($key, $value, ScopeInterface::SCOPE_STORES, $scope_id);
            }
        } catch (NoSuchEntityException $e) {
            $this->_logger->error($e->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);
        }

        return $this;
    }

    /**
     * Clear config cache
     */
    public function resetConfig()
    {
        ObjectManager::getInstance()->get(ReinitableConfigInterface::class)->reinit();
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
     * @return $this
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
     * @return $this
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
     * @param StoreInterface|string|int|null $store
     *
     * @return $this
     */
    public function saveRatingUpgradeFlag($value, $store)
    {
        $this->setStoreConfig(static::XML_PATH_RATING, $value, $store);

        return $this;
    }

    /**
     * get upgrade rating value
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function getRatingUpgradeFlag($store = null)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_RATING,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param string $elementId
     *
     * @return array
     * @deprecated
     * @see \Klevu\Search\Service\Account\GetFeatures::execute
     *
     * get feature update
     */
    public function getFeaturesUpdate($elementId)
    {
        try {
            if (!$this->_klevu_features_response) {
                $this->_klevu_features_response = $this->getFeatures();
            }
            $features = $this->_klevu_features_response;

            if (!empty($features) && !empty($features['disabled'])) {
                $checkStr = explode("_", $elementId);
                $disable_features = explode(",", $features['disabled']);
                $code = $this->_frameworkAppRequestInterface->getParam('store');// store level
                $store = $this->_frameworkModelStore->load($code);

                if (in_array("preserves_layout", $disable_features, true) &&
                    $this->_frameworkAppRequestInterface->getParam('section') === "klevu_search"
                ) {
                    // when some upgrade plugin if default value set to 1 means preserve layout
                    // then convert to klevu template layout
                    $landingEnabled = $this->_appConfigScopeConfigInterface->getValue(
                        static::XML_PATH_LANDING_ENABLED,
                        ScopeInterface::SCOPE_STORE,
                        $store
                    );
                    if ((int)$landingEnabled === Landingoptions::YES) {
                        $this->setStoreConfig(
                            static::XML_PATH_LANDING_ENABLED,
                            Landingoptions::KlEVULAND,
                            $store
                        );
                    }
                }

                if ($this->_frameworkAppRequestInterface->getParam('section') === "klevu_search" &&
                    in_array($checkStr[count($checkStr) - 1], $disable_features, true)
                ) {
                    $check = $checkStr[count($checkStr) - 1];
                    if (!empty($check)) {
                        $collection = $this->_modelConfigData->getCollection();
                        $configs = $collection->addFieldToFilter('path', ["like" => '%/' . $check . '%']);
                        $configs->load();
                        $data = $configs->getData();
                        if (!empty($data)) {
                            $this->setStoreConfig($data[0]['path'], 0, $store);
                        }

                        return $features;
                    }
                }
            }
        } catch (\Exception $e) {
            // OM can not be removed due to circular dependency
            $searchHelper = ObjectManager::getInstance()->get(SearchHelper::class);
            $searchHelper->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf(
                    "Error occured while getting features based on account %s::%s - %s",
                    __CLASS__,
                    __METHOD__,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Save the upgrade features defined in store configuration.
     *
     * @param mixed $value
     * @param StoreInterface|string|int|null $store
     */
    public function saveUpgradeFetaures($value, $store = null)
    {
        $savedValue = $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_UPGRADE_FEATURES,
            ScopeInterface::SCOPE_STORE,
            $store
        );
        if (isset($savedValue) && !empty($savedValue)) {
            if ($savedValue !== $value) {
                $this->setStoreConfig(static::XML_PATH_UPGRADE_FEATURES, $value, $store);
            }
        } else {
            $this->setStoreConfig(static::XML_PATH_UPGRADE_FEATURES, $value, $store);
        }
    }

    /**
     * Return the upgrade features defined in store configuration.
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    public function getUpgradeFetaures($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_UPGRADE_FEATURES,
            ScopeInterface::SCOPE_STORE,
            $store
        );
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
        return (int)$this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_TRIGGER_OPTIONS);
    }

    /**
     * save Trigger option value
     *
     * @param string $value
     *
     * @return $this
     */
    public function saveTriggerOptions($value)
    {
        $this->setGlobalConfig(static::XML_PATH_TRIGGER_OPTIONS, $value);

        return $this;
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    public function getImageHeight($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_CONFIG_IMAGE_HEIGHT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    public function getImageWidth($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_CONFIG_IMAGE_WIDHT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Return the klevu cron stettings.
     *
     * @return bool
     * @deprecated Method name does not match logic, please use isExternalCronActive() instead.
     * @see isExternalCronActive
     */
    public function isExternalCronEnabled()
    {
        $cronFrequency = $this->_appConfigScopeConfigInterface->getValue(static::XML_PATH_CONFIG_SYNC_FREQUENCY);

        return $cronFrequency !== "0 5 31 2 *";
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

        return $configFrequency === Frequency::CRON_NEVER;
    }

    /**
     * Return admin already included tax in price or not.
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return bool
     */
    public function getPriceIncludesTax($store = null)
    {
        return (bool)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_PRICE_INCLUDES_TAX,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if the Tax is include/exclude in the system configuration for the current store.
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return bool
     */
    public function isTaxCalRequired($store = null)
    {
        $taxDisplay = $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_PRICE_TYPEINSEARCH_METHOD,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $taxDisplay === \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX;
    }

    /**
     * Return the Price Include/Exclude Tax.
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function getPriceDisplaySettings($store = null)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_PRICE_DISPLAY_METHOD,
            ScopeInterface::SCOPE_STORE,
            $store
        );
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
     * @return array|string
     * @see \Klevu\Search\Service\Account\GetFeatures
     *
     * Get current store features based on klevu search account
     *
     * @deprecated
     */
    public function getFeatures()
    {
        // OM can not be removed due to circular dependency
        $searchHelper = ObjectManager::getInstance()->get(SearchHelper::class);
        try {
            $code = (string)$this->_frameworkAppRequestInterface->getParam('store');
            if ($code !== '') { // store level
                if (!$this->_klevu_features_response) {
                    $store = $this->_frameworkModelStore->load($code);
                    $store_id = $store->getId();
                    $restapi = $this->getRestApiKey($store_id);
                    if (!empty($restapi)) {
                        $this->_klevu_features_response = $this->executeFeatures($restapi, $store);
                    } else {
                        return '';
                    }
                }

                return $this->_klevu_features_response;
            }
        } catch (RuntimeException $re) {
            $searchHelper->log(
                LoggerConstants::ZEND_LOG_INFO,
                sprintf("Unable to get Klevu Features list (%s)", $re->getMessage())
            );
        } catch (\Exception $e) {
            $searchHelper->log(
                LoggerConstants::ZEND_LOG_INFO,
                sprintf("Uncaught Exception thrown while getting Klevu Features list (%s)", $e->getMessage())
            );
        }

        return '';
    }

    /**
     * @param string $restApi
     * @param StoreInterface $store
     *
     * @return array
     * @throws LocalizedException
     * @deprecated
     * @see \Klevu\Search\Service\Account\GetFeatures
     *
     * Get the features from config value if not get any response from api
     */
    public function executeFeatures($restApi, $store)
    {
        if (!$this->_klevu_enabled_feature_response) {
            $param = ["restApiKey" => $restApi, "store" => $store->getId()];
            // OM can not be removed due to circular dependency
            $apiGetFeatures = ObjectManager::getInstance()->get(ApiGetFeatures::class);
            $featuresRequest = $apiGetFeatures->execute($param);
            if ($featuresRequest->isSuccess()) {
                $this->_klevu_enabled_feature_response = $featuresRequest->getData();
                $this->saveUpgradeFetaures(
                    serialize($this->_klevu_enabled_feature_response), // phpcs:ignore
                    $store
                );
            } else {
                if (!empty($restApi)) {
                    $this->_klevu_enabled_feature_response = unserialize( // phpcs:ignore
                        $this->getUpgradeFetaures($store)
                    );
                }
                // OM can not be removed due to circular dependency
                $searchHelper = ObjectManager::getInstance()->get(SearchHelper::class);
                $searchHelper->log(
                    LoggerConstants::ZEND_LOG_INFO,
                    sprintf(
                        "failed to fetch feature details (%s)",
                        implode(', ', array_filter([
                            $featuresRequest->getMessage(),
                            $featuresRequest->getError(),
                        ]))
                    )
                );
            }
        }

        return $this->_klevu_enabled_feature_response;
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    public function getCatalogSearchRelevance($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_CATALOG_SEARCH_RELEVANCE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Return Catalog Visibility Sync.
     *
     * @param StoreInterface|string|int|null $storeId
     *
     * @return bool
     */
    public function useCatalogVisibitySync($storeId = null)
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(
            static::XML_PATH_PRODUCT_SYNC_CATALOGVISIBILITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return Current Configured Catalog search engine.
     *
     * @return string
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
     * @return mixed
     */
    public function getModuleInfoRecs()
    {
        return $this->_versionReader->getVersionString('Klevu_Recommendations');
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
        return (int)$this->_appConfigScopeConfigInterface->getValue(
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
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    public function getCatalogSearchRelevanceLabel($store = null)
    {
        $sortLabel = $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_CATALOG_SEARCH_RELEVANCE_LABEL,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $sortLabel ?: __('Relevance');
    }

    /**
     * Returns selection lockfile option
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return mixed
     */
    public function getSelectedLockFileOption($store = null)
    {
        return $this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_SYNC_LOCKFILE_OPTION,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns the category sync status
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function getCategorySyncEnabledFlag($store = Store::DEFAULT_STORE_ID)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_CATEGORY_SYNC_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns notification flag for object method
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function getObjMethodNotificationOption($store = Store::DEFAULT_STORE_ID)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_NOTIFICATION_OBJECT_VS_COLLECTION,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns notification flag for lock file warning
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function getLockFileNotificationOption($store = Store::DEFAULT_STORE_ID)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_NOTIFICATION_LOCK_FILE,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Check if Klevu preserve layout log enabled in settings
     *
     * @param StoreInterface|string|int|null $store
     *
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
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function getTreatCategoryAnchorAsSingle($store = Store::DEFAULT_STORE_ID)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_CATEGORY_ANCHOR,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Returns whether orders with same ip notification flag on or off
     *
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function isOrdersWithSameIPNotificationOptionEnabled($store = Store::DEFAULT_STORE_ID)
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
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function getPercentageOfOrders($store = Store::DEFAULT_STORE_ID)
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
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function getDaysToCalculateOrders($store = Store::DEFAULT_STORE_ID)
    {
        return (int)$this->_appConfigScopeConfigInterface->getValue(
            static::XML_PATH_DEVELOPER_DAYS_CALCULATE_ORDERS,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param StoreInterface|string|int|null $storeId
     *
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
     * @param StoreInterface|string|int|null $storeId
     *
     * @return bool
     */
    public function isRatingSyncEnable($storeId = null)
    {
        return $this->_appConfigScopeConfigInterface->isSetFlag(
            static::XML_PATH_RATING_SYNC_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
