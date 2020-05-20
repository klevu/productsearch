<?php
/**
 * Copyright © 2015 Dd . All rights reserved.
 */

namespace Klevu\Search\Block\Search;

use Klevu\Search\Helper\Backend as KlevuHelperBackend;
use Klevu\Search\Helper\Config as KlevuConfig;
use Klevu\Search\Model\Sync as KlevuSync;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as Magento_TemplateContext;


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
     * Index constructor
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
        //return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config')->getJsApiKey();
        return $this->_klevuConfigHelper->getJsApiKey();
    }

    public function getModuleInfo()
    {
        //$moduleInfo = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Module\ModuleList')->getOne('Klevu_Search');
        //return $moduleInfo['setup_version'];
        return $this->_klevuConfigHelper->getModuleInfo();
    }

    public function getJsUrl()
    {
        //return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config')->getJsUrl();
        return $this->_klevuConfigHelper->getJsUrl();
    }

    public function getStoreLanguage()
    {
        //return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Data')->getStoreLanguage();
        return $this->_klevuDataHelper->getStoreLanguage();
    }

    public function getCurrentCurrencyCode()
    {
        //$store = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\StoreManagerInterface')->getStore();
        //return $store->getCurrentCurrencyCode();
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function getCurrencyData()
    {
        /*$store = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\StoreManagerInterface')->getStore();
        return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Data')->getCurrencyData($store);*/
        return $this->_klevuDataHelper->getCurrencyData($this->getStore());
    }

    protected function getStore()
    {
        return $this->_storeManager->getStore();
    }

    //Based on the usage it can be removed
    public function getKlevuSync()
    {
        //return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Model\Sync');
        return $this->_klevusync;
    }

    public function getKlevuRequestParam($key)
    {
        /*$om = \Magento\Framework\App\ObjectManager::getInstance();
        $request = $om->get('Magento\Framework\App\RequestInterface');
        $store_id = $request->getParam('store');
        $store = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\StoreManagerInterface');
        $store_view = $store->getStore($store_id);
        $value = $request->getParam($key);
        return $value;*/
        return $this->_request->getParam($key);
    }

    public function getStoreParam()
    {
        /*$om = \Magento\Framework\App\ObjectManager::getInstance();
        $request = $om->get('Magento\Framework\App\RequestInterface');
        $store_id = $request->getParam('store');
        return $store_id;*/
        return $this->_request->getParam('store');
    }

    public function getRestApiParam()
    {
        /*$om = \Magento\Framework\App\ObjectManager::getInstance();
        $request = $om->get('Magento\Framework\App\RequestInterface');
        $restapi = $request->getParam('restapi');
        return $restapi;*/
        return $this->_request->getParam('restapi');
    }

    public function getRestApi($store_id)
    {
        //$om = \Magento\Framework\App\ObjectManager::getInstance();
        //$rest_api = $om->get('\Klevu\Search\Model\Sync')->getHelper()->getConfigHelper()->getRestApiKey($store_id);
        //return $rest_api;
        //return $this->_klevusync->getHelper()->getConfigHelper()->getRestApiKey((int)$store_id);
        return $this->_klevuConfigHelper->getRestApiKey((int)$store_id);

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
}
