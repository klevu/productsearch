<?php

namespace Klevu\Search\Controller\Adminhtml\Wizard\Store;

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

    public function execute()
    {

        $request = $this->getRequest();

        if (!$request->isPost() || !$request->isAjax()) {
            return $this->_redirect("adminhtml/dashboard");
        }

        $config = $this->_searchHelperConfig;
        $api = $this->_searchHelperApi;
        $session = $this->_searchModelSession;
        $customer_id = $session->getConfiguredCustomerId();

        if (!$customer_id) {
            $this->messageManager->addErrorMessage(__("You must configure a user first."));
            return $this->_redirect("*/*/configure_user");
        }

        $store_code = $request->getPost("store");
        if (strlen($store_code) == 0) {
            $this->messageManager->addErrorMessage(__("Must select a store"));
            return $this->_forward("store");
        }

        try {
            $store = $this->_storeModelStoreManagerInterface->getStore($store_code);
        } catch (\Magento\Framework\Model\Store\Exception $e) {
            $this->messageManager->addErrorMessage(__("Selected store does not exist."));
            return $this->_forward("store");
        }

        // Setup the live and test Webstores
            $result = $api->createWebstore($customer_id, $store);
        if ($result["success"]) {
            $config->setJsApiKey($result["webstore"]->getJsApiKey(), $store);
            $config->setRestApiKey($result["webstore"]->getRestApiKey(), $store);
            $config->setHostname($result["webstore"]->getHostedOn(), $store);
            $config->setCloudSearchUrl($result['webstore']->getCloudSearchUrl(), $store);
            $config->setAnalyticsUrl($result['webstore']->getAnalyticsUrl(), $store);
            $config->setJsUrl($result['webstore']->getJsUrl(), $store);
            $config->setRestHostname($result['webstore']->getRestHostname(), $store);
            $config->setTiresUrl($result['webstore']->getTiresUrl(), $store);
			$config->saveRatingUpgradeFlag(0,$store);
			$config->resetConfig();
            if (isset($result["message"])) {
                $this->messageManager->addSuccessMessage(__($result["message"]));	
                $this->_searchModelSession->setFirstSync($store_code);
            }
        } else {
            $this->messageManager->addErrorMessage(__($result["message"]));
            return $this->_forward("store");
        }
        $this->messageManager->addSuccessMessage("Store configured successfully. Saved API credentials.");

        $config->setTaxEnabledFlag((int)$request->getPost("tax_enable"), $store);
        $config->setSecureUrlEnabledFlag((int)$request->getPost("secureurl_setting"), $store);
        $config->saveUseCollectionMethodFlag((int)$request->getPost("use_collection_method"));
		$config->resetConfig();
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();

        // Clear Product Sync and Order Sync data for the newly configured store
        $this->_magentoProductActions->clearAllProducts($store);
        //$this->_modelOrderSync->clearQueue($store);

        $session->setConfiguredStoreCode($store_code);

        $this->messageManager->addSuccessMessage("Store configured successfully. Saved API credentials.");
		
		if($config->isExternalCronEnabled()) {
			// Schedule a Product Sync
			$this->_modelProductSync->schedule();
		}
    }
}
