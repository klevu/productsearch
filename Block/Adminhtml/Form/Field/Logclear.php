<?php
/**
 * Class \Klevu\Search\Block\Adminhtml\Form\Field\Logclear
 *
 * @method setStoreId($id)
 * @method string getStoreId()
 */
namespace Klevu\Search\Block\Adminhtml\Form\Field;

class Logclear extends \Magento\Config\Block\System\Config\Form\Field
{
    
    protected $_template = 'klevu/search/form/field/sync/clearlogbutton.phtml';
    
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
		$commentText = __('By Clicking on Rename Klevu Search Log, it will renamed the Klevu_Search.log file with current timestamp and newly file will be placed in the var/log directory.');
      
		$element->setComment($commentText);
		$this->addData([
			"html_id" => $element->getHtmlId(),
			"button_label" => $buttonLabel,				
			"destination_url" => $this->getUrl("klevu_search/download/logclear")
		]);
        
		return $this->_toHtml();
    }
	
	/**
     * Button label if Klevu_Search.log file exists
     * @return string
     * @throws Exception
     */
	private function getButtonLabel()
    {
		$_searchHelper = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Helper\Data');
		$buttonLabel = __('Rename Klevu Search Log');   
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
