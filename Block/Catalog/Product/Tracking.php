<?php
namespace Klevu\Search\Block\Catalog\Product;
use Magento\Framework\View\Element\Template as TemplateBase;
use Magento\Catalog\Block\Product\Context as ProductBlockContext;
use Klevu\Search\Helper\Config as KlevuConfig;

class Tracking extends TemplateBase
{
    protected $_coreRegistry;
    protected $_klevuConfig;

    /**
     * Tracking constructor.
     * @param ProductBlockContext $context
     * @param KlevuConfig $klevuConfig
     * @param array $data
     */
    public function __construct( ProductBlockContext $context, KlevuConfig $klevuConfig, array $data = [])
    {
        $this->_coreRegistry = $context->getRegistry();
        $this->_klevuConfig = $klevuConfig;

        parent::__construct($context, $data);
    }

    /**
     * JSON of required tracking parameter for Klevu Product Click Tracking, based on current product
     * @return string
     * @throws Exception
     */
    public function getJsonTrackingData()
    {
        // Get the product
        $product = $this->_coreRegistry->registry('product');
        if(!$product){
            $id = 0;
            $name = "";
            $product_url = "";
        } else {
            $id = $product->getId();
            $name = $product->getName();
            $product_url = $product->getProductUrl();
        }


        $api_key =  $this->_klevuConfig->getJsApiKey();
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
        return  $this->_klevuConfig->isExtensionConfigured();
    }

    public function getAnalyticsUrl()
    {
        return  $this->_klevuConfig->getAnalyticsUrl();
    }
}