<?php

namespace Klevu\Search\Block\Adminhtml\Wizard\Configure;

class Store extends \Magento\Backend\Block\Template
{

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
        $stores = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Store\Model\StoreManagerInterface')->getStores(false);
        $config = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Helper\Config');

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
		$config = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Helper\Config');
		if($config->getPriceDisplaySettings() == 3){
			return true;
		}
        return false;
    }
	
	public function getSyncUrlForStore(){
        $store_id = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Model\Session')->getCurrentKlevuStoreId();
        $rest_api = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Model\Session')->getCurrentKlevuRestApiKlevu();
        return $this->_storeManager->getStore($store_id)->getBaseUrl()."search/index/syncstore/store/".$store_id."/restapi/".$rest_api;
    }
}
