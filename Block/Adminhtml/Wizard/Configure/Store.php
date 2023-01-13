<?php

namespace Klevu\Search\Block\Adminhtml\Wizard\Configure;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Session as Klevu_Session;
use Klevu\Search\Model\Sync as Klevu_Sync;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context as Template_Context;
use Klevu\Search\Helper\Backend as Klevu_BackendHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class Store extends Template
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var Klevu_Sync
     */
    protected $_klevuSync;
    /**
     * @var Klevu_Session
     */
    protected $_klevuSession;
    /**
     * @var Klevu_BackendHelper
     */
    protected $_klevuBackendHelper;

    /**
     * Store constructor.
     *
     * @param Template_Context $context
     * @param Klevu_Sync $klevuSync
     * @param Klevu_Session $klevuSession
     * @param Klevu_BackendHelper $klevuBackendHelper
     * @param array $data
     */
    public function __construct(
        Template_Context $context,
        Klevu_Sync $klevuSync,
        Klevu_Session $klevuSession,
        Klevu_BackendHelper $klevuBackendHelper,
        array $data = []
    ) {
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
     * @throws NoSuchEntityException
     */
    public function getStoreSelectData()
    {
        $stores = $this->_storeManager->getStores(false);
        $helperManager = $this->_klevuSync->getHelper();
        /** @var ConfigHelper $config */
        $config = $helperManager->getConfigHelper();
        $data = [];

        foreach ($stores as $store) {
            /** @var StoreInterface $store */
            if ($config->getJsApiKey($store) && $config->getRestApiKey($store)) {
                // Skip already configured stores
                continue;
            }
            $website = $store->getWebsite();
            $websiteName = $website->getName();
            $group = $store->getGroup();
            $groupName = $group->getName();

            if (!isset($data[$websiteName])) {
                $data[$websiteName] = [];
            }
            if (!isset($data[$websiteName][$groupName])) {
                $data[$websiteName][$groupName] = [];
            }

            $data[$websiteName][$groupName][] = $store;
        }

        return $data;
    }

    /**
     * Return flag to display tax settings in wizard based on price display setting in magento.
     *
     * @return bool
     */
    public function showTaxSettings()
    {
        /** @var ConfigHelper $config */
        $config = $this->_klevuSync->getHelper()->getConfigHelper();

        return $config->getPriceDisplaySettings() === 3;
    }

    /**
     * Return Klevu Sync URL for current store
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getSyncUrlForStore()
    {
        $store_id = $this->_klevuSession->getCurrentKlevuStoreId();
        $restApiKey = $this->_klevuSession->getCurrentKlevuRestApiKlevu();
        $hashkey = $restApiKey ? hash('sha256', (string)$restApiKey) : '';

        return $this->_storeManager->getStore($store_id)->getBaseUrl()
            . "search/index/syncstore/store/" . $store_id
            . "/hashkey/" . $hashkey;
    }

    /**
     * Recommend to Use Collection Method or not based on collection.
     *
     * @return bool
     */
    public function showUseCollectionMethod()
    {
        return $this->_klevuBackendHelper->getRecommendToUseCollectionMethod();
    }
}
