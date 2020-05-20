<?php

namespace Klevu\Search\Controller\Index;

class Syncstoreview extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $_context;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\StateInterface $cacheState,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Filesystem $magentoFrameworkFilesystem,
        \Magento\Store\Model\StoreManagerInterface $storeInterface,
        \Klevu\Search\Model\Product\Sync $modelProductSync,
        \Klevu\Search\Model\Api\Action\Debuginfo $apiActionDebuginfo,
        \Klevu\Search\Model\Session $frameworkModelSession,
        \Klevu\Search\Helper\Data $searchHelperData,
		\Klevu\Search\Model\Sync $modelSync
    ) {

		parent::__construct($context);
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheState = $cacheState;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_storeInterface = $storeInterface;
        $this->_modelProductSync = $modelProductSync;
        $this->_apiActionDebuginfo = $apiActionDebuginfo;
        $this->_frameworkModelSession = $frameworkModelSession;
        $this->_searchHelperData = $searchHelperData;
		$this->_klevuSyncModel = $modelSync;
        $this->_context = $context;
		$this->_eventManager = $this->_context->getEventManager();
		$this->_request = $this->_context->getRequest();
    }
    
    public function execute()
    {
		if($this->_storeInterface->isSingleStoreMode())
		{
			$store_id = $this->_storeInterface->getStore()->getId();
		} else {
			//$store_id = $request->getParam('store');
            $store_id = $this->_request->getParam('store');
		}
		
		//$restapi = $request->getParam('restapi');
        $restapi = $this->_request->getParam('restapi');
	    $store_rest_api = $this->_klevuSyncModel->getHelper()->getConfigHelper()->getRestApiKey((int)$store_id);
		if($store_rest_api == $restapi) {
			$this->_storeInterface->setCurrentStore($store_id);
			$store_view = $this->_storeInterface->getStore($store_id);
            $this->_eventManager->dispatch('content_data_to_sync', []);
            $this->_klevuSyncModel->setSessionVariable("limit",500);
			$this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_add");
			$this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_delete");
			$this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_update");
			$this->_klevuSyncModel->getRegistry()->register("numberOfRecord_add",0);
			$this->_klevuSyncModel->getRegistry()->register("numberOfRecord_delete",0);
			$this->_klevuSyncModel->getRegistry()->register("numberOfRecord_update",0);
			$records_count = $this->_modelProductSync->syncStoreView($store_view);
			$result['numberOfRecord_add'] = $records_count["numberOfRecord_add"];
			$result['numberOfRecord_delete'] = $records_count["numberOfRecord_delete"];
			$result['numberOfRecord_update'] = $records_count["numberOfRecord_update"];
			$this->getResponse()->setHeader('Content-type', 'application/json');
			$this->getResponse()->setBody(json_encode($result));
		} else {
			return;
		}
		
    }
    
}