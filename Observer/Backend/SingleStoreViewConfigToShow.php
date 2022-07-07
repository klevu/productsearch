<?php

namespace Klevu\Search\Observer\Backend;

use Klevu\Logger\Constants as LoggerConstants;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;

class SingleStoreViewConfigToShow implements ObserverInterface
{
    private $_klevuHelperManager;
    private $_storeManager;
    private $_request;

    public function __construct(
        Klevu_HelperManager $klevuHelperManager,
        StoreManagerInterface $storeManager,
        RequestInterface $request
    ) {
        $this->_klevuHelperManager = $klevuHelperManager;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
    }

    /**
     * @param EventObserver $observer
     *
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        try {
            if (!($this->_storeManager->isSingleStoreMode())) {
                return;
            }
            if (
                $this->_request->getFullActionName() !== 'adminhtml_system_config_edit' ||
                $this->_request->getParam('section') !== 'klevu_search'
            ) {
                return;
            }
            $klevuConfig = $this->_klevuHelperManager->getConfigHelper();
            if (!$klevuConfig->getModuleInfo()) {
                return;
            }
            try {
                $store = $this->_storeManager->getStore();
            } catch (NoSuchEntityException $e) {
                return;
            }
            $klevuConfig->setGlobalConfig(
                $klevuConfig::XML_PATH_JS_API_KEY,
                $klevuConfig->getJsApiKey($store)
            );
            $klevuConfig->setGlobalConfig(
                $klevuConfig::XML_PATH_REST_API_KEY,
                $klevuConfig->getRestApiKey($store)
            );
            $klevuConfig->setGlobalConfig(
                $klevuConfig::XML_PATH_CLOUD_SEARCH_URL,
                $klevuConfig->getCloudSearchUrl($store)
            );
            $klevuConfig->setGlobalConfig(
                $klevuConfig::XML_PATH_ANALYTICS_URL,
                $klevuConfig->getAnalyticsUrl($store)
            );
            $klevuConfig->setGlobalConfig(
                $klevuConfig::XML_PATH_RESTHOSTNAME,
                $klevuConfig->getRestHostname($store)
            );
        } catch (\Exception $e) {
            $this->_klevuHelperManager->getDataHelper()->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf("Exception thrown for single store view %s::%s - %s",
                    __CLASS__, __METHOD__, $e->getMessage()
                )
            );
        }
    }
}
