<?php

namespace Klevu\Search\Controller\Index;

use Klevu\Search\Helper\Data;
use Klevu\Search\Model\Api\Action\Debuginfo;
use Klevu\Search\Model\Product\Sync;
use Klevu\Search\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Result\PageFactory;

class Syncstore extends Action
{
    /**
     * @var TypeListInterface
     */
    private $_cacheTypeList;
    /**
     * @var StateInterface
     */
    private $_cacheState;
    /**
     * @var Pool
     */
    private $_cacheFrontendPool;
    /**
     * @var PageFactory
     */
    private $resultPageFactory;
    /**
     * @var Sync
     */
    private $_modelProductSync;
    /**
     * @var Filesystem
     */
    private $_magentoFrameworkFilesystem;
    /**
     * @var Debuginfo
     */
    private $_apiActionDebuginfo;
    /**
     * @var Session
     */
    private $_frameworkModelSession;
    /**
     * @var Data
     */
    private $_searchHelperData;

    /**
     * @param Context $context
     * @param TypeListInterface $cacheTypeList
     * @param StateInterface $cacheState
     * @param Pool $cacheFrontendPool
     * @param PageFactory $resultPageFactory
     * @param Sync $modelProductSync
     * @param Filesystem $magentoFrameworkFilesystem
     * @param Debuginfo $apiActionDebuginfo
     * @param Session $frameworkModelSession
     * @param Data $searchHelperData
     */
    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        StateInterface $cacheState,
        Pool $cacheFrontendPool,
        PageFactory $resultPageFactory,
        Sync $modelProductSync,
        Filesystem $magentoFrameworkFilesystem,
        Debuginfo $apiActionDebuginfo,
        Session $frameworkModelSession,
        Data $searchHelperData
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
        $this->_searchHelperData = $searchHelperData;
    }

    /**
     * Store wise sync from the admin page upon clicking 'Sync Updates Only for This Store' button
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
