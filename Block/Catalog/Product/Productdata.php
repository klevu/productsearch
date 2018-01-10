<?php

namespace Klevu\Search\Block\Catalog\Product;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;

class Productdata extends \Magento\Framework\View\Element\Template
{

	protected $key;
    protected $method = 'AES-128-CBC';
    protected $iv = '1010101010101010';
    protected $option = OPENSSL_CIPHER_AES_128_CBC;
	protected $registry;
	protected $storeManagerInterface;
	protected $customerSession;
	protected $customerGroupCollection;
	
	
	public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
		\Klevu\Search\Helper\Stock $stockHelper,
		\Klevu\Search\Helper\Config $configHelper,
		\Klevu\Search\Helper\Price $priceHelper,
		\Klevu\Search\Helper\Data $searchHelperData,
		\Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->_registry = $registry;
		$this->_storeManagerInterface = $context->getStoreManager();
		$this->_stockHelper = $stockHelper;
		$this->_configHelper = $configHelper;
		$this->_priceHelper = $priceHelper;
		$this->_customerSession = $customerSession;
		$this->_searchHelperData = $searchHelperData;
        parent::__construct($context, $data);
    }

  
    public function encrypt($data,$key) {
        if (is_null($data)) {
            return "Error " . INVALID_PARAMS_ENCRYPTIONS . ": Data is null ";
        }
        $enc = openssl_encrypt($data, $this->method, $key, $this->option, $this->iv);
        return base64_encode($enc);
    }

    public function decrypt($data,$key) {
        if (is_null($data)) {
            return "Error " . INVALID_PARAMS_ENCRYPTIONS . ": Data is null ";
        }
        $data = base64_decode($data);
        $dec = openssl_decrypt($data, $this->method, $key, $this->option, $this->iv);
        return $dec;
    }
	
    /**
     * JSON of required to collect price information, based on current product
     * @return string
     * @logs Exception
     */
    public function getTrackingData()
    {
		try {
			// Get the product
			$product = $this->_registry->registry('current_product');
			$id = $product->getId();
			$store = $this->_storeManagerInterface->getStore();
			$rest_api = $this->_configHelper->getRestApiKey();
			$klevu_products['klevu_restApi'] = $rest_api;
			if($product->getData("type_id") == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
				$parent = $product;
				$productTypeInstance = $product->getTypeInstance();
				$usedProducts = $productTypeInstance->getUsedProducts($product);
				foreach ($usedProducts  as $child) {
					$product_saleprice = $this->_priceHelper->getKlevuSalePrice($parent, $child, $store);
					$product_child_sale_price = $this->_priceHelper->getKlevuSalePrice($child, $child, $store);
					$product_child_price = $this->_priceHelper->getKlevuPrice($child, $child, $store);
					$klevu_products['klevu_products'][] = [
						'id' => $parent->getId()."-".$child->getId(),
						'customerGroup'	=> $this->_customerSession->getCustomer()->getGroupId(),
						'salePrice' => $product_child_sale_price['salePrice'],
						'price' => $product_child_price['price'],
						'startPrice' => $product_saleprice['salePrice'], 
						'stock' => $this->_stockHelper->getKlevuStockStatus($parent, $child)
					];
				}
			} else {
				$parent = null;
				$product_saleprice = $this->_priceHelper->getKlevuSalePrice($parent, $product, $store);
				$product_price = $this->_priceHelper->getKlevuPrice($parent, $product, $store);
				$klevu_products['klevu_products'][] = [
					'id' =>  $id,
					'customerGroup'	=> $this->_customerSession->getCustomer()->getGroupId(),
					'salePrice' => $product_saleprice['salePrice'],
					'price' => $product_price['price'],
					'startPrice' => $product_saleprice['salePrice'],
					'stock' => $this->_stockHelper->getKlevuStockStatus($parent, $product)
				];
			}
			$data = json_encode($klevu_products);
			return $this->encrypt($data,$rest_api);
		} catch (\Exception $e) {
			$this->_searchHelperData->log(\Zend\Log\Logger::DEBUG, sprintf("Unable to get information for %s", $product->getId()));
		}

    }
	
	/**
     * JSON of required to collect price information, based on current product
     * @return string
     * @logs Exception
     */
    public function isTagMethodEnabled()
    {
		return $this->_configHelper->isTagMethodEnabled();
	}
  
}
