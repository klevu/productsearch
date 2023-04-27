<?php

namespace Klevu\Search\Controller\Index;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Api\Action\Debuginfo as ApiActionDebuginfo;
use Klevu\Search\Model\Product\Sync as ProductSync;
use Klevu\Search\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Cache\Frontend\Pool as CacheFrontendPool;
use Magento\Framework\App\Cache\StateInterface as CacheStateInterface;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeListInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\DriverInterface as FileSystemDriverInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Indexer\Model\Indexer\CollectionFactory as IndexerCollectionFactory;
use Magento\Indexer\Model\IndexerFactory;

class Runexternaly extends \Magento\Framework\App\Action\Action
{
    /**
     * @var IndexerFactory
     */
    protected $_indexerFactory;
    /**
     * @var IndexerCollectionFactory
     */
    protected $_indexerCollectionFactory;
    /**
     * @var CacheTypeListInterface
     */
    protected $_cacheTypeList;
    /**
     * @var CacheStateInterface
     */
    protected $_cacheState;
    /**
     * @var CacheFrontendPool
     */
    protected $_cacheFrontendPool;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var ProductSync
     */
    protected $_modelProductSync;
    /**
     * @var Filesystem
     */
    protected $_magentoFrameworkFilesystem;
    /**
     * @var ApiActionDebuginfo
     */
    protected $_apiActionDebuginfo;
    /**
     * @var Session
     */
    protected $_frameworkModelSession;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;
    /**
     * @var DirectoryList
     */
    protected $_directoryList;
    /**
     * @var FileSystemDriverInterface|null
     */
    private $fileSystemDriver;

    /**
     * @param Context $context
     * @param CacheTypeListInterface $cacheTypeList
     * @param CacheStateInterface $cacheState
     * @param CacheFrontendPool $cacheFrontendPool
     * @param PageFactory $resultPageFactory
     * @param ProductSync $modelProductSync
     * @param Filesystem $magentoFrameworkFilesystem
     * @param ApiActionDebuginfo $apiActionDebuginfo
     * @param Session $frameworkModelSession
     * @param ConfigHelper $searchHelperConfig
     * @param DirectoryList $directoryList
     * @param IndexerFactory $indexerFactory
     * @param IndexerCollectionFactory $indexerCollectionFactory
     * @param FileSystemDriverInterface|null $fileSystemDriver
     */
    public function __construct(
        Context $context,
        CacheTypeListInterface $cacheTypeList,
        CacheStateInterface $cacheState,
        CacheFrontendPool $cacheFrontendPool,
        PageFactory $resultPageFactory,
        ProductSync $modelProductSync,
        Filesystem $magentoFrameworkFilesystem,
        ApiActionDebuginfo $apiActionDebuginfo,
        Session $frameworkModelSession,
        ConfigHelper $searchHelperConfig,
        DirectoryList $directoryList,
        IndexerFactory $indexerFactory,
        IndexerCollectionFactory $indexerCollectionFactory,
        FileSystemDriverInterface $fileSystemDriver = null
    ) {
        parent::__construct($context);
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheState = $cacheState;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
        $this->_modelProductSync = $modelProductSync;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_apiActionDebuginfo = $apiActionDebuginfo;
        $this->_frameworkModelSession = $frameworkModelSession;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_directoryList = $directoryList;
        $this->_indexerFactory = $indexerFactory;
        $this->_indexerCollectionFactory = $indexerCollectionFactory;
        $this->fileSystemDriver = $fileSystemDriver ?: ObjectManager::getInstance()->get(FileDriver::class);
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $restAPI = $this->_searchHelperConfig->getRestApiKey();
        $debugapi = $this->_modelProductSync->getApiDebug();
        $line = 100;

        // send last few lines of klevu log files
        $logdir = $this->_directoryList->getPath('log');
        $path = $logdir . "/Klevu_Search.log";
        if ($this->getRequest()->getParam('lines')) {
            $line = $this->getRequest()->getParam('lines');
        } elseif ($this->getRequest()->getParam('sync')) {
            if ($this->getRequest()->getParam('sync') == 1) {
                $this->_modelProductSync->run();
                $this->getResponse()->setBody("Data has been sent to klevu server");

                return;
            }
        } else {
            $line = 100;
        }
        $content = "";
        $content .= $this->getLastlines($path, $line, true);
        $content .= "</br>";
        // Get the all indexing status
        $indexer = $this->_indexerFactory->create();
        $indexerCollection = $this->_indexerCollectionFactory->create();

        $ids = $indexerCollection->getAllIds();
        foreach ($ids as $id) {
            $idx = $indexer->load($id);
            $content .= "</br>" . $idx->getTitle() . ":" . $idx->getStatus();
        }

        $response = $this->_apiActionDebuginfo->debugKlevu([
            'apiKey' => $restAPI,
            'klevuLog' => $content,
            'type' => 'index',
        ]);

        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }

    /**
     * @param string $filepath
     * @param int $lines
     * @param bool $adaptive
     *
     * @return false|string
     * @throws FileSystemException
     */
    public function getLastlines($filepath, $lines, $adaptive = true)
    {
        // Open file
        try {
            $f = $this->fileSystemDriver->fileOpen($filepath, "rb");
        } catch (FileSystemException $e) {
            return false;
        }
        // Sets buffer size
        if (!$adaptive) {
            $buffer = 4096;
        } else {
            $bigBuffer = $lines < 10 ? 512 : 4096;
            $buffer = $lines < 2 ? 64 : $bigBuffer;
        }
        // Jump to last character
        $this->fileSystemDriver->fileSeek($f, -1, SEEK_END);
        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if ($this->fileSystemDriver->fileRead($f, 1) !== "\n") {
            --$lines;
        }
        // Start reading
        $output = '';
        $chunk = '';
        // While we would like more
        while ($this->fileSystemDriver->fileTell($f) > 0 && $lines >= 0) {
            // Figure out how far back we should jump
            $seek = min($this->fileSystemDriver->fileTell($f), $buffer);
            // Do the jump (backwards, relative to where we are)
            $this->fileSystemDriver->fileSeek($f, -$seek, SEEK_CUR);
            // Read a chunk and prepend it to our output
            $output = ($chunk = $this->fileSystemDriver->fileRead($f, $seek)) . $output;
            // Jump back to where we started reading
            $this->fileSystemDriver->fileSeek($f, -mb_strlen((string)$chunk, '8bit'), SEEK_CUR);
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
        $this->fileSystemDriver->fileClose($f);

        return trim($output);
    }
}
