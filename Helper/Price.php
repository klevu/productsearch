<?php

namespace Klevu\Search\Helper;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Config as KlevuConfig;
use Klevu\Search\Helper\Data as KlevuSearchHelper;
use Klevu\Search\Helper\Stock as KlevuStockHelper;
use Klevu\Search\Service\Catalog\Product\Stock as KlevuStockService;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data as MagentoCatalogHelper;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\CatalogRule\Model\ResourceModel\RuleFactory;
use Magento\CatalogRule\Observer\RulePricesStorage;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\GroupedProduct\Pricing\Price\FinalPrice as GroupProductFinalPrice;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Config as TaxConfig;

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
     * @param TimezoneInterface $localeDate
     * @param Config $searchHelperConfig
     * @param Data $searchHelperData
     * @param RulePricesStorage $rulePricesStorage
     * @param MagentoCatalogHelper $catalogTaxHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param RuleFactory $resourceRuleFactory
     * @param Stock $stockHelper
     */
    public function __construct(
        TimezoneInterface $localeDate,
        KlevuConfig $searchHelperConfig,
        KlevuSearchHelper $searchHelperData,
        RulePricesStorage $rulePricesStorage,
        MagentoCatalogHelper $catalogTaxHelper,
        PriceCurrencyInterface $priceCurrency,
        RuleFactory $resourceRuleFactory,
        KlevuStockHelper $stockHelper
    ) {
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_searchHelperData = $searchHelperData;
        $this->_localeDate = $localeDate;
        $this->_rulePricesStorage = $rulePricesStorage;
        $this->_resourceRuleFactory = $resourceRuleFactory;
        $this->_catalogTaxHelper = $catalogTaxHelper;
        $this->priceCurrency = $priceCurrency;
        $this->_stockHelper = $stockHelper;
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
     * @param array $parent
     * @param array $item
     * @param StoreInterface|string|int|null $store
     * @return array
     */
    public function getKlevuSalePrice($parent, $item, $store)
    {
        // Default to 0 if price can't be determined
        $productPrice['salePrice'] = 0;
        if ($parent && $parent->getData("type_id") == $this->getParentType()) {
            $final_price = $parent->getPriceInfo()
                ->getPrice('final_price')
                ->getAmount()
                ->getValue();
            $processed_final_price = $this->processPrice($final_price, 'final_price', $parent, $store);
            $productPrice['salePrice'] = $processed_final_price;
            $productPrice['startPrice'] = $processed_final_price;
        } else {
            $final_price = null;
            $priceInfo = $item->getPriceInfo();
            $price = $priceInfo->getPrice('final_price');
            if ($price && (!($price instanceof GroupProductFinalPrice) || $price->getMinProduct())) {
                $amount = $price->getAmount();
                if ($amount) {
                    $final_price = $amount->getValue();
                }
            }
            $processed_final_price = $this->processPrice($final_price, 'final_price', $item, $store);
            $productPrice['salePrice'] = $processed_final_price;
            $productPrice['startPrice'] = $processed_final_price;
            if ($item->getData('type_id') == "grouped") {
                $gprice = $this->getGroupProductMinPrice($item, $store);
                $productPrice['startPrice'] = $gprice;
                $productPrice["salePrice"] = $gprice;
            } elseif ($item->getData('type_id') == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                list($minimalPrice, $maximalPrice) = $this->getBundleProductPrices($item, $store);

                $productPrice["salePrice"] = $minimalPrice;
                $productPrice['startPrice'] = $minimalPrice;
                $productPrice['toPrice'] = $maximalPrice;
            }
        }
        return $productPrice;
    }

    /**
     * Get the secure and unsecure media url
     *
     * @param array $parent
     * @param array $item
     * @param StoreInterface|string|int|null $store
     * @return array
     */
    public function getKlevuPrice($parent, $item, $store)
    {
        /* getPrice */
        if ($parent && $parent->getData("type_id") == $this->getParentType()) {
            $price = $parent->getPriceInfo()
                ->getPrice('regular_price')
                ->getAmount()
                ->getValue();
            $processed_price = $this->processPrice($price, 'regular_price', $parent, $store);
            $productPrice['price'] = $processed_price;
        } else {
            if ($item->getData('type_id') == "grouped") {
                // Get the group product original price
                $sPrice = $this->getGroupProductMinPrice($item, $store);
                //$sPrice = $item->getPrice();
                $productPrice["price"] = $sPrice;
            } elseif ($item->getData('type_id') == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                // Product detail page always shows final price as price so we also take
                // final price as original price only for bundle product
                list($minimalPrice, $maximalPrice) = $this->getBundleProductPrices($item, $store);

                $productPrice["price"] = $minimalPrice;
            } else {
                $price = $item->getPriceInfo()
                    ->getPrice('regular_price')
                    ->getAmount()
                    ->getValue();
                $processed_price = $this->processPrice($price, 'regular_price', $item, $store);
                $productPrice['price'] = $processed_price;
            }

        }
        return $productPrice;
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
     * @param string $price_code
     * @param ProductInterface $pro
     * @param StoreInterface|string|int|null $store
     * @return int|float
     */
    public function processPrice($price, $price_code, $pro, $store)
    {
        if ($price < 0) {
            $price = 0;
        }

        if ($this->_searchHelperConfig->getPriceIncludesTax($store) == 1) {
            if ($this->_searchHelperConfig->getPriceDisplaySettings($store) == TaxConfig::DISPLAY_TYPE_BOTH) {
                if ($this->_searchHelperConfig->isTaxCalRequired($store)) {
                    $price = $this->_catalogTaxHelper->getTaxPrice($pro, $price, true);
                }
            } else {
                $price = $this->_catalogTaxHelper->getTaxPrice($pro, $price);
            }
            return $price;
        } else {
            if ($this->_searchHelperConfig->getPriceDisplaySettings($store) == TaxConfig::DISPLAY_TYPE_BOTH) {
                if (!$this->_searchHelperConfig->isTaxCalRequired($store)) {
                    $price = $pro->getPriceInfo()
                        ->getPrice($price_code)
                        ->getAmount()->getValue(\Magento\Tax\Pricing\Adjustment::ADJUSTMENT_CODE);
                }
            }
        }

        return $this->priceCurrency->round($price);
    }

    /**
     * Apply catalog price rules to product on frontend
     *
     * @param array $item
     * @param int $gId
     * @param int $pId
     * @param StoreInterface|string|int|null $store
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
     * @return array
     *
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
     * Get Original price for group product.
     *
     * @param object $product .
     *
     * @return
     */
    /**
     * @param ProductInterface $product
     * @param StoreInterface|string|int|null $store
     * @return void
     */
    public function getGroupProductOriginalPrice($product, $store)
    {
        try {
            $groupProductIds = $product->getTypeInstance()->getChildrenIds($product->getId());
            $groupPrices = [];
            foreach ($groupProductIds as $ids) {
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
     * Get Min price for group product.
     *
     * @param ProductInterface $product
     * @param StoreInterface $store
     * @return float|int|null
     */
    public function getGroupProductMinPrice($product, $store)
    {
        $typeInstance = $product->getTypeInstance();
        $groupProductIds = $typeInstance->getChildrenIds($product->getId());
        $groupPrices = [];

        foreach ($groupProductIds as $ids) {
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
                        $gPrice = $this->getKlevuSalePrice(null, $groupProduct, $store);
                        $groupPrices[] = $gPrice['salePrice'];
                    }
                } else {
                    $gPrice = $this->getKlevuSalePrice(null, $groupProduct, $store);
                    $groupPrices[] = $gPrice['salePrice'];
                }
            }
        }

        asort($groupPrices);
        return array_shift($groupPrices);
    }

    /**
     * @param ProductInterface $item
     * @param StoreInterface $store
     * @return float|array
     */
    public function getBundleProductPrices($item, $store)
    {
        $priceDisplaySetting = $this->_searchHelperConfig->getPriceDisplaySettings($store);
        if ($this->_searchHelperConfig->getPriceIncludesTax($store)) {
            //exluding
            if ($priceDisplaySetting == TaxConfig::DISPLAY_TYPE_EXCLUDING_TAX) {
                return $item->getPriceModel()->getTotalPrices($item, null, false, false);
            } elseif ($priceDisplaySetting == TaxConfig::DISPLAY_TYPE_INCLUDING_TAX) {
                return $item->getPriceModel()->getTotalPrices($item, null, true, false);
            } elseif ($priceDisplaySetting == TaxConfig::DISPLAY_TYPE_BOTH) {
                if (!$this->_searchHelperConfig->isTaxCalRequired($store)) {
                    return $item->getPriceModel()->getTotalPrices($item, null, false, false);
                } else {
                    return $item->getPriceModel()->getTotalPrices($item, null, true, false);
                }
            }

        } else {
            //including
            if ($priceDisplaySetting == TaxConfig::DISPLAY_TYPE_INCLUDING_TAX) {
                return $item->getPriceModel()->getTotalPrices($item, null, true, false);
            } elseif ($priceDisplaySetting == TaxConfig::DISPLAY_TYPE_EXCLUDING_TAX) {
                return $item->getPriceModel()->getTotalPrices($item, null, false, false);
            } elseif ($priceDisplaySetting == TaxConfig::DISPLAY_TYPE_BOTH) {
                if (!$this->_searchHelperConfig->isTaxCalRequired($store)) {
                    return $item->getPriceModel()->getTotalPrices($item, null, false, false);
                } else {
                    return $item->getPriceModel()->getTotalPrices($item, null, true, false);
                }
            }
        }
    }

    /**
     * @param float|int $price
     * @return float
     */
    public function roundPrice($price)
    {
        return $this->priceCurrency->round($price);
    }
}
