<?php
/**
 * Class \Klevu\Search\Block\Adminhtml\Form\Field\Logclear
 *
 * @method setStoreId($id)
 * @method string getStoreId()
 */

namespace Klevu\Search\Block\Adminhtml\Form\Field;

use Klevu\Search\Helper\Data as Klevu_HelperData;
use Magento\Backend\Block\Template\Context as Template_Context;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;

/**
 * Class Logclear
 * @package Klevu\Search\Block\Adminhtml\Form\Field
 */
class Logclear extends \Magento\Config\Block\System\Config\Form\Field
{

    protected $_template = 'klevu/search/form/field/sync/clearlogbutton.phtml';

    public function __construct(
        Template_Context $context,
        Klevu_HelperData $klevuHelperData,
        DirectoryList $directoryList,
        array $data = [])
    {
        $this->_klevuHelperData = $klevuHelperData;
        $this->_directoryList = $directoryList;
        parent::__construct($context, $data);
    }

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
        if (empty($buttonLabel)) {
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
        $buttonLabel = __('Rename Klevu Search Log');
        try {

            $filePath = $this->_directoryList->getPath('log') . "/" . \Klevu\Search\Helper\Data::LOG_FILE;
            if (file_exists($filePath)) {
                return $buttonLabel;
            } else {
                $buttonLabel = '';
            }
            return $buttonLabel;
        } catch (\Exception $e) {
            $this->_klevuHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
    }
}

