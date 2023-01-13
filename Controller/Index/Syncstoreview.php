<?php

namespace Klevu\Search\Controller\Index;

use Klevu\Content\Model\ContentInterface;
use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Data;
use Klevu\Search\Model\Api\Action\Debuginfo;
use Klevu\Search\Model\Product\Sync;
use Klevu\Search\Model\Session;
use Klevu\Search\Model\Sync as Klevu_ModelSync;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

class Syncstoreview extends Action
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
     * @var Klevu_ModelSync
     */
    private $_klevuSyncModel;
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var ContentInterface
     */
    private $_klevuSyncContent;
    /**
     * @var Context
     */
    protected $_context;

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
     * @param Klevu_ModelSync $modelSync
     * @param StoreManagerInterface $storeInterface
     * @param ContentInterface $modelSyncContent
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
        Data $searchHelperData,
        Klevu_ModelSync $modelSync,
        StoreManagerInterface $storeInterface,
        ContentInterface $modelSyncContent
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
        $this->_storeManager = $storeInterface;
        $this->_klevuSyncContent = $modelSyncContent;
        $this->_context = $context;
        $this->_request = $this->_context->getRequest();
    }

    /**
     * Triggers ajax call from the search/index/syncstore action
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        try {
            if ($this->_storeManager->isSingleStoreMode()) {
                $singleStore = $this->_storeManager->getStore();
                $storeId = (int)$singleStore->getId();
            } else {
                $storeId = (int)$this->_request->getParam('store');
            }
            $store = $this->_storeManager->getStore($storeId);
            $hashKey = urlencode($this->_request->getParam('hashkey'));

            $helperManager = $this->_klevuSyncModel->getHelper();
            $configHelper = $helperManager->getConfigHelper();
            $restApiKey = $configHelper->getRestApiKey((int)$storeId);
            $storeRestApi = $restApiKey ? hash('sha256', (string)$restApiKey) : '';

            if ($storeRestApi === $hashKey) {
                $this->_storeManager->setCurrentStore($storeId);
                $website = $store->getWebsite();
                $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, sprintf(
                    "Updates only data sync action performed from Magento Admin Panel %s (%s).",
                    $website->getName(),
                    $store->getName()
                ));
                $this->_klevuSyncContent->syncCmsData($store);
                $this->_klevuSyncModel->setSessionVariable("limit", 500);
                $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_add");
                $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_delete");
                $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_update");
                $this->_klevuSyncModel->getRegistry()->register("numberOfRecord_add", 0);
                $this->_klevuSyncModel->getRegistry()->register("numberOfRecord_delete", 0);
                $this->_klevuSyncModel->getRegistry()->register("numberOfRecord_update", 0);
                $recordsCount = $this->_modelProductSync->syncStoreView($store);
                $result['numberOfRecord_add'] = isset($recordsCount["numberOfRecord_add"])
                    ? $recordsCount["numberOfRecord_add"]
                    : null;
                $result['numberOfRecord_delete'] = isset($recordsCount["numberOfRecord_delete"])
                    ? $recordsCount["numberOfRecord_delete"]
                    : null;
                $result['numberOfRecord_update'] = isset($recordsCount["numberOfRecord_update"])
                    ? $recordsCount["numberOfRecord_update"]
                    : null;
            } else {
                $result['msg'] = __("Rest API key not found for requested store.");
            }
        } catch (NoSuchEntityException $e) {
            $result['msg'] = __("No Such Entity found  : " . $e->getMessage());
        } catch (\Exception $e) {
            $result['msg'] = __("Exception thrown : " . $e->getMessage());
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($result));
    }
}
