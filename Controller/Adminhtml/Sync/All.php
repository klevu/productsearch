<?php

namespace Klevu\Search\Controller\Adminhtml\Sync;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Klevu\Search\Model\Product\Sync as ProductSync;
use Klevu\Search\Model\Session;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class All extends Action
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var BackendSession
     */
    protected $_backendModelSession;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;
    /**
     * @var ProductSync
     */
    protected $_modelProductSync;
    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;
    /**
     * @var EventManagerInterface
     */
    protected $_frameworkEventManagerInterface;
    /**
     * @var RequestInterface
     */
    protected $_frameworkAppRequestInterface;
    /**
     * @var MagentoProductActionsInterface
     */
    protected $_magentoProductActions;
    /**
     * @var Session
     */
    protected $_klevuSync;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param ConfigHelper $searchHelperConfig
     * @param ProductSync $modelProductSync
     * @param SearchHelper $searchHelperData
     * @param MagentoProductActionsInterface $magentoProductActions
     * @param Session $klevuSync
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeModelStoreManagerInterface,
        ConfigHelper $searchHelperConfig,
        ProductSync $modelProductSync,
        SearchHelper $searchHelperData,
        MagentoProductActionsInterface $magentoProductActions,
        Session $klevuSync
    ) {
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_backendModelSession = $context->getSession();
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_modelProductSync = $modelProductSync;
        $this->_searchHelperData = $searchHelperData;
        $this->_frameworkEventManagerInterface = $context->getEventManager();
        $this->_magentoProductActions = $magentoProductActions;
        $this->_klevuSync = $klevuSync;

        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam("store");
        $website = null;
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore($storeId);
            $website = $store->getWebsite();
        } catch (NoSuchEntityException $e) {
            $this->_backendModelSession->addErrorMessage(__("Selected store could not be found!"));
            $this->_redirect($this->_redirect->getRefererUrl());
        }

        if ($this->_searchHelperConfig->isProductSyncEnabled((int)$store->getId())) {
            if ($this->_searchHelperConfig->getSyncOptionsFlag() === "2") {
                $this->_magentoProductActions->markAllProductsForUpdate($store);
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_INFO,
                    sprintf(
                        "Product Sync scheduled to re-sync ALL products in %s (%s).",
                        $website ? $website->getName() : '',
                        $store->getName()
                    )
                );
                $this->messageManager->addSuccessMessage(sprintf(
                    "Klevu Search Product Sync scheduled to be run on the next cron run for ALL products in %s (%s).",
                    $website ? $website->getName() : '',
                    $store->getName()
                ));
            } else {
                $this->syncWithoutCron();
            }
        } else {
            $this->messageManager->addErrorMessage(__("Klevu Search Product Sync is disabled."));
        }

        $this->_frameworkEventManagerInterface->dispatch('sync_all_external_data', [
            'store' => $store,
        ]);
        $this->_storeModelStoreManagerInterface->setCurrentStore(0);

        return $this->_redirect($this->_redirect->getRefererUrl());
    }

    /**
     * @return true
     */
    protected function _isAllowed()
    {
        return true;
    }

    /**
     * @return ResponseInterface
     */
    public function syncWithoutCron()
    {
        try {
            $store = $this->getRequest()->getParam("store");
            $onestore = $this->_storeModelStoreManagerInterface->getStore($store);
            if ($store !== null) {
                //Sync Data
                if (is_object($onestore)) {
                    $this->_modelProductSync->reset();
                    if (!$this->_modelProductSync->setupSession($onestore)) {
                        return null;
                    }
                    $this->_modelProductSync->syncData($onestore);
                    $this->_modelProductSync->runCategory($onestore);
                }
            } else {
                $this->_modelProductSync->run();
            }
            /* Use event For other content sync */
            $this->_frameworkEventManagerInterface->dispatch('content_data_to_sync', []);

            $this->_klevuSync->unsFirstSync();
            $this->messageManager->addSuccessMessage(__("Data updates have been sent to Klevu"));
        } catch (LocalizedException $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf(
                    "Error thrown while scheduling product sync %s",
                    $e->getMessage()
                )
            );
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
