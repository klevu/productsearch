<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Store\Level;

use \Klevu\Search\Helper\Config as Klevu_HelperConfig;
use \Magento\Backend\Block\Template\Context as Template_Context;

class Label extends \Magento\Config\Block\System\Config\Form\Field
{
	private $_context;
	
	public function __construct(
        Template_Context $context,
        Klevu_HelperConfig $klevuHelperConfig,         
        array $data = []
    )
    {
		parent::__construct($context, $data);
		$this->_context = $context;
        $this->_klevuHelperConfig = $klevuHelperConfig;        
    }
	
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $request = $this->getRequest();
		$store = $this->_context->getStoreManager();		
        if($store->isSingleStoreMode()) {
            $store_code = $store->getStore()->getId();
            return $this->_klevuHelperConfig->getLastProductSyncRun($store_code);
        }elseif($element->getScope() == "stores") {
            $store_code = $request->getParam("store");
            return $this->_klevuHelperConfig->getLastProductSyncRun($store_code);
        } else {
            return __("Switch to store scope to set");
        }
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setData('scope', $element->getScope());

        // Remove the inheritance checkbox
        $element
            ->unsCanUseWebsiteValue()
            ->unsCanUseDefaultValue();

        return parent::render($element);
    }
}