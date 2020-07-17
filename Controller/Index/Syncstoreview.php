<?php

namespace Klevu\Search\Controller\Index;

/**
 * Class Syncstoreview
 * @package Klevu\Search\Controller\Index
 */
class Syncstoreview extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\App\Action\Context
     */
    protected $_context;

    /**
     * Syncstoreview constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Filesystem $magentoFrameworkFilesystem
     * @param \Magento\Store\Model\StoreManagerInterface $storeInterface
     * @param \Klevu\Search\Model\Product\Sync $modelProductSync
     * @param \Klevu\Search\Model\Api\Action\Debuginfo $apiActionDebuginfo
     * @param \Klevu\Search\Model\Session $frameworkModelSession
     * @param \Klevu\Search\Helper\Data $searchHelperData
     * @param \Klevu\Search\Model\Sync $modelSync
     */
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
		\Magento\Store\Model\StoreManagerInterface $storeInterface,
        \Klevu\Content\Model\ContentInterface $modelSyncContent
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
		$this->_klevuSyncContent = $modelSyncContent;
		$this->_context = $context;
        $this->_request = $this->_context->getRequest();
    }
    
    public function execute()
    {
        try {
            if ($this->_storeInterface->isSingleStoreMode()) {
                $store_id = (int)$this->_storeInterface->getStore()->getId();
            } else {
                $store_id = (int)$this->_request->getParam('store');
            }

            $store_object = $this->_storeInterface->getStore($store_id);
            $hashkey = urlencode($this->_request->getParam('hashkey'));
            $store_rest_api = hash('sha256',$this->_klevuSyncModel->getHelper()->getConfigHelper()->getRestApiKey((int)$store_id));
            if ($store_rest_api == $hashkey) {
                $this->_storeInterface->setCurrentStore($store_id);
                $this->_klevuSyncContent->syncCmsData($store_object);
                $this->_klevuSyncModel->setSessionVariable("limit", 500);
                $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_add");
                $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_delete");
                $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_update");
                $this->_klevuSyncModel->getRegistry()->register("numberOfRecord_add", 0);
                $this->_klevuSyncModel->getRegistry()->register("numberOfRecord_delete", 0);
                $this->_klevuSyncModel->getRegistry()->register("numberOfRecord_update", 0);
                $records_count = $this->_modelProductSync->syncStoreView($store_object);
                $result['numberOfRecord_add'] = $records_count["numberOfRecord_add"];
                $result['numberOfRecord_delete'] = $records_count["numberOfRecord_delete"];
                $result['numberOfRecord_update'] = $records_count["numberOfRecord_update"];
            } else {
                $result['msg'] = __("Rest API key not found for requested store.");
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $result['msg'] = __("No Such Entity found  : " . $e->getMessage());
        } catch (\Exception $e) {
            $result['msg'] = __("Exception thrown : " . $e->getMessage());
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($result));
    }

}

