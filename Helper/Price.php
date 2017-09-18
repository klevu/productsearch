<?php
namespace Klevu\Search\Helper;
use \Magento\CatalogRule\Model\Rule;
use Magento\Framework\Pricing\PriceCurrencyInterface;
class Price extends \Magento\Framework\App\Helper\AbstractHelper
{
	
	/**
     * Price currency
     *
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;
	
	/**
     * @var \Klevu\Search\Helper\Taxdata
     */
    protected $_catalogTaxHelper;
	
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;
	
	/**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;
	
	/**
     * @var \Magento\CatalogRule\Observer\RulePricesStorage
     */
    protected $_rulePricesStorage;
	
	/**
     * @var \Magento\CatalogRule\Model\ResourceModel\RuleFactory
     */
    protected $_resourceRuleFactory;

    public function __construct(
	\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
	\Klevu\Search\Helper\Config $searchHelperConfig, 
	\Klevu\Search\Helper\Data $searchHelperData,
	\Magento\CatalogRule\Observer\RulePricesStorage $rulePricesStorage,
	\Klevu\Search\Helper\Taxdata $catalogTaxHelper,
	PriceCurrencyInterface $priceCurrency,
	\Magento\CatalogRule\Model\ResourceModel\RuleFactory $resourceRuleFactory)
    {
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_searchHelperData = $searchHelperData;
		$this->_localeDate = $localeDate;
		$this->_rulePricesStorage = $rulePricesStorage;
		$this->_resourceRuleFactory = $resourceRuleFactory;
		$this->_catalogTaxHelper = $catalogTaxHelper;
		 $this->priceCurrency = $priceCurrency;
    }

    /**
     * Get the secure and unsecure media url
     *
     * @return string
     */

