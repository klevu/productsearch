<?php
/**
 * Class Logclear
 * @package Klevu\Search\Controller\Adminhtml\Download
 */
namespace Klevu\Search\Controller\Adminhtml\Download;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context as ActionContext;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface as TimezoneInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Klevu\Search\Helper\Data as Klevu_HelperData;

class Logclear extends Action
{
    /**
     * Timezone
     *
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $_timezone;

    /**
     * Context
     *
     * @var \Magento\Backend\App\Action\Context
     */
    private $_context;

    /**
     * Klevu Helper
     *
     * @var \Klevu\Search\Helper\Data
     */
    private $klevuHelperData;

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
        Klevu_HelperData $klevuHelperData
    )
    {
        parent::__construct($context);
        $this->_context = $context;
        $this->_timezone = $timezone;
        $this->_directoryList = $directoryList;
        $this->_searchHelperData = $klevuHelperData;

    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\FileSystemException |  Exception
     */
    public function execute()
    {
        $logFileTitle = \Klevu\Search\Helper\Data::LOG_FILE;
        try {
            $filePath = $this->_directoryList->getPath('log') . "/".$logFileTitle;
            if (file_exists($filePath)) {
                if(!is_writable($filePath)) {
                    throw new \Magento\Framework\Exception\FileSystemException(
                        __($logFileTitle.' file is not writable!')
                    );
                }
                $fileName = $this->_directoryList->getPath('log').'/Klevu_Search_'.$this->_timezone->scopeTimeStamp().'.log';
                if(rename( $filePath, $fileName )) {
                    $this->_context->getMessageManager()->addSuccessMessage(__($logFileTitle.' file has been renamed successfully!'));
                }
            }else {
                $this->_context->getMessageManager()->addNoticeMessage(__($logFileTitle.' file not found!'));
            }
        } catch (Exception $e) {
            $message = __($e->getMessage());
            $this->_context->getMessageManager()->addErrorMessage($message);
            $this->_searchHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $message));

        } catch(\Magento\Framework\Exception\FileSystemException $e) {
            $message = $e->getMessage();
            $this->_searchHelperData->log(\Zend\Log\Logger::CRIT, sprintf("File System Exception thrown while renaming log file : %s", $e->getMessage()));
            $this->_context->getMessageManager()->addErrorMessage($message);
        }
        $this->_redirect('adminhtml/system_config/edit/section/klevu_search');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return true;
    }


}
