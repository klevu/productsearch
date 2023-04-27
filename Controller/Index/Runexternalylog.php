<?php

namespace Klevu\Search\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Cache\Frontend\Pool as CacheFrontendPool;
use Magento\Framework\App\Cache\StateInterface as CacheStateInterface;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeListInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;

class Runexternalylog extends Action
{
    /**
     * @var UrlInterface
     */
    protected $_magentoFrameworkUrlInterface;
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
     * @param Context $context
     * @param CacheTypeListInterface $cacheTypeList
     * @param CacheStateInterface $cacheState
     * @param CacheFrontendPool $cacheFrontendPool
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        CacheTypeListInterface $cacheTypeList,
        CacheStateInterface $cacheState,
        CacheFrontendPool $cacheFrontendPool,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheState = $cacheState;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
