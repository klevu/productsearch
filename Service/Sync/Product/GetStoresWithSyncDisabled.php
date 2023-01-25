<?php

namespace Klevu\Search\Service\Sync\Product;

use Klevu\Search\Api\Service\Sync\Product\GetStoresWithSyncDisabledInterface;
use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class GetStoresWithSyncDisabled implements GetStoresWithSyncDisabledInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @return string[]
     */
    public function execute()
    {
        $disabledStores = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            if ($this->isSyncEnabled($store)) {
                continue;
            }
            $disabledStores[$store->getId()] = $store->getName() . ' (' . $store->getCode() . ')';
        }

        return $disabledStores;
    }

    /**
     * @param StoreInterface $store
     *
     * @return bool
     */
    private function isSyncEnabled(StoreInterface $store)
    {
        return $this->scopeConfig->isSetFlag(
            ConfigHelper::XML_PATH_PRODUCT_SYNC_ENABLED,
            ScopeInterface::SCOPE_STORES,
            $store->getId()
        );
    }
}
