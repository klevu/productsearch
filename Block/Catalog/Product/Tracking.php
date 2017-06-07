<?php

namespace Klevu\Search\Block\Catalog\Product;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;

class Tracking extends \Magento\Framework\View\Element\Template
{

    /**
     * JSON of required tracking parameter for Klevu Product Click Tracking, based on current product
     * @return string
     * @throws Exception
     */
    public function getJsonTrackingData()
    {
        // Get the product
        $product = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\Registry')->registry('current_product');
        $id = $product->getId();
        $name = $product->getName();
        $product_url = $product->getProductUrl();
        
        $api_key = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Helper\Config')->getJsApiKey();
            $product = [
                'klevu_apiKey' => $api_key,
                'klevu_term'   => '',
                'klevu_type'   => 'clicked',
                'klevu_productId' => $id,
                'klevu_productName' => $name,
                'klevu_productUrl' => $product_url,
                'Klevu_typeOfRecord' => 'KLEVU_PRODUCT'
            ];

            return json_encode($product);
    }
    
    public function isExtensionConfigured()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config')->isExtensionConfigured();
    }
    
    public function getAnalyticsUrl()
    {
        return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config')->getAnalyticsUrl();
    }
}
