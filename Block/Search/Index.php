<?php
/**
 * Copyright © 2015 Dd . All rights reserved.
 */

namespace Klevu\Search\Block\Search;

use Klevu\Search\Helper\Backend as KlevuHelperBackend;
use Klevu\Search\Helper\Config as KlevuConfig;
use Klevu\Search\Model\Source\ThemeVersion;
use Klevu\Search\Model\Sync as KlevuSync;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as Magento_TemplateContext;
use Magento\Store\Model\ScopeInterface;


/**
 * Class Index
 * @package Klevu\Search\Block\Search
 */
class Index extends Template
{
    const KlEVUPRESERVELAYOUT = 1;
    const DISABLE = 0;
    const KlEVUTEMPLATE = 2;

    protected $_directoryList;

    protected $_klevuConfig;

    protected $_storeManager;

    protected $_requestInterface;

    /**
     * Index constructor.
     *
     * @param Magento_TemplateContext $context
     * @param DirectoryList $directorylist
     * @param KlevuSync $klevuSync
     * @param KlevuConfig $klevuConfig
     * @param KlevuHelperBackend $klevuHelperBackend
     * @param array $data
     */
    public function __construct(
        Magento_TemplateContext $context,
        DirectoryList $directorylist,
        KlevuSync $klevuSync,
        KlevuConfig $klevuConfig,
        KlevuHelperBackend $klevuHelperBackend,
        array $data = []
    )
    {
        $this->_context = $context;
        $this->_storeManager = $this->_context->getStoreManager();
        $this->_requestInterface = $this->_context->getRequest();
        $this->_directoryList = $directorylist;
        $this->_klevusync = $klevuSync;
        $this->_klevuDataHelper = $this->_klevusync->getHelper()->getDataHelper();
        $this->_klevuConfigHelper = $this->_klevusync->getHelper()->getConfigHelper();
        $this->_klevuConfig = $klevuConfig;
        $this->_klevuHelperBackend = $klevuHelperBackend;

        parent::__construct($context, $data);
    }

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function isExtensionConfigured()
    {
        return $this->_klevuConfigHelper->isExtensionConfigured();
    }

    public function isLandingEnabled()
    {
        return $this->_klevuConfigHelper->isLandingEnabled();
    }

    public function getJsApiKey()
    {
        return $this->_klevuConfigHelper->getJsApiKey();
    }

    public function getModuleInfo()
    {
        return $this->_klevuConfigHelper->getModuleInfo();
    }

    public function getJsUrl()
    {
        return $this->_klevuConfigHelper->getJsUrl();
    }

    public function getStoreLanguage()
    {
        return $this->_klevuDataHelper->getStoreLanguage();
    }

    /**
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        $return = '';
        try {
            $store = $this->getStore();
            $return = (string)$store->getBaseCurrencyCode();
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage(), ['method' => __METHOD__]);
        }

        return $return;
    }

    /**
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        $return = '';
        try {
            $store = $this->getStore();
            $return = (string)$store->getCurrentCurrencyCode();
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage(), ['method' => __METHOD__]);
        }

        return $return;
    }

    public function getCurrencyData()
    {
        return $this->_klevuDataHelper->getCurrencyData($this->getStore());
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws NoSuchEntityException
     */
    protected function getStore()
    {
        return $this->_storeManager->getStore();
    }

    //Based on the usage it can be removed
    public function getKlevuSync()
    {
        return $this->_klevusync;
    }

    public function getKlevuRequestParam($key)
    {
        return $this->_request->getParam($key);
    }

    public function getStoreParam()
    {
        return $this->_request->getParam('store');
    }

    public function getRestApiParam()
    {
        return urlencode($this->_request->getParam('hashkey'));
    }

    public function getRestApi($store_id)
    {
        return hash('sha256', (string)$this->_klevuConfigHelper->getRestApiKey((int)$store_id));
    }

    public function isPubInUse()
    {
        $pub = $this->_directoryList->getUrlPath("pub");
        if ($pub == "pub") {
            return false;
        }
        return true;
    }


    public function isCustomerGroupPriceEnabled()
    {
        return $this->_klevuConfigHelper->isCustomerGroupPriceEnabled();
    }

    /**
     * Retrieve default per page values
     *
     * @return array
     */
    public function getGridPerPageValues()
    {
        return explode(",", $this->_klevuConfigHelper->getCatalogGridPerPageValues());
    }

    /**
     * Retrieve default per page
     *
     * @return int
     */
    public function getGridPerPage()
    {
        return (int)$this->_klevuConfigHelper->getCatalogGridPerPage() ?
            $this->_klevuConfigHelper->getCatalogGridPerPage() : 24;
    }

    /**
     * To show message indexers are invalid
     *
     * @return boolean
     */
    public function isShowIndexerMessage()
    {
        return $this->_klevuHelperBackend->checkToShowIndexerMessage();
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    protected function _toHtml()
    {
        try {
            $store = $this->getStore();
            $storeId = (int)$store->getId();
        } catch (NoSuchEntityException $e) {
            $this->_logger->error($e->getMessage());

            return '';
        }

        $themeVersion = $this->_scopeConfig->getValue(
            KlevuConfig::XML_PATH_THEME_VERSION,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );

        if ((ThemeVersion::V2 === $themeVersion) || !$this->isExtensionConfigured()) {
            return '';
        }

        return parent::_toHtml();
    }
}

