<?php

namespace Klevu\Search\Model\Observer;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Klevu\Search\Model\Product\Sync as ProductSync;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;

class SyncAllProducts implements ObserverInterface
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
     * @var MagentoProductActionsInterface
     */
    protected $_magentoProductActions;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ProductSync $modelProductSync
     * @param Filesystem $magentoFrameworkFilesystem
     * @param SearchHelper $searchHelperData
     * @param ConfigHelper $searchHelperConfig
     * @param MagentoProductActionsInterface $magentoProductActions
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        ProductSync $modelProductSync,
        Filesystem $magentoFrameworkFilesystem,
        SearchHelper $searchHelperData,
        ConfigHelper $searchHelperConfig,
        MagentoProductActionsInterface $magentoProductActions,
        StoreManagerInterface $storeManager = null
    ) {
        $this->_modelProductSync = $modelProductSync;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_searchHelperData = $searchHelperData;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_magentoProductActions = $magentoProductActions;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    /**
     * Mark all of the products for update and then schedule a sync
     * to run immediately.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $store = null;

        $attribute = $observer->getEvent()->getAttribute();
        if ($attribute instanceof \Magento\Catalog\Model\ResourceModel\Eav\Attribute) {
            // On attribute change, sync only if the attribute was added
            // or removed from layered navigation
            $originalIsFilterable = $attribute->getOrigData("is_filterable_in_search");
            $updatedIsFilterable = $attribute->getData("is_filterable_in_search");
            if ($originalIsFilterable === $updatedIsFilterable) {
                return;
            }
        }
        if ($observer->getEvent()->getStore()) {
            // Only sync products for a specific store if the event was fired in that store
            try {
                $store = $this->storeManager->getStore($observer->getEvent()->getStore());
            } catch (NoSuchEntityException $e) {
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_ERR,
                    sprintf('%s: %s', __METHOD__, $e->getMessage())
                );
            }
        }
        $this->_magentoProductActions->markAllProductsForUpdate($store);

        if ($this->_searchHelperConfig->isExternalCronEnabled()) {
            $this->_modelProductSync->schedule();
        }
    }
}
