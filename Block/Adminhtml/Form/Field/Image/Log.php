<?php

/**
 * Class \Klevu\Search\Block\Adminhtml\Form\Field\Image\Button
 *
 * @method setStoreId($id)
 * @method string getStoreId()
 */
 
namespace Klevu\Search\Block\Adminhtml\Form\Field\Image;

class Log extends \Magento\Config\Block\System\Config\Form\Field {
	
    protected $_template = 'klevu/search/form/field/sync/logbutton.phtml';
	
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element) {
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

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element) {
        $url_params = array("debug" => "klevu");
        $label_suffix = ($this->getStoreId()) ? " for This Store" : "";
		$store = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\StoreManagerInterface');
        $storeId = $store->getDefaultStoreView()->getStoreId();
        $this->addData(array(
            "html_id"         => $element->getHtmlId(),
            "button_label"    => sprintf("Send Log"),
            "destination_url" => $store->getStore($storeId)->getBaseUrl()."search/index/Runexternaly"
        ));

        return $this->_toHtml();
    }
}
