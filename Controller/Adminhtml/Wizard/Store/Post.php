<?php

namespace Klevu\Search\Controller\Adminhtml\Wizard\Store;

use Magento\Framework\Controller\Result\Forward as ResultForward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Post extends \Magento\Backend\App\Action
{
    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;

    /**
     * @var \Klevu\Search\Helper\Api
     */
    protected $_searchHelperApi;

    /**
     * @var \Klevu\Search\Model\Session
     */
    protected $_searchModelSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    /**
     * @var \Klevu\Search\Model\Product\Sync
     */
    protected $_modelProductSync;

    /**
     * @var \Klevu\Search\Model\Order\Sync
     */
    protected $_modelOrderSync;

	/**
     * @var \Klevu\Search\Model\Product\MagentoProductActionsInterface
     */
    protected $_magentoProductActions;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        \Klevu\Search\Helper\Api $searchHelperApi,
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Klevu\Search\Model\Product\Sync $modelProductSync,
        \Klevu\Search\Model\Order\Sync $modelOrderSync,
		\Klevu\Search\Model\Product\MagentoProductActionsInterface $magentoProductActions
    ) {

        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_searchHelperApi = $searchHelperApi;
        $this->_searchModelSession = $context->getSession();
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_modelProductSync = $modelProductSync;
        $this->_modelOrderSync = $modelOrderSync;
		$this->_magentoProductActions = $magentoProductActions;

        parent::__construct($context);
    }

    /**
     * Show 404 error page
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var ResultForward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        $resultForward->forward('noroute');

        return $resultForward;
    }
}
