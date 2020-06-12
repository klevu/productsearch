<?php

/**
 * Class \Klevu\Search\Block\Adminhtml\Form\Field\Logdownload
 *
 * @method setStoreId($id)
 * @method string getStoreId()
 */

namespace Klevu\Search\Block\Adminhtml\Form\Field;

use Klevu\Search\Helper\Data as Klevu_HelperData;
use Magento\Backend\Block\Template\Context as Template_Context;
use Magento\Framework\App\Filesystem\DirectoryList as DirectoryList;

class Logdownload extends \Magento\Config\Block\System\Config\Form\Field
{

    protected $_template = 'klevu/search/form/field/sync/downloadbutton.phtml';

    public function __construct(
        Template_Context $context,
        DirectoryList $directoryList,
        Klevu_HelperData $klevuHelperData,
        array $data = [])
    {
        $this->_directoryList = $directoryList;
        $this->_klevuHelperData = $klevuHelperData;
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
        $commentText = __('If file size is less than 1GB then you can click on button to download klevu search log file from var/log folder.');

        $element->setComment($commentText);
        $this->addData([
            "html_id" => $element->getHtmlId(),
            "button_label" => $buttonLabel,
            "destination_url" => $this->getUrl("klevu_search/download/logdownload")
        ]);

        return $this->_toHtml();
    }


    /**
     * Button label for download button if Klevu_Search.log file exists
     * @return string
     * @throws Exception
     */
    private function getButtonLabel()
    {
        $buttonLabel = __('Download Klevu Search Log');
        try {

            $filePath = $this->_directoryList->getPath('log') . "/" . \Klevu\Search\Helper\Data::LOG_FILE;
            if (file_exists($filePath)) {
                $filesize = filesize($filePath);
                if ($filesize < 1024) {
                    $buttonLabel = $buttonLabel . ' ( ' . $filesize . ' Bytes )';
                } else {
                    $filesize = $this->_klevuHelperData->bytesToHumanReadable($filesize);
                    $buttonLabel = $buttonLabel . ' ( ' . $filesize . ' )';
                }
            } else {
                $buttonLabel = '';
            }
            return $buttonLabel;
        } catch (\Exception $e) {
            $this->_klevuHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
    }
}
