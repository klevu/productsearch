<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration;

use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Backend\Block\Template;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class AuthKeys extends Template
{
    /**
     * @var array
     */
    private $jsKeys = [];

    /**
     * @var StoreInterface[]
     */
    private $stores;

    /**
     * @return bool
     */
    public function isAnyStoreIntegrated()
    {
        $config = $this->getJsApiKeysByStore();
        $apiKeys = array_filter($config, function ($store) {
            return array_filter($store, 'strlen');
        });

        return (bool)count($apiKeys);
    }

    /**
     * @return array
     */
    public function getJsApiKeysByStore()
    {
        $websiteId = $this->getWebsiteId();
        if (isset($this->jsKeys[$websiteId])) {
            return $this->jsKeys[$websiteId];
        }
        $this->jsKeys[$websiteId] = $this->getStoresConfigByPath(ConfigHelper::XML_PATH_JS_API_KEY, $websiteId);

        return $this->jsKeys[$websiteId];
    }

    /**
     * @param string $websiteName
     * @param string $storeName
     * @return int|null
     */
    public function getStoreIdByWebsiteAndStoreName($websiteName, $storeName)
    {
        $return = null;
        $stores = $this->getStores();
        foreach ($stores as $store) {
            $storeWebsiteName = null;
            if (method_exists($store, 'getWebsite')) {
                /** @var WebsiteInterface $storeWebsite */
                try {
                    $storeWebsite = $store->getWebsite();
                } catch (NoSuchEntityException $e) {
                    $this->_logger->error($e->getMessage());
                    continue;
                }
                $storeWebsiteName = $storeWebsite->getName();
            }

            if ($websiteName === $storeWebsiteName && $storeName === $store->getName()) {
                $return = (int)$store->getId();
                break;
            }
        }

        return $return;
    }

    /**
     * @param string $websiteName
     * @param string $storeName
     * @return string|null
     */
    public function getIntegrateUrlByWebsiteAndStoreName($websiteName, $storeName)
    {
        $storeId = $this->getStoreIdByWebsiteAndStoreName($websiteName, $storeName);
        if (!$storeId) { // Admin Store (0) intentionally caught here
            return null;
        }

        return $this->getUrl('*/*/*', [
            'section' => 'klevu_integration',
            'store' => $storeId,
        ]);
    }

    /**
     * @return string|null
     */
    private function getWebsiteId()
    {
        $request = $this->getRequest();
        $websiteId = (string)$request->getParam('website');

        return ('' !== $websiteId) ? $websiteId : null;
    }

    /**
     * @param string $configPath
     * @param string|null $websiteId
     *
     * @return array
     */
    private function getStoresConfigByPath($configPath, $websiteId = null)
    {
        $stores = $this->getStores();
        $storeValues = [];
        /** @var $store Store */
        foreach ($stores as $store) {
            try {
                $website = $store->getWebsite();
                if ($website->getCode() === Store::ADMIN_CODE) {
                    continue;
                }
                if ($websiteId && $website->getId() !== $websiteId) {
                    continue;
                }
                $value = $this->_scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $store->getCode());
                $storeValues[$website->getName()][$store->getName()] = $value;
            } catch (NoSuchEntityException $exception) {
                // Store doesn't really exist, so move on.
                continue;
            }
        }

        return $storeValues;
    }

    /**
     * @return StoreInterface[]
     */
    private function getStores()
    {
        if (null === $this->stores) {
            $this->stores = $this->_storeManager->getStores(true);
        }

        return $this->stores;
    }
}
