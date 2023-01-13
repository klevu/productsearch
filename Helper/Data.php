<?php

namespace Klevu\Search\Helper;

use InvalidArgumentException;
use Klevu\Logger\Api\ConvertLogLevelServiceInterface;
use Klevu\Logger\Logger\Logger;
use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigDataCollection;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Eav\Model\Entity\Attribute as EntityAttribute;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Backend\Model\Url;
use Magento\Tax\Helper\Data as TaxHelper;
use \Psr\Log\LoggerInterface;
use \Magento\Catalog\Model\Product;
use Magento\Config\Model\ResourceModel\Config\Data\Collection;

class Data extends AbstractHelper
{
    const LOG_FILE = "Klevu_Search.log";
    const PRESERVE_LAYOUT_LOG_FILE = "Klevu_Search_Preserve_Layout.log";
    const ID_SEPARATOR = "-";
    const SKU_SEPARATOR = ";;;;";
    const SANITISE_STRING = "/:|,|;/";

    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    /**
     * @var Url
     */
    protected $_backendModelUrl;

    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;

    /**
     * @var LoggerInterface
     */
    protected $_psrLogLoggerInterface;

    /**
     * @var ProductInterface
     */
    protected $_catalogModelProduct;

    /**
     * @var TaxHelper
     */
    protected $_taxHelperData;

    /**
     * @var EntityType
     */
    protected $_modelEntityType;

    /**
     * @var EntityAttribute
     */
    protected $_modelEntityAttribute;

    /**
     * @var CurrencyInterface
     */
    protected $_localeCurrency;

    /**
     * @var CurrencyFactory
     */
    protected $_currencyFactory;

    /**
     * @var ConfigDataCollection
     */
    protected $_configDataCollection;

    /**
     * @var mixed
     * deprecated no used in class
     * maintained for backward compatibility
     */
    protected $_frameworkModelStore;

    /**
     * @var mixed
     * deprecated no used in class
     * maintained for backward compatibility
     */
    protected $_klevu_features_response;

    /**
     * @var mixed
     * deprecated no used in class
     * maintained for backward compatibility
     */
    protected $_klevu_enabled_feature_response;

    /**
     * @var ConvertLogLevelServiceInterface
     */
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
     *
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param Url $backendModelUrl
     * @param ConfigHelper $searchHelperConfig
     * @param LoggerInterface $psrLogLoggerInterface
     * @param Product $catalogModelProduct
     * @param CatalogHelper $taxHelperData
     * @param EntityType $modelEntityType
     * @param EntityAttribute $modelEntityAttribute
     * @param CurrencyFactory $currencyFactory
     * @param CurrencyInterface $localeCurrency
     * @param Collection $configDataCollection
     * @param ConvertLogLevelServiceInterface $convertLogLevelService
     * @param LoggerInterface $searchLogger
     * @param LoggerInterface $preserveLayoutLogger
     */
    public function __construct(
        StoreManagerInterface $storeModelStoreManagerInterface,
        Url $backendModelUrl,
        ConfigHelper $searchHelperConfig,
        LoggerInterface $psrLogLoggerInterface,
        Product $catalogModelProduct,
        CatalogHelper $taxHelperData,
        EntityType $modelEntityType,
        EntityAttribute $modelEntityAttribute,
        CurrencyFactory $currencyFactory,
        CurrencyInterface $localeCurrency,
        ConfigDataCollection $configDataCollection,
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
        $objectManager = ObjectManager::getInstance();
        $this->searchLogger = $searchLogger ?: $objectManager->get(LoggerInterface::class);
        $this->preserveLayoutLogger = $preserveLayoutLogger ?: $objectManager->get(LoggerInterface::class);
    }

    /**
     * Given a locale code, extract the language code from it
     * e.g. en_GB => en, fr_FR => fr
     *
     * @param string $locale
     */
    public function getLanguageFromLocale($locale)
    {
        if (strlen((string)$locale) === 5 && strpos($locale, "_") === 2) {
            return substr($locale, 0, 2);
        }

        return $locale;
    }

