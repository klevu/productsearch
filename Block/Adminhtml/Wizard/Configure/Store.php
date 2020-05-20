<?php

namespace Klevu\Search\Block\Adminhtml\Wizard\Configure;

use Klevu\Search\Model\Session as Klevu_Session;
use Klevu\Search\Model\Sync as Klevu_Sync;
use Magento\Backend\Block\Template\Context as Template_Context;
use Klevu\Search\Helper\Backend as Klevu_BackendHelper;

/**
 * Class Store
 * @package Klevu\Search\Block\Adminhtml\Wizard\Configure
 */
class Store extends \Magento\Backend\Block\Template
{

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;


    /**
     * Store constructor.
     * @param Template_Context $context
     * @param Klevu_Sync $klevuSync
     * @param Klevu_Session $klevuSession
     * @param array $data
     */
    public function __construct(
        Template_Context $context,
        Klevu_Sync $klevuSync,
        Klevu_Session $klevuSession,
        Klevu_BackendHelper $klevuBackendHelper,
        array $data = [])
    {
        $this->_klevuSync = $klevuSync;
        $this->_klevuSession = $klevuSession;
        $this->_klevuBackendHelper = $klevuBackendHelper;
        $this->_storeManager = $context->getStoreManager();
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
        $stores = $this->_storeManager->getStores(false);
        $config = $this->_klevuSync->getHelper()->getConfigHelper();

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
        $config = $this->_klevuSync->getHelper()->getConfigHelper();
        if ($config->getPriceDisplaySettings() == 3) {
            return true;
        }
        return false;
    }


    /**
     * Return Klevu Sync URL for current store
     *
     * @return string
     */
    public function getSyncUrlForStore()
    {
        $store_id = $this->_klevuSession->getCurrentKlevuStoreId();
        $rest_api = $this->_klevuSession->getCurrentKlevuRestApiKlevu();
        return $this->_storeManager->getStore($store_id)->getBaseUrl() . "search/index/syncstore/store/" . $store_id . "/restapi/" . $rest_api;
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

