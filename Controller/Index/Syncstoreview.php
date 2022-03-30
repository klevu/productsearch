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

/**
 * Class Syncstoreview
 * @package Klevu\Search\Controller\Index
 */
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
    private $_storeInterface;
    /**
     * @var ContentInterface
     */
    private $_klevuSyncContent;
    /**
     * @var Context
     */
    protected $_context;

    /**
     * Sync store view constructor.
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
        Context               $context,
        TypeListInterface     $cacheTypeList,
        StateInterface        $cacheState,
        Pool                  $cacheFrontendPool,
        PageFactory           $resultPageFactory,
        Sync                  $modelProductSync,
        Filesystem            $magentoFrameworkFilesystem,
        Debuginfo             $apiActionDebuginfo,
        Session               $frameworkModelSession,
        Data                  $searchHelperData,
        Klevu_ModelSync       $modelSync,
        StoreManagerInterface $storeInterface,
        ContentInterface      $modelSyncContent
    )
    {

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

    /**
     * Triggers ajax call from the search/index/syncstore action
     * @return ResponseInterface|ResultInterface|void
     */
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
            $store_rest_api = hash('sha256', (string)$this->_klevuSyncModel->getHelper()->getConfigHelper()->getRestApiKey((int)$store_id));
            if ($store_rest_api == $hashkey) {
                $this->_storeInterface->setCurrentStore($store_id);
                $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, sprintf(
                    "Updates only data sync action performed from Magento Admin Panel %s (%s).",
                    $store_object->getWebsite()->getName(),
                    $store_object->getName()
                ));
                $this->_klevuSyncContent->syncCmsData($store_object);
                $this->_klevuSyncModel->setSessionVariable("limit", 500);
                $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_add");
                $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_delete");
                $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_update");
                $this->_klevuSyncModel->getRegistry()->register("numberOfRecord_add", 0);
                $this->_klevuSyncModel->getRegistry()->register("numberOfRecord_delete", 0);
                $this->_klevuSyncModel->getRegistry()->register("numberOfRecord_update", 0);
                $records_count = $this->_modelProductSync->syncStoreView($store_object);
                $result['numberOfRecord_add'] = isset($records_count["numberOfRecord_add"]) ? $records_count["numberOfRecord_add"] : null;
                $result['numberOfRecord_delete'] = isset($records_count["numberOfRecord_delete"]) ? $records_count["numberOfRecord_delete"] : null;
                $result['numberOfRecord_update'] = isset($records_count["numberOfRecord_update"]) ? $records_count["numberOfRecord_update"] : null;
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