    /**
     * Return the language code for the given store.
     *
     * @param int|StoreInterface $store
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreLanguage($store = null)
    {
        if ($store = $this->_storeModelStoreManagerInterface->getStore($store)) {
            return $this->getLanguageFromLocale(
                $store->getConfig(DirectoryHelper::XML_PATH_DEFAULT_LOCALE)
            );
        }
    }

    /**
     * Return the timezone for the given store.
     *
     * @param int|StoreInterface $store
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreTimeZone($store = null)
    {
        if ($store = $this->_storeModelStoreManagerInterface->getStore($store)) {
            return $this->getLanguageFromLocale(
                $store->getConfig(DirectoryHelper::XML_PATH_DEFAULT_TIMEZONE)
            );
        }
    }

    /**
     * Check if the given domain is considered to be a valid domain for a production environment.
     *
     * @param string $domain
     *
     * @return bool
     */
    public function isProductionDomain($domain)
    {
        if (!is_scalar($domain)) {
            return false;
        }

        return preg_match("/\b(staging|dev|local)\b/", (string)$domain) === 0;
    }

    /**
     * Generate a Klevu product ID for the given product.
     *
     * @param int $productId Magento ID of the product to generate a Klevu ID for.
     * @param null|int $parentId Optional Magento ID of the parent product.
     *
     * @return string
     */
    public function getKlevuProductId($productId, $parentId = 0)
    {
        $formattedParentId = "";
        if ((int)$parentId !== 0) {
            $formattedParentId = $parentId . static::ID_SEPARATOR;
        }

        return sprintf("%s%s", $formattedParentId, $productId);
    }

    /**
     * Convert a Klevu product ID back into a Magento product ID. Returns an
     * array with "product_id" element for the product ID and a "parent_id"
     * element for the parent product ID or 0 if the Klevu product doesn't have
     * a parent.
     *
     * @param string $klevuId
     *
     * @return array|null
     */
    public function getMagentoProductId($klevuId)
    {
        if (!is_scalar($klevuId)) {
            return null;
        }
        $parts = explode(static::ID_SEPARATOR, $klevuId, 2);

        if (count($parts) > 1) {
            return ['product_id' => $parts[1], 'parent_id' => $parts[0]];
        }

        return ['product_id' => $parts[0], 'parent_id' => "0"];
    }

    /**
     * Format bytes into a human readable representation, e.g.
     * 6815744 => 6.5M
     *
     * @param int $bytes
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
     * @param string $string
     *
     * @return int
     */
    public function humanReadableToBytes($string)
    {
        $suffix = strtolower(substr($string, -1));
        $result = (int)substr($string, 0, -1);
        switch ($suffix) {
            case 'g':
                $result *= 1024;
            // no break
            case 'm':
                $result *= 1024;
            // no break
            case 'k':
                $result *= 1024;
                break;
            default:
                $result = $string;
                break;
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
            'label' => __("Sync All Products to Klevu"),
            'onclick' => sprintf(
                "setLocation('%s')",
                $this->_backendModelUrl->getUrl("adminhtml/klevu_search/sync_all")
            )
        ];
    }

    /**
     * Write a log message to the \Klevu\Search log file.
     *
     * @param int $level
     * @param string $message
     *
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
     *
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
     * @param string|array $value
     *
     * @return string|array
     */
    public function santiseAttributeValue($value)
    {
        if (is_array($value) && !empty($value)) {
            $sanitisedArray = [];
            foreach ($value as $item) {
                if (!is_array($item) && !is_object($item)) {
                    $sanitisedArray[] = $this->processCharHtml($item);
                }
            }

            return $sanitisedArray;
        }

        return $this->processCharHtml($value);
    }

    /**
     * Replace the characters used to organise the other attribute values from the
     * passed in string.
     *
     * @param string|array|Phrase $value
     *
     * @return string|array
     */
    private function processCharHtml($value)
    {
        if (is_object($value) && ($value instanceof Phrase)) {
            $value = $value->getText();
        }
        if (!(is_scalar($value) || is_array($value))) {
            return '';
        }
        if (is_scalar($value)) {
            $value = (string)$value;
        }

        return str_replace(
            [';', ',', ':',],
            ['&semi;', '&comma;', '&colon;'],
            $value
        );
    }

    /**
     * Generate a Klevu product sku with parent product.
     *
     * @param string $productSku Magento Sku of the product to generate a Klevu sku for.
     * @param string|null $parentSku Optional Magento Parent Sku of the parent product.
     *
     * @return string
     */
    public function getKlevuProductSku($productSku, $parentSku = '')
    {
        if (!is_scalar($parentSku) || empty($parentSku)) {
            $parentSku = '';
        } else {
            $parentSku .= static::SKU_SEPARATOR;
        }

        return sprintf('%s%s', $parentSku, $productSku);
    }

