<?php

/**
 * Class \Klevu\Search\Block\Adminhtml\Form\Field\Image\Button
 *
 * @method setStoreId($id)
 * @method string getStoreId()
 */
 
namespace Klevu\Search\Block\Adminhtml\Form\Field\Image;

class Log extends \Magento\Config\Block\System\Config\Form\Field
{
    
    protected $_template = 'klevu/search/form/field/sync/logbutton.phtml';
    
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
		$buttonLabel = $this->getButtonLabel();
		if(empty($buttonLabel)){
			return;
		}	
        $url_params = ["debug" => "klevu"];
        $label_suffix = ($this->getStoreId()) ? " for This Store" : "";
        $store = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Store\Model\StoreManagerInterface');
        $storeId = $store->getDefaultStoreView()->getStoreId();
        $this->addData([
            "html_id"         => $element->getHtmlId(),
            "button_label"    => $buttonLabel,
            "destination_url" => $store->getStore($storeId)->getBaseUrl()."search/index/runexternaly"
        ]);

        return $this->_toHtml();
    }
	
	/**
     * Button label for send log if Klevu_Search.log file exists
	 *
     * @return string
     * @throws Exception
     */
	private function getButtonLabel()
    {
		$_searchHelper = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Helper\Data');
		$buttonLabel = __('Send Log');   
        try {    
            $dir = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Filesystem\DirectoryList');
            $filePath = $dir->getPath('log') . "/".\Klevu\Search\Helper\Data::LOG_FILE;
            if (file_exists($filePath)) {                
                return $buttonLabel;
            } else {
                $buttonLabel = '';
            }
            return $buttonLabel;
        } catch (Exception $e) {
            $_searchHelper->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
    }
}
