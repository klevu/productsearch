<?php

namespace Klevu\Search\Controller\Index;

class Syncstore extends \Magento\Framework\App\Action\Action
{

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Klevu\Search\Model\Product\Sync $modelProductSync,
        \Magento\Framework\Filesystem $magentoFrameworkFilesystem,
        \Klevu\Search\Model\Api\Action\Debuginfo $apiActionDebuginfo,
        \Klevu\Search\Model\Session $frameworkModelSession,
        \Klevu\Search\Helper\Data $searchHelperData
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
    
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
    
}
