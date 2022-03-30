<?php

namespace Klevu\Search\Helper;

use Klevu\Logger\Api\ConvertLogLevelServiceInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Debug;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Backend\Model\Url;
use \Klevu\Search\Helper\Config;
use \Psr\Log\LoggerInterface;
use \Magento\Catalog\Model\Product;
use Magento\Config\Model\ResourceModel\Config\Data\Collection;
use \Magento\Framework\App\RequestInterface;



class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    /**
     * @var \Magento\Backend\Model\Url
     */
    protected $_backendModelUrl;

    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_psrLogLoggerInterface;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_catalogModelProduct;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxHelperData;

    /**
     * @var \Magento\Eav\Model\Entity\Type
     */
    protected $_modelEntityType;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    protected $_modelEntityAttribute;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $_localeCurrency;

    /**
     * @var Magento\Directory\Model\CurrencyFactory
     */
    protected $_currencyFactory;

	/**
     * @var Magento\Config\Model\ResourceModel\Config\Data\Collection
     */
    protected $_configDataCollection;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $_frameworkModelStore;

    protected $_klevu_features_response;

    protected $_klevu_enabled_feature_response;

    private $convertLogLevelService;

    /**
     * @var LoggerInterface
     */
    private $searchLogger;

    /**
     * @var LoggerInterface
     */
    private $preserveLayoutLogger;

    /**
     * Data constructor.
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param Url $backendModelUrl
     * @param \Klevu\Search\Helper\Config $searchHelperConfig
     * @param LoggerInterface $psrLogLoggerInterface
     * @param Product $catalogModelProduct
     * @param \Magento\Catalog\Helper\Data $taxHelperData
     * @param \Magento\Eav\Model\Entity\Type $modelEntityType
     * @param \Magento\Eav\Model\Entity\Attribute $modelEntityAttribute
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param Collection $configDataCollection
     * @param LoggerInterface|null $searchLogger
     * @param LoggerInterface|null $preserveLayoutLogger
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Magento\Backend\Model\Url $backendModelUrl,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        \Psr\Log\LoggerInterface $psrLogLoggerInterface,
        \Magento\Catalog\Model\Product $catalogModelProduct,
        \Magento\Catalog\Helper\Data $taxHelperData,
        \Magento\Eav\Model\Entity\Type $modelEntityType,
        \Magento\Eav\Model\Entity\Attribute $modelEntityAttribute,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
		\Magento\Config\Model\ResourceModel\Config\Data\Collection $configDataCollection,
        ConvertLogLevelServiceInterface $convertLogLevelService = null,
        LoggerInterface $searchLogger = null,
        LoggerInterface $preserveLayoutLogger = null
    ) {
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_backendModelUrl = $backendModelUrl;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_psrLogLoggerInterface = $psrLogLoggerInterface;
        $this->_catalogModelProduct = $catalogModelProduct;
        $this->_taxHelperData = $taxHelperData;
        $this->_modelEntityType = $modelEntityType;
        $this->_modelEntityAttribute = $modelEntityAttribute;
        $this->_localeCurrency = $localeCurrency;
        $this->_currencyFactory = $currencyFactory;
		$this->_configDataCollection = $configDataCollection;
		$this->convertLogLevelService = $convertLogLevelService;
        $this->searchLogger = $searchLogger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->preserveLayoutLogger = $preserveLayoutLogger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
    }


    const LOG_FILE = "Klevu_Search.log";

    const PRESERVE_LAYOUT_LOG_FILE = "Klevu_Search_Preserve_Layout.log";

    const ID_SEPARATOR = "-";

	const SKU_SEPARATOR = ";;;;";

    const SANITISE_STRING = "/:|,|;/";

    /**
     * Given a locale code, extract the language code from it
     * e.g. en_GB => en, fr_FR => fr
     *
     * @param string $locale
     */
    public function getLanguageFromLocale($locale)
    {
        if (strlen((string)$locale) == 5 && strpos($locale, "_") === 2) {
            return substr($locale, 0, 2);
        }

        return $locale;
    }

    /**
     * Return the language code for the given store.
     *
     * @param int|\Magento\Framework\Model\Store $store
     *
     * @return string
     */
    public function getStoreLanguage($store = null)
    {
        if ($store = $this->_storeModelStoreManagerInterface->getStore($store)) {
            return $this->getLanguageFromLocale($store->getConfig(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE));
        }
    }

    /**
     * Return the timezone for the given store.
     *
     * @param int|\Magento\Framework\Model\Store $store
     *
     * @return string
     */
    public function getStoreTimeZone($store = null)
    {
        if ($store = $this->_storeModelStoreManagerInterface->getStore($store)) {
            return $this->getLanguageFromLocale($store->getConfig(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_TIMEZONE));
        }
    }

    /**
     * Check if the given domain is considered to be a valid domain for a production environment.
     *
     * @param $domain
     *
     * @return bool
     */
    public function isProductionDomain($domain)
    {
        return preg_match("/\b(staging|dev|local)\b/", $domain) == 0;
    }

    /**
     * Generate a Klevu product ID for the given product.
     *
     * @param int      $product_id Magento ID of the product to generate a Klevu ID for.
     * @param null|int $parent_id  Optional Magento ID of the parent product.
     *
     * @return string
     */
    public function getKlevuProductId($product_id, $parent_id = 0)
    {
        if ($parent_id != 0) {
            $parent_id .= static::ID_SEPARATOR;
        } else {
            $parent_id = "";
        }

        return sprintf("%s%s", $parent_id, $product_id);
    }

    /**
     * Convert a Klevu product ID back into a Magento product ID. Returns an
     * array with "product_id" element for the product ID and a "parent_id"
     * element for the parent product ID or 0 if the Klevu product doesn't have
     * a parent.
     *
     * @param $klevu_id
     *
     * @return array
     */
    public function getMagentoProductId($klevu_id)
    {
        $parts = explode(static::ID_SEPARATOR, $klevu_id, 2);

        if (count($parts) > 1) {
            return ['product_id' => $parts[1], 'parent_id' => $parts[0]];
        } else {
            return ['product_id' => $parts[0], 'parent_id' => "0"];
        }
    }

    /**
     * Format bytes into a human readable representation, e.g.
     * 6815744 => 6.5M
     *
     * @param     $bytes
     * @param int $precision
     *
     * @return string
     */
    public function bytesToHumanReadable($bytes, $precision = 2)
    {
        $suffixes = ["", "k", "M", "G", "T", "P"];
        $base = log($bytes) / log(1024);
        return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
    }

    /**
     * Convert human readable formatting of bytes to bytes, e.g.
     * 6.5M => 6815744
     *
     * @param $string
     *
     * @return int
     */
    public function humanReadableToBytes($string)
    {
        $suffix = strtolower(substr($string, -1));
		$result = intval(substr($string, 0, -1));
        switch ($suffix) {
            case 'g':
                $result *= 1024;
            case 'm':
                $result *= 1024;
            case 'k':
                $result *= 1024;
                break;
            default:
                $result = $string;
        }

        return ceil($result);
    }

    /**
     * Return the configuration data for a "Sync All Products" button displayed
     * on the Manage Products page in the backend.
     *
     * @return array
     */
    public function getSyncAllButtonData()
    {
        return [
            'label'   => __("Sync All Products to Klevu"),
            'onclick' => sprintf("setLocation('%s')", $this->_backendModelUrl->getUrl("adminhtml/klevu_search/sync_all"))
        ];
    }

    /**
     * Write a log message to the \Klevu\Search log file.
     *
     * @param int    $level
     * @param string $message
     * @return void
     * @deprecated Logging should be handled through implementation of LoggerInterface provided by Klevu_Logger
     * @see Klevu\Logger\Logger\Logger
     */
    public function log($level, $message)
    {
        if ($this->convertLogLevelService) {
            // Levels may be passed in Zend format, but logger requires Monolog levels
            $level = $this->convertLogLevelService->toNumeric((string)$level);
        }

        $this->searchLogger->log($level, $message);
    }

    /**
     * Write a log message to the \Klevu\Search\preserve log file.
     *
     * @param string $message
     * @return void
     * @deprecated Logging should be handled through implementation of LoggerInterface provided by Klevu_Logger
     * @see Klevu\Logger\Logger\Logger
     */
    public function preserveLayoutLog($message)
    {
        $this->preserveLayoutLogger->info($message);
    }

    /**
     * Remove the characters used to organise the other attribute values from the
     * passed in string.
     *
     * @param string $value
     * @return string
     */
    public function santiseAttributeValue($value)
    {
        if (is_array($value) && !empty($value)) {
            $sanitised_array = [];
            foreach ($value as $item) {
                if (!is_array($item) && !is_object($item)) {
                    $sanitised_array[] = preg_replace(self::SANITISE_STRING, " ", $item);
                }
            }
            return $sanitised_array;
        }
        return preg_replace(self::SANITISE_STRING, " ", $value);
    }

    /**
     * Generate a Klevu product sku with parent product.
     *
     * @param string      $product_sku Magento Sku of the product to generate a Klevu sku for.
     * @param null $parent_sku  Optional Magento Parent Sku of the parent product.
     *
     * @return string
     */
    public function getKlevuProductSku($product_sku, $parent_sku = "")
    {
        if (!empty($parent_sku)) {
            if(!is_array($parent_sku)) {
                $parent_sku .= static::SKU_SEPARATOR;
            }
        } else {
            $parent_sku = "";
        }
        if(!is_array($parent_sku)) {
            return sprintf("%s%s", $parent_sku, $product_sku);
        } else {
            return $product_sku;
        }
    }


    /**
     * Get the is active attribute id
     *
     * @return string
     */
    public function getIsActiveAttributeId()
    {
        $entity_type = $this->_modelEntityType->loadByCode("catalog_category");
        $entity_typeid = $entity_type->getId();
        $attributecollection = $this->_modelEntityAttribute->getCollection()->addFieldToFilter("entity_type_id", $entity_typeid)->addFieldToFilter("attribute_code", "is_active");
        $attribute = $attributecollection->getFirstItem();
        return $attribute->getAttributeId();
    }

    /**
     * Get the attribute id for media gallery
     *
     * @return string
     */
    public function getIsMediaGalleryAttributeId()
    {
        $entity_type = $this->_modelEntityType->loadByCode("catalog_product");
        $entity_typeid = $entity_type->getId();
        $attributecollection = $this->_modelEntityAttribute->getCollection()->addFieldToFilter("entity_type_id", $entity_typeid)->addFieldToFilter("attribute_code", "media_gallery");
        $attribute = $attributecollection->getFirstItem();
        return $attribute->getAttributeId();
    }

    /**
     * Get the is active attribute id
     *
     * @return string
     */
    public function getIsExcludeAttributeId()
    {
        $entity_type = $this->_modelEntityType->loadByCode("catalog_category");
        $entity_typeid = $entity_type->getId();
        $attributecollection = $this->_modelEntityAttribute->getCollection()->addFieldToFilter("entity_type_id", $entity_typeid)->addFieldToFilter("attribute_code", "is_exclude_cat");
        $attribute = $attributecollection->getFirstItem();
        return $attribute->getAttributeId();
    }

	public function processIp($ips)
	{
		$iplist = explode(',', $ips);
		if(count($iplist) > 1) {
            foreach ($iplist as $ip) {
				if (filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
				{
					return $ip;
				}
            }
		} else {
			return $ips;
		}
	}

    /**
     * Get the client ip
     *
     * @return string
     */
    public function getIp()
    {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $this->processIp($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $this->processIp($_SERVER['HTTP_X_FORWARDED']);
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $this->processIp($_SERVER['HTTP_FORWARDED_FOR']);
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ip = $this->processIp($_SERVER['HTTP_FORWARDED']);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = 'UNKNOWN';
        }

        return $this->processIp($ip);
    }

    /**
     * Get the currecy switcher data
     *
     * @return string
     */
    public function getCurrencyData($store)
    {
        $baseCurrencyCode = $store->getBaseCurrency()->getCode();
        $currentCurrencyCode = $store->getCurrentCurrencyCode();
        if ($baseCurrencyCode != $currentCurrencyCode) {
            $availableCurrencies = $store->getAvailableCurrencyCodes();
            $currencyResource = $this->_currencyFactory
            ->create()
            ->getResource();
            $currencyRates = $currencyResource->getCurrencyRates($baseCurrencyCode, array_values($availableCurrencies));
            if (count($availableCurrencies) > 1) {
                foreach ($currencyRates as $key => &$value) {
                    $Symbol = $this->_localeCurrency->getCurrency($key)->getSymbol() ? $this->_localeCurrency->getCurrency($key)->getSymbol() : $this->_localeCurrency->getCurrency($key)->getShortName();
                    $value = sprintf("'%s':'%s:%s'", $key, $value, $Symbol);
                }
                $currency = implode(",", $currencyRates);
                return $currency;
            }
        }
    }

	/**
     * get for base domain
     *
     * @return string
     */
	public function getBaseDomain() {
		$base_domain = $this->_storeModelStoreManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);
		if(!empty($base_domain)) {
			$base_url_value = parse_url($base_domain);
			return $base_url_value['host'];
		}
    }

	/**
     * return JsApiKey
     *
     * @return string
     */
	public function getJsApiKey() {
		return $this->_searchHelperConfig->getJsApiKey();
    }

	/**
     * Return the value of store id from api key.
     *
     * @param $klevuApi
     *
     * @return int
     */
	public function storeFromScopeId(){
		$configs =  $this->_configDataCollection->addFieldToFilter('value',$this->getJsApiKey())->load();
        $scope_id = $configs->getData();
		if(!empty($scope_id)) {
			return $this->_storeModelStoreManagerInterface->getStore(intval($scope_id[0]['scope_id']));
		}
	}

}
