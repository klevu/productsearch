<?php

namespace Klevu\Search\Controller\Index;

use Magento\CatalogSearch\Helper\Data as CatalogSearchHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Cache\Frontend\Pool as CacheFrontendPool;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory as ResultPageFactory;

class Index extends Action
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_magentoFrameworkUrlInterface;
    /**
     * @var TypeListInterface
     */
    protected $_cacheTypeList;
    /**
     * @var StateInterface
     */
    protected $_cacheState;
    /**
     * @var CacheFrontendPool
     */
    protected $_cacheFrontendPool;
    /**
     * @var ResultPageFactory
     */
    protected $resultPageFactory;
    /**
     * @var CatalogSearchHelper
     */
    protected $_catalogSearchHelper;

    /**
     * @param Context $context
     * @param TypeListInterface $cacheTypeList
     * @param StateInterface $cacheState
     * @param CacheFrontendPool $cacheFrontendPool
     * @param ResultPageFactory $resultPageFactory
     * @param CatalogSearchHelper $catalogSearchHelper
     */
    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        StateInterface $cacheState,
        CacheFrontendPool $cacheFrontendPool,
        ResultPageFactory $resultPageFactory,
        CatalogSearchHelper $catalogSearchHelper
    ) {
        parent::__construct($context);
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheState = $cacheState;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
        $this->_catalogSearchHelper = $catalogSearchHelper;
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $query = $this->_catalogSearchHelper->getEscapedQueryText();
        $this->_view->loadLayout();
        $page = $this->_view->getPage();
        $config = $page->getConfig();
        $title = $config->getTitle();
        $title->set(__("Search results for: '%1'", $query));
        $this->_view->renderLayout();
    }
}
