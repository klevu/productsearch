<?php
namespace Klevu\Search\Observer\Backend;

use Magento\Framework\App\RequestInterface as RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
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
    )
    {
        $this->_klevuHelperManager = $klevuHelperManager;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
    }

    public function execute(EventObserver $observer)
    {
		$klevuDataHelper = $this->_klevuHelperManager->getDataHelper();
        try {
            $isSingleStoreMode = $this->_storeManager->isSingleStoreMode();
            $klevuConfig = $this->_klevuHelperManager->getConfigHelper();            

            $actionFlag = FALSE;
            if( $this->_request->getFullActionName() == 'adminhtml_system_config_edit' &&
                $this->_request->getParam('section') == 'klevu_search' ) {
                $actionFlag = TRUE;
            }
            if(!$isSingleStoreMode || !$klevuConfig->getModuleInfo() || !$actionFlag) {
                return;
            }

            $jsApiValue = $klevuConfig->getJsApiKey( $this->_storeManager->getStore() );
            $jsRestValue = $klevuConfig->getRestApiKey( $this->_storeManager->getStore() );
            $cloudSearchURL = $klevuConfig->getCloudSearchUrl( $this->_storeManager->getStore() );
            $analyticsURL = $klevuConfig->getAnalyticsUrl( $this->_storeManager->getStore() );
            $restHostName = $klevuConfig->getRestHostname( $this->_storeManager->getStore() );

            $klevuConfig->setGlobalConfig( $klevuConfig::XML_PATH_JS_API_KEY , $jsApiValue );
            $klevuConfig->setGlobalConfig( $klevuConfig::XML_PATH_REST_API_KEY , $jsRestValue );
            $klevuConfig->setGlobalConfig( $klevuConfig::XML_PATH_CLOUD_SEARCH_URL , $cloudSearchURL );
            $klevuConfig->setGlobalConfig( $klevuConfig::XML_PATH_ANALYTICS_URL , $analyticsURL );
			$klevuConfig->setGlobalConfig( $klevuConfig::XML_PATH_RESTHOSTNAME , $restHostName );

        } catch (\Exception $e) {
            $klevuDataHelper->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown for single store view %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            return;
        }
    }
}