<?php

namespace Klevu\Search\Model\System\Config\Source;

class Syncoptions
{

    const SYNC_PARTIALLY = 1;
    const SYNC_ALL = 2;

    public function toOptionArray()
    {
	    $store_param = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\RequestInterface')->getParams('store');
		$store_mode = \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Store\Model\StoreManagerInterface')->isSingleStoreMode();
		
   		if (!empty($store_param['store']) || $store_mode == true) {
			return [
			   ['value' => static::SYNC_PARTIALLY, 'label' => __('Updates only (syncs data immediately)')],
			   ['value' => static::SYNC_ALL, 'label' => __('All data (syncs data on CRON execution)')],
			];
		} else {
			return [
			  // ['value' => static::SYNC_PARTIALLY, 'label' => __('Updates only (syncs data immediately)')],
			   ['value' => static::SYNC_ALL, 'label' => __('All data (syncs data on CRON execution)')],
			];
		
		}
    }
}
