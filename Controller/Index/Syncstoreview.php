<?php

namespace Klevu\Search\Controller\Index;

class Syncstoreview extends \Magento\Framework\App\Action\Action
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
        \Klevu\Search\Helper\Data $searchHelperData,
		\Klevu\Search\Model\Sync $modelSync,
		\Magento\Store\Model\StoreManagerInterface $storeInterface
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
		$this->_klevuSyncModel = $modelSync;
		$this->_storeInterface = $storeInterface;
    }
    
    public function execute()
    {
		$om = \Magento\Framework\App\ObjectManager::getInstance();
		$request = $om->get('Magento\Framework\App\RequestInterface');
		if($this->_storeInterface->isSingleStoreMode())
		{
			$store_id = $this->_storeInterface->getStore()->getId();
		} else {
			$store_id = $request->getParam('store');
		}
		
		$restapi = $request->getParam('restapi');
	    $store_rest_api = $this->_klevuSyncModel->getHelper()->getConfigHelper()->getRestApiKey($store_id);
		if($store_rest_api == $restapi) {
			$store_view = $this->_storeInterface->getStore($store_id);
			\Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Framework\Event\ManagerInterface')->dispatch('content_data_to_sync', []);
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