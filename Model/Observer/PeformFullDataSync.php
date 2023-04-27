<?php

namespace Klevu\Search\Model\Observer;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Klevu\Search\Model\Product\Sync as ProductSync;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem;

class PeformFullDataSync implements ObserverInterface
{
    /**
     * @var ProductSync
     */
    protected $_modelProductSync;
    /**
     * @var Filesystem
     */
    protected $_magentoFrameworkFilesystem;
    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;
    /**
     * @var ProductAction
     */
    protected $_modelProductAction;
    /**
     * @var ResourceConnection
     */
    protected $_frameworkModelResource;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;
    /**
     * @var MagentoProductActionsInterface
     */
    protected $_magentoProductActions;
    /**
     * @var HttpRequest
     */
    protected $request;

    /**
     * @param ProductSync $modelProductSync
     * @param Filesystem $magentoFrameworkFilesystem
     * @param ConfigHelper $searchHelperConfig
     * @param ResourceConnection $frameworkModelResource
     * @param HttpRequest $request
     * @param MagentoProductActionsInterface $magentoProductActions
     */
    public function __construct(
        ProductSync $modelProductSync,
        Filesystem $magentoFrameworkFilesystem,
        ConfigHelper $searchHelperConfig,
        ResourceConnection $frameworkModelResource,
        HttpRequest $request,
        MagentoProductActionsInterface $magentoProductActions
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
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $store = $this->request->getParam("store");
        if ($store === null) {
            return;
        }
        $config_state = $this->request->getParam('groups');
        if (isset($config_state['tax_setting']['fields']['enabled']['value'])) {
            $value_tax = $config_state['tax_setting']['fields']['enabled']['value'];
            $new_value = (bool)((int)$value_tax);
            if ($this->_searchHelperConfig->isTaxEnabled($store) !== $new_value) {
                $this->_magentoProductActions->markAllProductsForUpdate($store);
            }
        }

        if (isset($config_state['secureurl_setting']['fields']['enabled']['value'])) {
            $value_secureurl = $config_state['secureurl_setting']['fields']['enabled']['value'];
            $new_value_secureurl = (bool)((int)$value_secureurl);
            if ($this->_searchHelperConfig->isSecureUrlEnabled($store) !== $new_value_secureurl) {
                $this->_magentoProductActions->markAllProductsForUpdate($store);
            }
        }

        if (isset($config_state['image_setting']['fields']['enabled']['value'])) {
            $value_image_setting = $config_state['image_setting']['fields']['enabled']['value'];
            $new_value_image_setting = (bool)((int)$value_image_setting);
            if ($this->_searchHelperConfig->isUseConfigImage($store) !== $new_value_image_setting) {
                $this->_magentoProductActions->markAllProductsForUpdate($store);
            }
        }
    }
}
