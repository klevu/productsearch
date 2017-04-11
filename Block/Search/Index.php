<?php
/**
 * Copyright © 2015 Dd . All rights reserved.
 */
namespace Klevu\Search\Block\Search;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;

class Index extends \Magento\Framework\View\Element\Template
{
	const KlEVUPRESERVELAYOUT    = 1;
    const DISABLE     = 0;
    const KlEVUTEMPLATE = 2;
	
	public function _prepareLayout()
	{
	   return parent::_prepareLayout();
	} 
	
	public function isExtensionConfigured(){
		return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config')->isExtensionConfigured();
		
	}
	
	public function isLandingEnabled(){
		return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config')->isLandingEnabled();
		
	}
	
	public function getJsApiKey(){
		return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config')->getJsApiKey();
		
	}
	
	public function getModuleInfo(){
		$moduleInfo = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Module\ModuleList')->getOne('Klevu_Search');
		return $moduleInfo['setup_version'];
	}
	
	public function getJsUrl(){
		return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Config')->getJsUrl();
	}
	
	public function getStoreLanguage(){
		return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Data')->getStoreLanguage();
		
	}
	
	public function getCurrentCurrencyCode(){
		$store = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\StoreManagerInterface')->getStore();
		return $store->getCurrentCurrencyCode();
		
	}
	
	
	public function getCurrencyData(){
		$store = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\StoreManagerInterface')->getStore();
		return \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Helper\Data')->getCurrencyData($store);
		
	}
	
	public function isPubInUse() {
	    $check_pub = explode('/',$_SERVER["DOCUMENT_ROOT"]);
		$filter = array_filter($check_pub);
		$folder_name = end($filter);
		if($folder_name == "pub"){
			return true;
		} 
		return false;
	}


}
