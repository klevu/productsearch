<?php

namespace Klevu\Search\Controller\Adminhtml\Download;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Model\Session;

class Logdownload extends \Magento\Backend\App\Action
{


    public function __construct(
		\Magento\Backend\App\Action\Context $context)
    {
        parent::__construct($context);
    }

    public function execute()
    {
		$dir = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Filesystem\DirectoryList');
        $logdir = $dir->getPath('log');
		$vardir = $dir->getPath('var');
        $path = $logdir."/Klevu_Search.log";
		if(filesize($path) <= 1073741824) {
			$tarPacker = new \Magento\Framework\Archive\Zip();
			$tarPacker->pack($logdir."/Klevu_Search.log",$vardir.'/Klevu_Search.zip');
			$file = $vardir."/Klevu_Search.zip";
			$filefactory = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Response\Http\FileFactory');
			return $filefactory->create(
				"Klevu_Search.zip",
				@file_get_contents($file)
			);
		}
    }
    
    protected function _isAllowed()
    {
        return true;
    }
    

}
