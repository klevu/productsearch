<?php

namespace Klevu\Search\Controller\Index;

class Runexternaly extends  \Magento\Framework\App\Action\Action
{
   

    public function __construct(\Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
		\Klevu\Search\Model\Product\Sync $modelProductSync, 
        \Magento\Framework\Filesystem $magentoFrameworkFilesystem, 
        \Klevu\Search\Model\Api\Action\Debuginfo $apiActionDebuginfo, 
        \Klevu\Search\Model\Session $frameworkModelSession,
		\Klevu\Search\Helper\Data $searchHelperData,
		\Magento\Framework\Message\ManagerInterface $messageManager 
		)
    {

        parent::__construct($context);
		$this->_messageManager = $messageManager;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheState = $cacheState;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
		$this->_modelProductSync = $modelProductSync;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_apiActionDebuginfo = $apiActionDebuginfo;
        $this->_frameworkModelSession = $frameworkModelSession;
        //$this->_indexModelIndexer = $indexModelIndexer;
        $this->_searchHelperData = $searchHelperData;
		

    }
	

    public function execute() {
		$debugapi = $this->_modelProductSync->getApiDebug();
		
		// send last few lines of klevu log files
		$dir = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Filesystem\DirectoryList');  
        $logdir = $dir->getPath('log');
		$path = $logdir."/Klevu_Search.log";
        if($this->getRequest()->getParam('lines')) {
            $line = $this->getRequest()->getParam('lines'); 
        }else {
            $line = 100;
        }
		$content = "";
        $content.= $this->getLastlines($path,$line,true);
		
		// Get the all indexing status
		$indexer = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Indexer\Model\IndexerFactory')
		->create();
		$indexerCollection = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Indexer\Model\Indexer\CollectionFactory')->create();

		$ids = $indexerCollection->getAllIds();
		foreach ($ids as $id){
			$idx = $indexer->load($id);
			$content.= $idx->getTitle().":".$idx->getStatus(); 
		}
		
		$response = $this->_apiActionDebuginfo->debugKlevu(array('apiKey'=>$debugapi,'klevuLog'=>$content,'type'=>'index'));
		$this->messageManager->addSuccess( __('klevu debug data was sent to klevu server successfully.'));
        $this->_view->loadLayout();
		$this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }
	
	
	public function getLastlines($filepath, $lines, $adaptive = true) {
		
        // Open file
        $f = @fopen($filepath, "rb");
		
        if ($f === false) return false;
        // Sets buffer size
        if (!$adaptive) $buffer = 4096;
        else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
        // Jump to last character
        fseek($f, -1, SEEK_END);
        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if (fread($f, 1) != "\n") $lines -= 1;
        // Start reading
        $output = '';
        $chunk = '';
        // While we would like more
        while (ftell($f) > 0 && $lines >= 0) {
        // Figure out how far back we should jump
        $seek = min(ftell($f), $buffer);
        // Do the jump (backwards, relative to where we are)
        fseek($f, -$seek, SEEK_CUR);
        // Read a chunk and prepend it to our output
        $output = ($chunk = fread($f, $seek)) . $output;
        // Jump back to where we started reading
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
        // Decrease our line counter
        $lines -= substr_count($chunk, "\n");
        }
        // While we have too many lines
        // (Because of buffer size we might have read too many)
        while ($lines++ < 0) {
        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, "\n") + 1);
        }
        // Close file and return
        fclose($f);
        return trim($output);
    }
}