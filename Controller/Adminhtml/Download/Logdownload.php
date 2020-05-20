<?php

namespace Klevu\Search\Controller\Adminhtml\Download;

use Klevu\Search\Helper\Data as Klevu_HelperData;
use Magento\Backend\App\Action\Context as ActionContext;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Archive\Zip;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface as TimezoneInterface;

class Logdownload extends \Magento\Backend\App\Action
{

    /**
     * Construct
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     */
    public function __construct(
        ActionContext $context,
        TimezoneInterface $timezone,
        DirectoryList $directoryList,
        Zip $zip,
        FileFactory $fileFactory,
        Klevu_HelperData $klevuHelperData
    )
    {
        parent::__construct($context);
        $this->_context = $context;
        $this->_timezone = $timezone;
        $this->_directoryList = $directoryList;
        $this->_zip = $zip;
        $this->_fileFactory = $fileFactory;
        $this->_searchHelperData = $klevuHelperData;
    }

    public function execute()
    {

        try {
            //$dir = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Filesystem\DirectoryList');
            $logdir = $this->_directoryList->getPath('log');
            $vardir = $this->_directoryList->getPath('var');
            $path = $logdir . "/Klevu_Search.log";


            if (filesize($path) <= 1073741824) {
                $this->_zip->pack($logdir . "/Klevu_Search.log", $vardir . '/Klevu_Search.zip');
                $file = $vardir . "/Klevu_Search.zip";
                $contentToGen =
                [
                    'type' => 'filename',
                    'value' => $file,
                    'rm' => true
                ];
                return $this->_fileFactory->create("Klevu_Search.zip", $contentToGen, \Magento\Framework\App\Filesystem\DirectoryList::ROOT, 'application/zip');
                //return $this->_fileFactory->create("Klevu_Search.zip", @file_get_contents($file));
            }
        } catch (Exception $e) {
            $message = __($e->getMessage());
            $this->_context->getMessageManager()->addErrorMessage($message);
            $this->_searchHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown in while downloading file. %s::%s - %s", __CLASS__, __METHOD__, $message));
        }
        $this->_redirect('adminhtml/system_config/edit/section/klevu_search');
    }

    protected function _isAllowed()
    {
        return true;
    }


}

