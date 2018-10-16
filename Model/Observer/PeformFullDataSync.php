<?php

/**
 * Class \Klevu\Search\Model\Observer
 *
 * @method setIsProductSyncScheduled($flag)
 * @method bool getIsProductSyncScheduled()
 */
namespace Klevu\Search\Model\Observer;
 
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Layout\Interceptor;

class PeformFullDataSync implements ObserverInterface
{

     /**
      * @var \Klevu\Search\Model\Product\Sync
      */
    protected $_modelProductSync;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_magentoFrameworkFilesystem;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $_modelProductAction;
    
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_frameworkModelResource;

    public function __construct(
        \Klevu\Search\Model\Product\Sync $modelProductSync,
        \Magento\Framework\Filesystem $magentoFrameworkFilesystem,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        \Magento\Framework\App\ResourceConnection $frameworkModelResource,
		\Magento\Framework\App\Request\Http $request,
		\Klevu\Search\Model\Product\MagentoProductActionsInterface $magentoProductActions
    ) {
    
        $this->_modelProductSync = $modelProductSync;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_frameworkModelResource = $frameworkModelResource;
		$this->_magentoProductActions = $magentoProductActions;
		$this->request = $request;
    }

    /**
     * When products are updated in bulk, update products so that they will be synced.
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
		$store = $this->request->getParam("store");
		if ($store !== null) {
			$config_state = $this->request->getParam('groups');
			if(isset($config_state['tax_setting'])){
                $value_tax = $config_state['tax_setting']['fields']['enabled']['value'];
                $new_value = (int)$value_tax?true:false;
                if($this->_searchHelperConfig->isTaxEnabled($store) !== $new_value){
                    $this->_magentoProductActions->markAllProductsForUpdate($store);
                }
            }

            if(isset($config_state['secureurl_setting'])) {
                $value_secureurl = $config_state['secureurl_setting']['fields']['enabled']['value'];
                $new_value_secureurl = (int)$value_secureurl?true:false;
                if($this->_searchHelperConfig->isSecureUrlEnabled($store) !== $new_value_secureurl){
                    $this->_magentoProductActions->markAllProductsForUpdate($store);
                }
            }

            if(isset($config_state['image_setting'])) {
                $value_image_setting = $config_state['image_setting']['fields']['enabled']['value'];
                $new_value_image_setting = (int)$value_image_setting ? true : false;
                if ($this->_searchHelperConfig->isUseConfigImage($store) !== $new_value_image_setting) {
                    $this->_magentoProductActions->markAllProductsForUpdate($store);
                }
            }
		}

    }
}
