<?php

namespace Klevu\Search\Block\Search;

use Klevu\Search\Helper\Backend as KlevuHelperBackend;
use Klevu\Search\Helper\Config as KlevuConfig;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Source\ThemeVersion;
use Klevu\Search\Model\Sync as KlevuSync;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as Magento_TemplateContext;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Index extends Template
{
    // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
    const KlEVUPRESERVELAYOUT = 1;
    const DISABLE = 0;
    // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
    const KlEVUTEMPLATE = 2;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;
    /**
     * @var KlevuConfig
     */
    protected $_klevuConfig;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var RequestInterface
     */
    protected $_requestInterface;
    /**
     * @var Magento_TemplateContext
     */
    protected $_context;
    /**
     * @var KlevuSync
     */
    protected $_klevusync;
    /**
     * @var SearchHelper
     */
    protected $_klevuDataHelper;
    /**
     * @var KlevuConfig
     */
    protected $_klevuConfigHelper;
    /**
     * @var KlevuHelperBackend
     */
    protected $_klevuHelperBackend;

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
    ) {
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

    /**
     * @return bool
     */
    public function isExtensionConfigured()
    {
        return $this->_klevuConfigHelper->isExtensionConfigured();
    }

    /**
     * @return int
     */
    public function isLandingEnabled()
    {
        return $this->_klevuConfigHelper->isLandingEnabled();
    }

    /**
     * @return string
     */
    public function getJsApiKey()
    {
        return $this->_klevuConfigHelper->getJsApiKey();
    }

    /**
     * @return mixed
     */
    public function getModuleInfo()
    {
        return $this->_klevuConfigHelper->getModuleInfo();
    }

    /**
     * @return string
     */
    public function getJsUrl()
    {
        return $this->_klevuConfigHelper->getJsUrl();
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
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

    /**
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getCurrencyData()
    {
        return $this->_klevuDataHelper->getCurrencyData($this->getStore());
    }

    /**
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    protected function getStore()
    {
        return $this->_storeManager->getStore();
    }

    /**
     * Based on the usage it can be removed
     *
     * @return KlevuSync
     */
    public function getKlevuSync()
    {
        return $this->_klevusync;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getKlevuRequestParam($key)
    {
        return $this->_request->getParam($key);
    }

    /**
     * @return mixed
     */
    public function getStoreParam()
    {
        return $this->_request->getParam('store');
    }

    /**
     * @return string
     */
    public function getRestApiParam()
    {
        return urlencode($this->_request->getParam('hashkey'));
    }

    /**
     * @param string|int $storeId
     *
     * @return false|string|null
     */
    public function getRestApi($storeId)
    {
        $restApiKey = $this->_klevuConfigHelper->getRestApiKey((int)$storeId);

        return $restApiKey ? hash('sha256', (string)$restApiKey) : null;
    }

    /**
     * @return bool
     */
    public function isPubInUse()
    {
        $pub = $this->_directoryList->getUrlPath("pub");

        return $pub !== "pub";
    }

    /**
     * @return bool
     */
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
     * @return bool
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
