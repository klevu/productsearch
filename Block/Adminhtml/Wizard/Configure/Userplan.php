<?php

namespace Klevu\Search\Block\Adminhtml\Wizard\Configure;

class Userplan extends \Magento\Backend\Block\Template
{

    /**
     * Return the submit URL for the user configuration form.
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('klevu_search/wizard/userplan_post');
    }

    /**
     * Return the base URL for the store.
     *
     * @return string
     */
    public function getStoreUrl()
    {
        return $this->getBaseUrl();
    }
	
	
	/**
     * Return plans from klevu server.
     *
     * @return array
     */
    public function getPlans()
    {
		$getPlans = \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Model\Api\Action\Getplans');
		
		$extension_version = \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Block\Search\Index')->getModuleInfo();
		
        $response = $getPlans->execute(array("store"=>"magento","extension_version" => (string)$extension_version[0]));
		if ($response->isSuccess()) {
		    $plans = $response->getData();
			return $plans['plans']['plan'];
		} else {
			return;
		}
    }
	
	
	
}
