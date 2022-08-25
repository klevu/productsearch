<?php

namespace Klevu\Search\Block\Catalog\Product;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Config as KlevuConfig;
use Klevu\Search\Helper\Data as Klevu_HelperData;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\Context as ProductBlockContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry as CoreRegistry;
use Magento\Framework\View\Element\Template as TemplateBase;

/**
 * Class Tracking
 * @package Klevu\Search\Block\Catalog\Product
 */
class Tracking extends TemplateBase
{
    /**
     * @var CoreRegistry
     */
    protected $_coreRegistry;

    /**
     * @var KlevuConfig
     */
    protected $_klevuConfig;

    /**
     * @var Klevu_HelperData
     */
    protected $_searchHelperData;

    /**
     * Tracking constructor.
     * @param ProductBlockContext $context
     * @param KlevuConfig $klevuConfig
     * @param array $data
     * @param Klevu_HelperData|null $searchHelperData
     */
    public function __construct(
        ProductBlockContext $context,
        KlevuConfig $klevuConfig,
        array $data = [],
        Klevu_HelperData $searchHelperData = null
    ) {
        $this->_coreRegistry = $context->getRegistry();
        $this->_klevuConfig = $klevuConfig;
        $this->_searchHelperData = $searchHelperData ?: ObjectManager::getInstance()->get(Klevu_HelperData::class);
        parent::__construct($context, $data);
    }

    /**
     * JSON of required tracking parameter for Klevu Product Click Tracking, based on current product
     * @return string
     */
    public function getJsonTrackingData()
    {
        $return = '';

        try {
            $product = $this->getCurrentProduct();
            if (!$product) {
                return $return;
            }

            $id = $product->getId();
            $name = $product->getName();
            $product_url = (method_exists($product, 'getProductUrl'))
                ? $product->getProductUrl()
                : '';

            $klevu_productGroupId = $id;
            $klevu_productVariantId = $id;

            $api_key = $this->_klevuConfig->getJsApiKey();
            $product = [
                'klevu_apiKey' => $api_key,
                'klevu_term' => '',
                'klevu_type' => 'clicked',
                'klevu_productId' => $id,
                'klevu_productName' => $name,
                'klevu_productUrl' => $product_url,
                'Klevu_typeOfRecord' => 'KLEVU_PRODUCT',
                "klevu_productGroupId" => $klevu_productGroupId,
                "klevu_productVariantId" => $klevu_productVariantId
            ];
            $return = json_encode($product);
        } catch (\Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage())
            );
        }

        return $return;
    }

    /**
     * @return ProductInterface|null
     */
    private function getCurrentProduct()
    {
        $product = $this->_coreRegistry->registry('product');
        if (!$product) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_WARN, "No product object found in registry");

            return null;
        }

        if (!$product instanceof ProductInterface) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf(
                    "Product object in registry expected to be of type %s; %s encountered",
                    ProductInterface::class,
                    get_class($product)
                )
            );

            return null;
        }

        return $product;
    }

    /**
     * Check klevu configured or nor
     *
     * @return boolean
     */
    public function isExtensionConfigured()
    {
        return $this->_klevuConfig->isExtensionConfigured();
    }

    /**
     * get klevu analytics URL
     *
     * @return string
     */
    public function getAnalyticsUrl()
    {
        $protocol = $this->getRequest()->isSecure() ? 'https://' : 'http://';

        return $protocol . $this->_klevuConfig->getAnalyticsUrl() . '/analytics/productTracking?';
    }
}

