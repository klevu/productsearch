<?php
/**
 * Class Logclear
 * @package Klevu\Search\Controller\Adminhtml\Download
 */
namespace Klevu\Search\Controller\Adminhtml\Download;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context as ActionContext;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface as TimezoneInterface;

class Logclear extends Action
{
	/**
     * Timezone 
     *
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */    
    private $_timezone;
	
	/**
     * Construct
     *
     * @param \Magento\Backend\App\Action\Context $context
	 * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */	 
    public function __construct(
		ActionContext $context,		
		TimezoneInterface $timezone	
		)
    { 
		$this->_timezone = $timezone;
		parent::__construct($context);
    }

	/**
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\FileSystemException |  Exception
     */
    public function execute()
    {			
		$logFileTitle = \Klevu\Search\Helper\Data::LOG_FILE;
		$_searchHelper = $this->objectManager->get('\Klevu\Search\Helper\Data');
		try {   
			$dir = $this->objectManager->get('Magento\Framework\App\Filesystem\DirectoryList');			 
			$filePath = $dir->getPath('log') . "/".$logFileTitle;			
			if (file_exists($filePath)) {
				if(!is_writable($filePath)) {
					throw new \Magento\Framework\Exception\FileSystemException(
						__($logFileTitle.' file is not writable!')
					);
				}				
				$fileName = $dir->getPath('log').'/Klevu_Search_'.$this->_timezone->scopeTimeStamp().'.log';
				if(rename( $filePath, $fileName )) {
					$this->_messageManager->addSuccessMessage(__($logFileTitle.' file has been renamed successfully!'));
				}
			}else {
				$this->_messageManager->addNoticeMessage(__($logFileTitle.' file not found!'));
			}
		} catch (Exception $e) {				
			$message = __($e->getMessage());
			$this->_messageManager->addErrorMessage($message);
            $_searchHelper->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $message));
			 
        } catch(\Magento\Framework\Exception\FileSystemException $e) {
			$message = $e->getMessage();
			$_searchHelper->log(\Zend\Log\Logger::CRIT, sprintf("File System Exception thrown while renaming log file : %s", $e->getMessage()));			
			$this->_messageManager->addErrorMessage($message);
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
