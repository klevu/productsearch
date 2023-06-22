<?php

namespace Klevu\Search\Helper;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Logger\Logger\Logger;
use Klevu\Search\Helper\Config as KlevuConfig;
use Klevu\Search\Helper\Data as KlevuSearchHelper;
use Klevu\Search\Helper\Stock as KlevuStockHelper;
use Klevu\Search\Service\Catalog\Product\Stock as KlevuStockService;
use Magento\Bundle\Model\Product\Price as BundlePrice;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data as MagentoCatalogHelper;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\CatalogRule\Model\ResourceModel\RuleFactory;
use Magento\CatalogRule\Observer\RulePricesStorage;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProductType;
use Magento\GroupedProduct\Pricing\Price\FinalPrice as GroupProductFinalPrice;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Config as TaxConfig;
use Psr\Log\LoggerInterface;

class Price extends AbstractHelper
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var MagentoCatalogHelper
     */
    protected $_catalogTaxHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    /**
     * @var KlevuConfig
     */
    protected $_searchHelperConfig;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var RulePricesStorage
     */
    protected $_rulePricesStorage;

    /**
     * @var RuleFactory
     */
    protected $_resourceRuleFactory;

    /**
     * @var KlevuStockHelper
     */
    protected $_stockHelper;

    /**
     * @var KlevuSearchHelper
     */
    protected $_searchHelperData;
    /**
     * @var array<float|null>
     */
    private $klevuSalePrice = [];
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LoggerInterface|null
     */
    private $logger;
    /**
     * @var array
     */
    private $minProductPriceCache = [];

    /**
     * @param TimezoneInterface $localeDate
     * @param Config $searchHelperConfig
     * @param KlevuSearchHelper $searchHelperData
     * @param RulePricesStorage $rulePricesStorage
     * @param MagentoCatalogHelper $catalogTaxHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param RuleFactory $resourceRuleFactory
     * @param Stock $stockHelper
     * @param StoreManagerInterface|null $storeManager
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        TimezoneInterface $localeDate,
        KlevuConfig $searchHelperConfig,
        KlevuSearchHelper $searchHelperData,
        RulePricesStorage $rulePricesStorage,
        MagentoCatalogHelper $catalogTaxHelper,
        PriceCurrencyInterface $priceCurrency,
        RuleFactory $resourceRuleFactory,
        KlevuStockHelper $stockHelper,
        StoreManagerInterface $storeManager = null,
        LoggerInterface $logger = null
    ) {
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_searchHelperData = $searchHelperData;
        $this->_localeDate = $localeDate;
        $this->_rulePricesStorage = $rulePricesStorage;
        $this->_resourceRuleFactory = $resourceRuleFactory;
        $this->_catalogTaxHelper = $catalogTaxHelper;
        $this->priceCurrency = $priceCurrency;
        $this->_stockHelper = $stockHelper;
        $objectManager = ObjectManager::getInstance();
        $this->storeManager = $storeManager
            ?: $objectManager->get(StoreManagerInterface::class);
        $this->logger = $logger
            ?: $objectManager->get(LoggerInterface::class);
    }

    /**
     * @return string
     */
    public function getParentType()
    {
        return ConfigurableType::TYPE_CODE;
    }

    /**
     * Get the secure and unsecure media url
     *
     * @param ProductInterface|null $parent
     * @param ProductInterface $item
     * @param StoreInterface|string|int|null $store
     *
     * @return array<float|null>
     */
    public function getKlevuSalePrice($parent, $item, $store)
    {
        $cacheKey = $this->getCacheKey($item, $parent, $store);
        if (!isset($this->klevuSalePrice[$cacheKey])) {
            $salePrice = null;
            $toPrice = null;
            if ($parent && $parent->getData("type_id") === $this->getParentType()) {
                $item = $parent;
            }
            try {
                $maxPrice = null;
                switch ($item->getTypeId()) {
                    case GroupedProductType::TYPE_CODE:
                        // Would remove this and get the price from the item returned from getGroupProductMinProduct,
                        // but it's a public method and merchant may have extended it.
                        $price = $this->getGroupProductMinPrice($item);
                        // Grouped products don't have a tax class,
                        // switch item to use simple child product with the lowest price instead
                        $item = $this->getGroupProductMinProduct($item);
                        break;
                    case ProductType::TYPE_BUNDLE:
                        list($price, $maxPrice) = $this->getBundleProductPrices(
                            $item,
                            $store
                        );
                        break;
                    default:
                        $priceInfo = $item->getPriceInfo();
                        $finalPrice = $priceInfo->getPrice(FinalPrice::PRICE_CODE);
                        $price = $finalPrice->getValue();
                        break;
                }
                $salePrice = (null !== $price && null !== $item)
                    ? $this->processPrice($price, '', $item, $store)
                    : null;
                $toPrice = (null !== $maxPrice && null !== $item)
                    ? $this->processPrice($maxPrice, '', $item, $store)
                    : null;
            } catch (\InvalidArgumentException $exception) {
                $this->logger->error(
                    sprintf(
                        'Method: %s - Error: %s',
                        __METHOD__,
                        $exception->getMessage()
                    )
                );
            }
            $this->klevuSalePrice[$cacheKey] = [
                'salePrice' => $salePrice,
                'startPrice' => $salePrice,
                'toPrice' => $toPrice,
            ];
        }

        return $this->klevuSalePrice[$cacheKey];
    }

    /**
     * Get the secure and unsecure media url
     *
     * @param ProductInterface|null $parent
     * @param ProductInterface $item
     * @param StoreInterface|string|int|null $store
     *
     * @return array<float|null>
     */
    public function getKlevuPrice($parent, $item, $store)
    {
        $klevuPrice = null;
        if ($parent && $parent->getData("type_id") === $this->getParentType()) {
            $item = $parent;
        }
        try {
            switch ($item->getTypeId()) {
                case GroupedProductType::TYPE_CODE:
                    // Product detail page always shows final price as price,
                    // so we also take final price as original price for grouped product.
                    // Would remove this and get the price from the item returned from getGroupProductMinProduct,
                    // but it's a public method and merchant may have extended it.
                    $price = $this->getGroupProductMinPrice($item);
                    // Grouped products don't have a tax class,
                    // switch item to use simple child product with the lowest price instead
                    $item = $this->getGroupProductMinProduct($item);
                    break;
                case ProductType::TYPE_BUNDLE:
                    // Product detail page always shows final price as price,
                    // so we also take final price as original price for bundle product
                    $price = $this->getBundleProductPrices($item, $store, 'min');
                    break;
                default:
                    $priceInfo = $item->getPriceInfo();
                    $regularPrice = $priceInfo->getPrice(RegularPrice::PRICE_CODE);
                    $price = $regularPrice->getValue();
                    break;
            }
            $klevuPrice = (null !== $price && null !== $item)
                ? $this->processPrice($price, '', $item, $store)
                : null;
        } catch (\InvalidArgumentException $exception) {
            $this->logger->error(
                sprintf(
                    'Method: %s - Error: %s',
                    __METHOD__,
                    $exception->getMessage()
                )
            );
        }

        return [
            'price' => $klevuPrice
        ];
    }

    /**
     * Convert the given price into the current store currency.
     *
     * @param int|float $price
     * @param StoreInterface|string|int|null $store
     *
     * @return float
     */
    public function convertPrice($price, $store)
    {
        return $this->priceCurrency->convert($price, $store);
    }

    /**
     * Process the given product price for using in Product Sync.
     * Applies tax, if needed, and converts to the currency of the current store.
     *
     * @param int|float $price
     * @param string $price_code // no longer used
     * @param ProductInterface $pro
     * @param StoreInterface|string|int|null $store
     *
     * @return float
     * @throws \InvalidArgumentException
     * @deprecared replaced by calculateTaxPrice
     * @see calculateTaxPrice
     */
    public function processPrice($price, $price_code, $pro, $store)
    {
        /** @var ProductModel $pro */
        if (!($pro instanceof ProductInterface)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Product argument is not a valid product model. Expected instance of %s; Received %s',
                    ProductInterface::class,
                    is_object($pro)
                        ? get_class($pro)
                        : gettype($pro) // phpcs:ignore Magento2.Functions.DiscouragedFunction
                )
            );
        }

        return $this->calculateTaxPrice($pro, $price, $store);
    }

    /**
     * Price passed here should be whatever is in the database.
     * Price should not have tax calculation before this point.
     *
     * @param ProductInterface $product
     * @param float $price
     * @param StoreInterface|string|int|null $requestedStore
     *
     * @return float
     */
    public function calculateTaxPrice(ProductInterface $product, $price, $requestedStore = null)
    {
        /** @var ProductModel $product */
        if (!is_numeric($price)) {
            throw new \InvalidArgumentException(
                __(
                    'Price supplied must be numeric, %1 provided: ',
                    is_object($price)
                        ? get_class($price)
                        : gettype($price) // phpcs:ignore Magento2.Functions.DiscouragedFunction
                )
            );
        }
        if ($price < 0) {
            return 0.0;
        }
        try {
            $store = $this->storeManager->getStore($requestedStore);
        } catch (NoSuchEntityException $exception) {
            $this->logger->error(
                sprintf(
                    'Could not load current store. Original price returned. Method: %s - Exception: %s',
                    __METHOD__,
                    $exception->getMessage()
                )
            );

            return $price;
        }
        $returnPriceWithTax = $this->isTaxIncludedInReturnPrice($store);
        $dbPriceIncludesTax = $this->_searchHelperConfig->getPriceIncludesTax($store);

        $taxPrice = $this->_catalogTaxHelper->getTaxPrice(
            $product,
            $price,
            $returnPriceWithTax,
            null,
            null,
            null,
            $store,
            $dbPriceIncludesTax
        );

        return $this->priceCurrency->round($taxPrice);
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return bool
     */
    private function isTaxIncludedInReturnPrice($store)
    {
        switch ((int)$this->_searchHelperConfig->getPriceDisplaySettings($store)) {
            case TaxConfig::DISPLAY_TYPE_BOTH:
                $return = $this->_searchHelperConfig->isTaxCalRequired($store); // method name is awful
                break;
            case TaxConfig::DISPLAY_TYPE_EXCLUDING_TAX:
                $return = false;
                break;
            case TaxConfig::DISPLAY_TYPE_INCLUDING_TAX:
                $return = true;
                break;
            default:
                $return = false;
        }

        return $return;
    }

    /**
     * Apply catalog price rules to product on frontend
     *
     * @param array $item
     * @param int $gId
     * @param int $pId
     * @param StoreInterface|string|int|null $store
     *
     * @return float|null
     */
    public function calculateFinalPriceFront($item, $gId, $pId, $store)
    {
        $date = $this->_localeDate->scopeDate($store->getId());
        $wId = $store->getWebsiteId();
        $key = $date->format('Y-m-d H:i:s') . "|{$wId}|{$gId}|{$pId}";

        $rulePrice = null;
        if (!$this->_rulePricesStorage->hasRulePrice($key)) {
            $rulePriceModel = $this->_resourceRuleFactory->create();
            $rulePrice = $rulePriceModel->getRulePrice($date, $wId, $gId, $pId);
            $this->_rulePricesStorage->setRulePrice($key, $rulePrice);
        }

        return $rulePrice;
    }

    /**
     * Get the list of prices based on customer group
     *
     * @param ProductInterface $proData
     * @param StoreInterface $store
     *
     * @return array
     * @deprecated not called anywhere, method includes none existent class property _customerModelGroup
     * @see \Klevu\Search\Model\Product\Product::getGroupPricesData
     */
    public function getGroupPrices($proData, $store)
    {
        $groupPrices = $proData->getData('tier_price');
        if (null === $groupPrices) {
            $productResource = $proData->getResource();
            $attribute = $productResource->getAttribute('tier_price');
            if ($attribute) {
                $attributeBackend = $attribute->getBackend();
                $attributeBackend->afterLoad($proData);
                $groupPrices = $proData->getData('tier_price');
            }
        }

        $priceGroupData = [];
        if (!empty($groupPrices) && is_array($groupPrices)) {
            foreach ($groupPrices as $groupPrice) {
                if ($store->getWebsiteId() == $groupPrice['website_id'] || $groupPrice['website_id'] == 0) {
                    if ($groupPrice['price_qty'] == 1) {
                        $groupPriceKey = $groupPrice['cust_group'];
                        $customerGroup = $this->_customerModelGroup->load($groupPrice['cust_group']);
                        $groupname = $customerGroup->getCustomerGroupCode();
                        $result['label'] = $groupname;
                        $result['values'] = $groupPrice['website_price'];
                        $priceGroupData[$groupPriceKey] = $result;
                    }
                }
            }
        }

        return $priceGroupData;
    }

    /**
     * @param ProductInterface $product
     * @param StoreInterface|string|int|null $store
     *
     * @return void
     * @deprecated Not required, only show min price in PLP & SRLP
     * @see getGroupProductMinPrice or getGroupProductMinProduct
     */
    public function getGroupProductOriginalPrice($product, $store)
    {
        try {
            $groupProductIds = $product->getTypeInstance()->getChildrenIds($product->getId());
            $groupPrices = [];
            foreach ($groupProductIds as $ids) {
                if (!is_array($ids)) {
                    continue;
                }
                foreach ($ids as $id) {
                    if ((int)$id === (int)$product->getId()) {
                        continue;
                    }

                    $groupProduct = ObjectManager::getInstance()->create(ProductModel::class)->load($id);
                    if ($groupProduct->getStatus() != 1) {
                        continue;
                    }

                    if (!$this->_searchHelperConfig->displayOutofstock()) {
                        $stockStatus = $this->_stockHelper->getKlevuStockStatus(
                            null,
                            $groupProduct,
                            $store->getWebsiteId()
                        );
                        if ($stockStatus === KlevuStockService::KLEVU_IN_STOCK) {
                            $gPrice = $this->getKlevuPrice(null, $groupProduct, $store);
                            $groupPrices[] = $gPrice['price'];
                        }
                    } else {
                        $gPrice = $this->getKlevuPrice(null, $groupProduct, $store);
                        $groupPrices[] = $gPrice['price'];
                    }
                }
            }
            asort($groupPrices);
            $product->setPrice(array_shift($groupPrices));
        } catch (\Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_WARN,
                sprintf("Unable to get original group price for product id %s", $product->getId())
            );
        }
    }

    /**
     * Get Minimum price for group product.
     *
     * @param ProductInterface $product
     * @param StoreInterface $store // no longer required
     *
     * @return float
     * @throws \InvalidArgumentException
     */
    public function getGroupProductMinPrice($product, $store = null)
    {
        if (!($product instanceof ProductInterface)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Product argument is not a valid product model. Expected instance of %s; Received %s',
                    ProductInterface::class,
                    is_object($product)
                        ? get_class($product)
                        : gettype($product) // phpcs:ignore Magento2.Functions.DiscouragedFunction
                )
            );
        }
        if ($product->getTypeId() !== GroupedProductType::TYPE_CODE) {
            throw new \InvalidArgumentException(
                __(
                    'Incorrect product type provided: Expected %1: %2 Provided',
                    GroupedProductType::TYPE_CODE,
                    $product->getTypeId()
                )
            );
        }
        if (!isset($this->minProductPriceCache[$product->getId()])) {
            $priceInfo = $product->getPriceInfo();
            $finalPrice = $priceInfo->getPrice(FinalPrice::PRICE_CODE);
            $this->minProductPriceCache[$product->getId()] = max(0, $finalPrice->getValue());
        }

        return $this->minProductPriceCache[$product->getId()];
    }

    /**
     * @param ProductInterface $product
     *
     * @return ProductInterface
     */
    public function getGroupProductMinProduct(ProductInterface $product)
    {
        if ($product->getTypeId() !== GroupedProductType::TYPE_CODE) {
            throw new \InvalidArgumentException(
                __(
                    'Incorrect product type provided: Expected %1: %2 Provided',
                    GroupedProductType::TYPE_CODE,
                    $product->getTypeId()
                )
            );
        }
        $priceInfo = $product->getPriceInfo();
        /** @var GroupProductFinalPrice $price */
        $price = $priceInfo->getPrice(FinalPrice::PRICE_CODE);

        return $price->getMinProduct();
    }

    /**
     * @param ProductInterface $item
     * @param StoreInterface $store
     * @param string|null $which // null|min|max - null returns array containing min and max
     *
     * @return float|array
     * @throws \InvalidArgumentException
     */
    public function getBundleProductPrices($item, $store, $which = null)
    {
        /** @var ProductModel $item */
        if (!($item instanceof ProductInterface)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Item argument is not a valid product model. Expected instance of %s; Received %s',
                    ProductInterface::class,
                    is_object($item)
                        ? get_class($item)
                        : gettype($item) // phpcs:ignore Magento2.Functions.DiscouragedFunction
                )
            );
        }
        if ($item->getTypeId() !== ProductType::TYPE_BUNDLE) {
            throw new \InvalidArgumentException(
                __(
                    'Provided Product must a Bundle %1 provided',
                    $item->getTypeId()
                )
            );
        }
        /** @var BundlePrice $priceModel */
        $priceModel = $item->getPriceModel();
        if (!($priceModel instanceof BundlePrice)) {
            throw new \InvalidArgumentException(
                __(
                    'Price Model is not an instance of %1:, %2 provided',
                    BundlePrice::class,
                    is_object($priceModel)
                        ? get_class($priceModel)
                        : gettype($priceModel) // phpcs:ignore Magento2.Functions.DiscouragedFunction
                )
            );
        }
        // return whatever is in the database, we process tax later in self::processPrice()
        $returnPriceWithTax = $this->_searchHelperConfig->getPriceIncludesTax($store);

        return $priceModel->getTotalPrices($item, $which, $returnPriceWithTax, false);
    }

    /**
     * @param float|int $price
     *
     * @return float
     */
    public function roundPrice($price)
    {
        return $this->priceCurrency->round($price);
    }

    /**
     * @param ProductInterface $product
     * @param ProductInterface|null $parent
     * @param StoreInterface|string|int|null $store
     *
     * @return string
     */
    private function getCacheKey(ProductInterface $product, $parent, $store)
    {
        $parentId = $parent ? $parent->getId() : '0';
        $storeId = ($store instanceof StoreInterface) ? $store->getId() : $store;

        return $product->getId() . '::' . $parentId . '::' . $storeId;
    }
}