    public function getKlevuSalePrice($parent, $item, $store)
    {
		$price_code = "final_price";
        // Default to 0 if price can't be determined
        $productPrice['salePrice'] = 0;
        if ($parent && $parent->getData("type_id") == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
        {
            // Calculate configurable product price based on option values
            $ruleprice = $this->calculateFinalPriceFront($parent, \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,$parent->getId() , $store);
            if (!empty($ruleprice))
            {
                $fprice = min($ruleprice, $parent->getPriceInfo()
                    ->getPrice('final_price')
                    ->getAmount()
                    ->getValue(\Magento\Tax\Pricing\Adjustment::ADJUSTMENT_CODE));
            }
            else
            {
                $fprice = $parent->getPriceInfo()
                    ->getPrice('final_price')
                    ->getAmount()
                    ->getValue(\Magento\Tax\Pricing\Adjustment::ADJUSTMENT_CODE);
            }
            
            // show low price for config products
            $productPrice['startPrice'] = $this->processPrice($fprice, $price_code, $parent,$store);

            // also send sale price for sorting and filters for klevu
            $productPrice['salePrice'] = $this->processPrice($fprice, $price_code, $parent, $store);
        }
        else
        {
            // Use price index prices to set the product price and start/end prices if available
            // Falling back to product price attribute if not
            if ($item)
            {
                $ruleprice = $this->calculateFinalPriceFront($item, \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID, $item->getId() , $store);
                if ($item->getData('type_id') == "grouped")
                {
                    $this->getGroupProductMinPrice($item, $store);
                    if (!empty($ruleprice))
                    {
                        $sPrice = min($ruleprice, $item->getFinalPrice());
                    }
                    else
                    {
                        $sPrice = $item->getFinalPrice();
                    }
                    $productPrice['startPrice'] = $sPrice;
                    $productPrice["salePrice"] = $sPrice;
                }
                elseif ($item->getData('type_id') == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE)
                {
                    list($minimalPrice, $maximalPrice) = $this->getBundleProductPrices($item, $store);

                    $productPrice["salePrice"] = $minimalPrice;
                    $productPrice['startPrice'] = $minimalPrice;
                    $productPrice['toPrice'] = $maximalPrice;
                }
                else
                {
                    // Always use minimum price as the sale price as it's the most accurate
                    if (!empty($ruleprice))
                    {
                        $sPrice = min($ruleprice, $item->getPriceInfo()
                            ->getPrice('final_price')
                            ->getAmount()
                            ->getValue(\Magento\Tax\Pricing\Adjustment::ADJUSTMENT_CODE));
                    }
                    else
                    {
                        $sPrice = $item->getPriceInfo()
                            ->getPrice('final_price')
                            ->getAmount()
                            ->getValue(\Magento\Tax\Pricing\Adjustment::ADJUSTMENT_CODE);
                    }
 
                    $productPrice['salePrice'] = $this->processPrice($sPrice, $price_code, $item,$store);
					$productPrice['startPrice'] = $this->processPrice($sPrice, $price_code, $item,$store);
                }
            }
            else
            {
                if ($item->getData("price") !== null)
                {
					$price = $item->getPriceInfo()
                        ->getPrice('regular_price')
                        ->getAmount()
                        ->getValue(\Magento\Tax\Pricing\Adjustment::ADJUSTMENT_CODE);
                    $productPrice["salePrice"] = $this->processPrice($price ,$price_code, $item,$store);

                }
            }
        }
		
		return $productPrice;
    }

    /**
     * Get the secure and unsecure media url
     *
     * @return string
     */

    public function getKlevuPrice($parent, $item, $store)
    {
		$price_code = "regular_price";
        // Default to 0 if price can't be determined
        $product['price'] = 0;
        if ($parent && $parent->getData("type_id") == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
        {
			// Becuase of MAGENTO BUG we always take final price as price for configurable product
			$price_code = "final_price";
            // Calculate configurable product price based on option values
            $ruleprice = $this->calculateFinalPriceFront($parent, \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,$parent->getId() , $store);
            if (!empty($ruleprice))
            {
                $price = min($ruleprice, $parent->getPriceInfo()
                    ->getPrice('final_price')
                    ->getAmount()
                    ->getValue(\Magento\Tax\Pricing\Adjustment::ADJUSTMENT_CODE));
            }
            else
            {
                $price = $parent->getPriceInfo()
                    ->getPrice('final_price')
                    ->getAmount()
                    ->getValue(\Magento\Tax\Pricing\Adjustment::ADJUSTMENT_CODE);
            }
           
            // also send sale price for sorting and filters for klevu
            $productPrice['price'] = $this->processPrice($price, $price_code, $parent,$store);
        }
        else
        {
            // Use price index prices to set the product price and start/end prices if available
            // Falling back to product price attribute if not
            if ($item)
            {
                if ($item->getData('type_id') == "grouped")
                {
                    // Get the group product original price
                    $this->getGroupProductOriginalPrice($item, $store);
                    $sPrice = $item->getPrice();
                    $productPrice["price"] = $sPrice;
                }
                elseif ($item->getData('type_id') == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE)
                {
                    // product detail page always shows final price as price so we also taken final price as original price only for bundle product
                    list($minimalPrice, $maximalPrice) = $this->getBundleProductPrices($item, $store);

                    $productPrice["price"] = $minimalPrice;
                }
                else
                {
                    // Always use minimum price as the sale price as it's the most accurate
					$price = $item->getPriceInfo()
                        ->getPrice('regular_price')
                        ->getAmount()
                        ->getValue(\Magento\Tax\Pricing\Adjustment::ADJUSTMENT_CODE);
                    $productPrice['price'] = $this->processPrice($price, $price_code, $item,$store);
                }
            }
            else
            {
                if ($item->getData("price") !== null)
                {
                    $productPrice["price"] = $this->processPrice($item->getPriceInfo()
                        ->getPrice('regular_price')
                        ->getAmount()
                        ->getValue(\Magento\Tax\Pricing\Adjustment::ADJUSTMENT_CODE) , $price_code, $item, $store);
                }
            }
        }
		return $productPrice;
    }

    /**
     * Convert the given price into the current store currency.
     *
     * @param $price
     *
     * @return float
     */
    protected function convertPrice($price, $store)
    {
        $convertPrice = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Pricing\PriceCurrencyInterface');
        return $convertPrice->convert($price, $store);
    }
    /**
     * Process the given product price for using in Product Sync.
     * Applies tax, if needed, and converts to the currency of the current store.
     *
     * @param $price
     * @param $tax_class_id
     * @param product object
     *
     * @return float
     */
    protected function processPrice($price, $price_code, $pro, $store)
    {
        if ($price < 0) {
            $price = 0;
        } else {
            $price = $price;
        }
		
		if ($this->_searchHelperConfig->getPriceIncludesTax($store)) {
			$taxPrice = $this->_catalogTaxHelper->getTaxPrice($pro,$price,$store->getId());
			if($this->_searchHelperConfig->isTaxEnabled($store->getId())) {
				$admin_price = $pro->getPriceInfo()
							->getPrice($price_code)
							->getAmount()
							->getValue();
				if($admin_price > $taxPrice['include_tax']){
					$f_price = round($admin_price,2);
				} else if($admin_price < $taxPrice['include_tax']) {
					$f_price = floor($admin_price * 100)/100;
				} else {
					$f_price = round($taxPrice['include_tax'],2);
				}
				return $f_price;
			} else {
				$taxPrice = $this->_catalogTaxHelper->getTaxPrice($pro,$price,$store->getId());
				return round($taxPrice['exclude_tax'],2);
			}
		} else {
			
			if($this->_searchHelperConfig->isTaxEnabled($store->getId())) {
				$taxPrice = $this->_catalogTaxHelper->getTaxPrice($pro,$price,$store->getId());
				$f_price = round($taxPrice['include_tax'],2);
				return $f_price;
			} else {
				return round($price,2);
			}
		}
    }

    /**
     * Apply catalog price rules to product on frontend
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function calculateFinalPriceFront($item, $gId, $pId, $store)
    {
        $date = $this->_localeDate
            ->scopeDate($store->getId());
        $wId = $store->getWebsiteId();
        $key = "{$date->format('Y-m-d H:i:s') }|{$wId}|{$gId}|{$pId}";
        if (!$this
            ->_rulePricesStorage
            ->hasRulePrice($key))
        {
            $rulePrice = $this
                ->_resourceRuleFactory
                ->create()
                ->getRulePrice($date, $wId, $gId, $pId);
            $this
                ->_rulePricesStorage
                ->setRulePrice($key, $rulePrice);
            return $rulePrice;
        }
        return;
    }

    /**
     * Get the list of prices based on customer group
     *
     * @param object $item OR $parent
     *
     * @return array
     */
    protected function getGroupPrices($proData, $store)
    {
        $groupPrices = $proData->getData('tier_price');
        if (is_null($groupPrices))
        {
            $attribute = $proData->getResource()
                ->getAttribute('tier_price');
            if ($attribute)
            {
                $attribute->getBackend()
                    ->afterLoad($proData);
                $groupPrices = $proData->getData('tier_price');
            }
        }

        if (!empty($groupPrices) && is_array($groupPrices))
        {
            $priceGroupData = [];
            foreach ($groupPrices as $groupPrice)
            {
                if ($store->getWebsiteId() == $groupPrice['website_id'] || $groupPrice['website_id'] == 0)
                {
                    if ($groupPrice['price_qty'] == 1)
                    {
                        $groupPriceKey = $groupPrice['cust_group'];
                        $groupname = $this
                            ->_customerModelGroup
                            ->load($groupPrice['cust_group'])->getCustomerGroupCode();
                        $result['label'] = $groupname;
                        $result['values'] = $groupPrice['website_price'];
                        $priceGroupData[$groupPriceKey] = $result;
                    }
                }
            }
            return $priceGroupData;
        }
    }
	
	
	    /**
     Get Original price for group product.
     *
     * @param object $product.
     *
     * @return
     */
    public function getGroupProductOriginalPrice($product, $store)
    {
        try {
            $groupProductIds = $product->getTypeInstance()->getChildrenIds($product->getId());
            $config = $this->_searchHelperConfig;
            $groupPrices = [];
            foreach ($groupProductIds as $ids) {
                foreach ($ids as $id) {
                    $groupProduct = \Magento\Framework\App\ObjectManager::getInstance()->create('\Magento\Catalog\Model\Product')->load($id);
                    if ($config->isTaxEnabled($store->getId())) {
                        $groupPrices[] = $this->getKlevuPrice(null,$groupProduct,$store);
                    } else {
                        $groupPrices[] = $groupProduct->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                    }
                }
            }
            asort($groupPrices);
            $product->setPrice(array_shift($groupPrices));
        } catch (\Exception $e) {
            $this->_searchHelperData->log(\Zend\Log\Logger::WARN, sprintf("Unable to get original group price for product id %s", $product->getId()));
        }
    }
    
    /**
     Get Min price for group product.
     *
     * @param object $product.
     *
     * @return
     */
    public function getGroupProductMinPrice($product, $store)
    {
        $groupProductIds = $product->getTypeInstance()->getChildrenIds($product->getId());
        $config = $this->_searchHelperConfig;
        $groupPrices = [];
        foreach ($groupProductIds as $ids) {
            foreach ($ids as $id) {
                $groupProduct = \Magento\Framework\App\ObjectManager::getInstance()->create('\Magento\Catalog\Model\Product')->load($id);
                if ($config->isTaxEnabled($store->getId()) || $this->_searchHelperConfig->getPriceIncludesTax($store)) {
                     $groupPrices[] = $this->getKlevuSalePrice(null,$groupProduct,$store);
                } else {
                    $groupPrices[] = $groupProduct->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
                }
            }
        }
        asort($groupPrices);
        $product->setFinalPrice(array_shift($groupPrices));
    }
    
    /**
     * Get Min price for group product.
     *
     * @param object $product.
     *
     * @return
     */
    public function getBundleProductPrices($item, $store)
    {
        $config = $this->_searchHelperConfig;
        if ($config->isTaxEnabled($store->getId())) {
                return $item->getPriceModel()->getTotalPrices($item, null, true, false);
        } else {
                return $item->getPriceModel()->getTotalPrices($item, null, false, false);
        }
    }
    
}