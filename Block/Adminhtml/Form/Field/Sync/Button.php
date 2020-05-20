<?php

/**
 * Class \Klevu\Search\Block\Adminhtml\Form\Field\Sync\Button
 *
 * @method setStoreId($id)
 * @method string getStoreId()
 */
namespace Klevu\Search\Block\Adminhtml\Form\Field\Sync;

use Klevu\Search\Model\Sync as Klevu_Sync;

class Button extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Klevu_Sync $klevuSync,
        array $data = [])
    {
        $this->_storeManager = $context->getStoreManager();
        $this->_klevuSync = $klevuSync;
        parent::__construct($context, $data);
    }
    
    protected $_template = 'klevu/search/form/field/sync/button.phtml';

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($element->getScope() == "stores") {
            $this->setStoreId($element->getScopeId());
        }

        // Remove the scope information so it doesn't get printed out
        $element
            ->unsScope()
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();

        return parent::render($element);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $url_params = ($this->getStoreId()) ? ["store" => $this->getStoreId()] : [];
        $label_suffix = ($this->getStoreId()) ? " for This Store" : "";

        $this->addData([
            "html_id"         => $element->getHtmlId(),
            "button_label"    => sprintf("Sync Data %s", $label_suffix),
            "destination_url" => $this->getUrl("klevu_search/sync/all/store/".$this->getStoreId(), $url_params)
			
        ]);

        return $this->_toHtml();
    }
	
	public function getStoreParam()
    {
        $store_id = $this->getRequest()->getParam('store');
		return $store_id;
	}

	public function getRestApi($store_id)
    {
		/*$om = \Magento\Framework\App\ObjectManager::getInstance();
		$rest_api = $om->get('\Klevu\Search\Model\Sync')->getHelper()->getConfigHelper()->getRestApiKey($store_id);
		return $rest_api;*/
        return $this->_klevuSync->getHelper()->getConfigHelper()->getRestApiKey((int)$store_id);
	}

	public function getSyncUrlForStore(){
		
		//$store = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\StoreManagerInterface');
        $store = $this->_storeManager;
		if($store->isSingleStoreMode())
		{
			$store_id = $store->getStore()->getId();
		} else {
			$store_id = $this->getRequest()->getParam('store');
		}
		$rest_api = $this->getRestApi($store_id);
        return $this->_storeManager->getStore($store_id)->getBaseUrl()."search/index/syncstore/store/".$store_id."/restapi/".$rest_api;
    }
}
