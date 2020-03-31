<?php
namespace Klevu\Search\Block\Adminhtml\Wizard\Configure;

use Magento\Backend\Block\Template as Template;
use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;
use Klevu\Search\Helper\Backend as Klevu_BackendHelper;
use Klevu\Search\Model\Session as Klevu_Session;

class Store extends \Magento\Backend\Block\Template
{

    /**
     * @var \Magento\Backend\Block\Template
     */
    protected $_context;


    /**
     * Wizard Store constructor
     *
     * @param \Klevu\Search\Model\Klevu\HelperManager $klevuHelperManager
     * @param \Klevu\Search\Helper\Backend $klevuBackendHelper
     * @param \Magento\Backend\Block\Template $context
	 * @param \Klevu\Search\Model\Session $klevuSession
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Klevu_HelperManager $klevuHelperManager,
        Klevu_BackendHelper $klevuBackendHelper,
        Klevu_Session $klevuSession,
        array $data = []
    )
    {
        $this->_klevuHelperManager = $klevuHelperManager;
        $this->_klevuBackendHelper = $klevuBackendHelper;
        $this->_context = $context;
        $this->_klevuSession = $klevuSession;
        parent::__construct($context, $data);
    }

    /**
     * Return the submit URL for the store configuration form.
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl("klevu_search/wizard/store_post");
    }

    /**
     * Return the list of stores that can be selected to be configured (i.e. haven't
     * been configured already), organised by website name and group name.
     *
     * @return array
     */
    public function getStoreSelectData()
    {
        //$stores = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Store\Model\StoreManagerInterface')->getStores(false);
        $stores = $this->_context->getStoreManager()->getStores();
        $config = $this->_klevuHelperManager->getConfigHelper();

        $data = [];

        foreach ($stores as $store) {
            /** @var \Magento\Framework\Model\Store $store */
            if ($config->getJsApiKey($store) && $config->getRestApiKey($store)) {
                // Skip already configured stores
                continue;
            }

            $website = $store->getWebsite()->getName();
            $group = $store->getGroup()->getName();

            if (!isset($data[$website])) {
                $data[$website] = [];
            }
            if (!isset($data[$website][$group])) {
                $data[$website][$group] = [];
            }

            $data[$website][$group][] = $store;
        }
        return $data;
    }

    /**
     * Return flag to display tax settings in wizard based on price display setting in magento.
     *
     * @return string
     */
    public function showTaxSettings()
    {
        if($this->_klevuHelperManager->getConfigHelper()->getPriceDisplaySettings() == 3){
            return true;
        }
        return false;
    }

	/**
     * Return Klevu Sync URL for current store
     *
     * @return string
     */
    public function getSyncUrlForStore(){
        //$store_id = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Model\Session')->getCurrentKlevuStoreId();
        //$rest_api = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Model\Session')->getCurrentKlevuRestApiKlevu();
        $store_id = $this->_klevuSession->getCurrentKlevuStoreId();
        $rest_api = $this->_klevuSession->getCurrentKlevuRestApiKlevu();
        return $this->_storeManager->getStore($store_id)->getBaseUrl()."search/index/syncstore/store/".$store_id."/restapi/".$rest_api;
    }

    /**
     * Recommend to Use Collection Method or not based on collection.
     *
     * @return boolean
     */
    public function showUseCollectionMethod()
    {
        return $this->_klevuBackendHelper->getRecommendToUseCollectionMethod();
    }
}