    /**
     * Get the is active attribute id
     *
     * @return string
     */
    public function getIsActiveAttributeId()
    {
        $entityType = $this->_modelEntityType->loadByCode("catalog_category");
        $entityTypeId = $entityType->getId();
        $attributeCollection = $this->_modelEntityAttribute->getCollection();
        if (!$attributeCollection) {
            return '';
        }
        $attributeCollection->addFieldToFilter("entity_type_id", $entityTypeId);
        $attributeCollection->addFieldToFilter("attribute_code", "is_active");
        $attribute = $attributeCollection->getFirstItem();

        return $attribute->getAttributeId();
    }

    /**
     * Get the attribute id for media gallery
     *
     * @return string
     */
    public function getIsMediaGalleryAttributeId()
    {
        $entityType = $this->_modelEntityType->loadByCode("catalog_product");
        $entityTypeId = $entityType->getId();
        $attributeCollection = $this->_modelEntityAttribute->getCollection();
        if (!$attributeCollection) {
            return '';
        }
        $attributeCollection->addFieldToFilter("entity_type_id", $entityTypeId);
        $attributeCollection->addFieldToFilter("attribute_code", "media_gallery");
        $attribute = $attributeCollection->getFirstItem();

        return $attribute->getAttributeId();
    }

    /**
     * @return string
     */
    public function getIsExcludeAttributeId()
    {
        $entityType = $this->_modelEntityType->loadByCode("catalog_category");
        $entityTypeId = $entityType->getId();
        $attributeCollection = $this->_modelEntityAttribute->getCollection();
        if (!$attributeCollection) {
            return '';
        }
        $attributeCollection->addFieldToFilter("entity_type_id", $entityTypeId);
        $attributeCollection->addFieldToFilter("attribute_code", "is_exclude_cat");
        $attribute = $attributeCollection->getFirstItem();

        return $attribute->getAttributeId();
    }

    /**
     * @param string $ips
     *
     * @return string|null
     */
    public function processIp($ips)
    {
        if (!is_scalar($ips)) {
            return null;
        }
        $iplist = explode(',', (string)$ips);
        if (count($iplist) > 1) {
            foreach ($iplist as $ip) {
                if (filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    return $ip;
                }
            }
        } else {
            return $ips;
        }

        return null;
    }

    /**
     * Get the client ip
     *
     * @return string
     */
    public function getIp()
    {
        // phpcs:disable Magento2.Security.Superglobal.SuperglobalUsageWarning
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
     * Get the currency switcher data
     *
     * @param StoreInterface $store
     *
     * @return string|null
     */
    public function getCurrencyData(StoreInterface $store)
    {
        $baseCurrency = $store->getBaseCurrency();
        $baseCurrencyCode = $baseCurrency->getCode();
        $currentCurrencyCode = $store->getCurrentCurrencyCode();
        if ($baseCurrencyCode === $currentCurrencyCode) {
            return null;
        }
        $availableCurrencies = $store->getAvailableCurrencyCodes();
        $currency = $this->_currencyFactory->create();
        $currencyResource = $currency->getResource();
        $currencyRates = $currencyResource->getCurrencyRates($baseCurrencyCode, array_values($availableCurrencies));
        if (count($availableCurrencies) <= 1) {
            return null;
        }
        foreach ($currencyRates as $key => &$value) {
            $localeCurrency = $this->_localeCurrency->getCurrency($key);

            $symbol = $localeCurrency->getSymbol() ?: $localeCurrency->getShortName();
            $value = sprintf("'%s':'%s:%s'", $key, $value, $symbol);
        }

        return implode(",", $currencyRates);
    }

    /**
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getBaseDomain()
    {
        $store = $this->_storeModelStoreManagerInterface->getStore();
        $baseDomain = $store->getBaseUrl(UrlInterface::URL_TYPE_LINK);
        if (empty($baseDomain)) {
            return null;
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $baseUrlValue = parse_url($baseDomain);

        return $baseUrlValue['host'];
    }

    /**
     * @return string
     */
    public function getJsApiKey()
    {
        return $this->_searchHelperConfig->getJsApiKey();
    }

    /**
     * Return the value of store id from api key.
     *
     * @return StoreInterface|null
     * @throws NoSuchEntityException
     */
    public function storeFromScopeId()
    {
        $configCollection = $this->_configDataCollection;
        $configCollection->addFieldToFilter('value', $this->getJsApiKey());
        $configCollection->load();
        $cofigData = $configCollection->getData();
        if (empty($cofigData)) {
            return null;
        }

        return $this->_storeModelStoreManagerInterface->getStore((int)$cofigData[0]['scope_id']);
    }
}
