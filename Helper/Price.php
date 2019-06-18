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
     * @var \Magento\Catalog\Helper\Data
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
	
	/**
     * @var \Klevu\Search\Helper\Stock
     */
	protected $_stockHelper;

    public function __construct(
	\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
	\Klevu\Search\Helper\Config $searchHelperConfig, 
	\Klevu\Search\Helper\Data $searchHelperData,
	\Magento\CatalogRule\Observer\RulePricesStorage $rulePricesStorage,
	\Magento\Catalog\Helper\Data $catalogTaxHelper,
	PriceCurrencyInterface $priceCurrency,
	\Magento\CatalogRule\Model\ResourceModel\RuleFactory $resourceRuleFactory,
	\Klevu\Search\Helper\Stock $stockHelper)
    {
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_searchHelperData = $searchHelperData;
		$this->_localeDate = $localeDate;
		$this->_rulePricesStorage = $rulePricesStorage;
		$this->_resourceRuleFactory = $resourceRuleFactory;
		$this->_catalogTaxHelper = $catalogTaxHelper;
		$this->priceCurrency = $priceCurrency;
		$this->_stockHelper = $stockHelper;
    }

    public function getParentType(){
        return \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
    }
    /**
     * Get the secure and unsecure media url
     *
     * @return string
     */

    public function getKlevuSalePrice($parent, $item, $store)
    {
		// Default to 0 if price can't be determined
        $productPrice['salePrice'] = 0;
		if($parent && $parent->getData("type_id") == $this->getParentType()) {
			$final_price = $parent->getPriceInfo()
                    ->getPrice('final_price')
                    ->getAmount()
                    ->getValue();
			$processed_final_price = $this->processPrice($final_price,'final_price',$parent,$store);
			$productPrice['salePrice'] = $processed_final_price;
			$productPrice['startPrice'] = $processed_final_price;
		} else {
			$final_price = $item->getPriceInfo()
                    ->getPrice('final_price')
                    ->getAmount()
                    ->getValue();
			$processed_final_price = $this->processPrice($final_price,'final_price',$item,$store);
			$productPrice['salePrice'] = $processed_final_price;
			$productPrice['startPrice'] = $processed_final_price;
			if ($item->getData('type_id') == "grouped")
                {
                    $gprice = $this->getGroupProductMinPrice($item, $store);
                    $productPrice['startPrice'] = $gprice;
                    $productPrice["salePrice"] = $gprice;
                }
            elseif ($item->getData('type_id') == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE)
                {
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
     * @return string
     */

    public function getKlevuPrice($parent, $item, $store)
    {
		/* getPrice */
		if($parent && $parent->getData("type_id") == $this->getParentType()) {
			$price = $parent->getPriceInfo()
                    ->getPrice('regular_price')
                    ->getAmount()
                    ->getValue();
			$processed_price = $this->processPrice($price,'regular_price',$parent,$store);
			$productPrice['price'] = $processed_price;
		} else {
			if ($item->getData('type_id') == "grouped")
                {
                    // Get the group product original price
                    $sPrice = $this->getGroupProductMinPrice($item, $store);
                    //$sPrice = $item->getPrice();
                    $productPrice["price"] = $sPrice;
                }
            elseif ($item->getData('type_id') == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE)
                {
                    // product detail page always shows final price as price so we also taken final price as original price only for bundle product
                    list($minimalPrice, $maximalPrice) = $this->getBundleProductPrices($item, $store);

                    $productPrice["price"] = $minimalPrice;
            } else {
				$price = $item->getPriceInfo()
                    ->getPrice('regular_price')
                    ->getAmount()
                    ->getValue();
				$processed_price = $this->processPrice($price,'regular_price',$item,$store);
				$productPrice['price'] = $processed_price;
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
        return $this->priceCurrency->convert($price, $store);
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
    public function processPrice($price, $price_code, $pro, $store)
    {
        if ($price < 0) {
            $price = 0;
        }
		
		if($this->_searchHelperConfig->getPriceIncludesTax($store) == 1) {
			if($this->_searchHelperConfig->getPriceDisplaySettings($store) == \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH){
				if($this->_searchHelperConfig->isTaxCalRequired($store)){
					$price = $this->_catalogTaxHelper->getTaxPrice($pro, $price,true);
				}
			} else {
				$price = $this->_catalogTaxHelper->getTaxPrice($pro, $price);
		    }
		    return $price;
		} else {
			if($this->_searchHelperConfig->getPriceDisplaySettings($store) == \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH){
				if(!$this->_searchHelperConfig->isTaxCalRequired($store)){
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
    public function getGroupPrices($proData, $store)
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
					
					if($groupProduct->getStatus() == 1) {
						if(!$this->_searchHelperConfig->displayOutofstock()){
							if($this->_stockHelper->getKlevuStockStatus(null,$groupProduct) == "yes") {
								$gPrice = $this->getKlevuPrice(null,$groupProduct,$store);
								$groupPrices[] = $gPrice['price'];
							}
						} else {
							$gPrice = $this->getKlevuPrice(null,$groupProduct,$store);
							$groupPrices[] = $gPrice['price'];
						}
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
				if($groupProduct->getStatus() == 1) {
					if(!$this->_searchHelperConfig->displayOutofstock()){
						if($this->_stockHelper->getKlevuStockStatus(null,$groupProduct) == "yes") {
							$gPrice = $this->getKlevuSalePrice(null,$groupProduct,$store);
							$groupPrices[] = $gPrice['salePrice'];
						}
					} else {
						$gPrice = $this->getKlevuSalePrice(null,$groupProduct,$store);
						$groupPrices[] = $gPrice['salePrice'];
					}
				}
            }
        }
		
        asort($groupPrices);
        return array_shift($groupPrices);
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
		if($this->_searchHelperConfig->getPriceIncludesTax($store)) {
			//exluding
			if($this->_searchHelperConfig->getPriceDisplaySettings($store) == \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX){
				return $item->getPriceModel()->getTotalPrices($item, null, false, false);
			} else if($this->_searchHelperConfig->getPriceDisplaySettings($store) == \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX){
				return $item->getPriceModel()->getTotalPrices($item, null, true, false);
			}else if($this->_searchHelperConfig->getPriceDisplaySettings($store) == \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH){
				if(!$this->_searchHelperConfig->isTaxCalRequired($store)){
					return $item->getPriceModel()->getTotalPrices($item, null, false, false);
				}else {
					return $item->getPriceModel()->getTotalPrices($item, null, true, false);
				}
			}
			
		}else {
			//including
			if($this->_searchHelperConfig->getPriceDisplaySettings($store) == \Magento\Tax\Model\Config::DISPLAY_TYPE_INCLUDING_TAX){
				return $item->getPriceModel()->getTotalPrices($item, null, true, false);
			}else if($this->_searchHelperConfig->getPriceDisplaySettings($store) == \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX){
				return $item->getPriceModel()->getTotalPrices($item, null, false, false);
			}else if($this->_searchHelperConfig->getPriceDisplaySettings($store) == \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH){
				if(!$this->_searchHelperConfig->isTaxCalRequired($store)){
					return $item->getPriceModel()->getTotalPrices($item, null, false, false);
				}else {
					return $item->getPriceModel()->getTotalPrices($item, null, true, false);
				}
			}
		}
    }
	
}