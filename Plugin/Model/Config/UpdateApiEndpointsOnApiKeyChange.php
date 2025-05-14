<?php

namespace Klevu\Search\Plugin\Model\Config;

use Klevu\Registry\Api\ConfigRegistryInterface;
use Klevu\Search\Api\Service\Account\GetAccountDetailsInterface;
use Klevu\Search\Api\Service\Account\Model\AccountDetailsInterface;
use Klevu\Search\Api\Service\Account\UpdateEndpointsInterface;
use Klevu\Search\Exception\InvalidApiResponseException;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Service\Account\Model\AccountDetailsFactory;
use Magento\Config\Model\Config;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class UpdateApiEndpointsOnApiKeyChange
{
    const SECTION_KLEVU_INTEGRATION = 'klevu_integration';
    const GROUP_AUTHENTICATION_KEYS = 'authentication_keys';
    const CONFIG_FORM_JS_API_KEY = 'js_api_key';
    const CONFIG_FORM_REST_API_KEY = 'rest_api_key';
    const GROUP_ENDPOINTS = 'endpoints';
    const CONFIG_FORM_ANALYTICS_URL = 'analytics_url';
    const CONFIG_FORM_CATEGORY_NAVIGATION_URL = 'category_navigation_url';
    const CONFIG_FORM_INDEXING_URL = 'rest_hostname';
    const CONFIG_FORM_JS_URL = 'js_url';
    const CONFIG_FORM_SEARCH_URL = 'cloud_search_v2_url';
    const CONFIG_FORM_TIRES_URL = 'tiers_url';

    /**
     * @var UpdateEndpointsInterface
     */
    private $updateEndpoints;
    /**
     * @var GetAccountDetailsInterface
     */
    private $getAccountDetails;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var AccountDetailsFactory
     */
    private $accountDetailsFactory;
    /**
     * @var ConfigRegistryInterface
     */
    private $configRegistry;

    /**
     * @param UpdateEndpointsInterface $updateEndpoints
     * @param GetAccountDetailsInterface $getAccountDetails
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param AccountDetailsFactory $accountDetailsFactory
     * @param ConfigRegistryInterface|null $configRegistry
     */
    public function __construct(
        UpdateEndpointsInterface $updateEndpoints,
        GetAccountDetailsInterface $getAccountDetails,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        AccountDetailsFactory $accountDetailsFactory,
        ?ConfigRegistryInterface $configRegistry = null
    ) {
        $this->updateEndpoints = $updateEndpoints;
        $this->getAccountDetails = $getAccountDetails;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->accountDetailsFactory = $accountDetailsFactory;
        $this->configRegistry = $configRegistry ?: ObjectManager::getInstance()->get(ConfigRegistryInterface::class);
    }

    /**
     * @param Config $subject
     *
     * @return array
     */
    public function beforeSave(Config $subject)
    {
        // an array of empty strings is value here
        $apiKeys = $this->getApiKeyIncludedInSave($subject);
        if (!$apiKeys) {
            return [];
        }
        try {
            $storeId = $subject->getStore();
            if (null === $storeId) {
                return [];
            }
            $store = $this->storeManager->getStore($storeId);
        } catch (\Exception $exception) {
            $this->removeApiDataFromSave($subject);

            return [];
        }
        if (!$this->haveApiKeysChanged($store, $apiKeys)) {
            return [];
        }
        try {
            $accountDetails = $this->getAccountDetails($apiKeys, $store);
            $this->updateEndpoints->execute($accountDetails, $store->getId());
            $this->removeEndpointsFromSave($subject);
        } catch (\Exception $exception) {
            $this->removeApiDataFromSave($subject);
        }

        return [];
    }

    /**
     * @param Config $config
     *
     * @return array
     */
    private function getApiKeyIncludedInSave(Config $config)
    {
        $section = $config->getData('section');
        if ($section !== static::SECTION_KLEVU_INTEGRATION) {
            return [];
        }
        $groups = $config->getData('groups');

        // phpcs:disable Generic.Files.LineLength.TooLong
        $formJsApiKey = isset($groups[static::GROUP_AUTHENTICATION_KEYS]['fields'][static::CONFIG_FORM_JS_API_KEY]['value'])
            ? $groups[static::GROUP_AUTHENTICATION_KEYS]['fields'][static::CONFIG_FORM_JS_API_KEY]['value']
            : null;
        $formRestApiKey = isset($groups[static::GROUP_AUTHENTICATION_KEYS]['fields'][static::CONFIG_FORM_REST_API_KEY]['value'])
            ? $groups[static::GROUP_AUTHENTICATION_KEYS]['fields'][static::CONFIG_FORM_REST_API_KEY]['value']
            : null;
        // phpcs:enable Generic.Files.LineLength.TooLong

        return [
            static::CONFIG_FORM_JS_API_KEY => $formJsApiKey,
            static::CONFIG_FORM_REST_API_KEY => $formRestApiKey
        ];
    }

    /**
     * @param StoreInterface $store
     * @param array $apiKeys
     *
     * @return bool
     */
    private function haveApiKeysChanged(StoreInterface $store, array $apiKeys)
    {
        if ($this->configRegistry->isSingleStoreMode()) {
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = Store::DEFAULT_STORE_ID;
        } else {
            $scopeType = ScopeInterface::SCOPE_STORES;
            $scopeId = $store->getId();
        }

        $jsApiKey = $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_JS_API_KEY,
            $scopeType,
            $scopeId
        );
        $restApiKey = $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_REST_API_KEY,
            $scopeType,
            $scopeId
        );

        $suppliedJsApiKey = isset($apiKeys[static::CONFIG_FORM_JS_API_KEY]) ?
            $apiKeys[static::CONFIG_FORM_JS_API_KEY] :
            null;
        $suppliedRestApiKey = isset($apiKeys[static::CONFIG_FORM_REST_API_KEY]) ?
            $apiKeys[static::CONFIG_FORM_REST_API_KEY] :
            null;

        return $jsApiKey !== $suppliedJsApiKey || $restApiKey !== $suppliedRestApiKey;
    }

    /**
     * @param array $apiKeys
     * @param StoreInterface $store
     *
     * @return AccountDetailsInterface
     * @throws InvalidApiResponseException
     * @throws \Zend_Validate_Exception
     */
    private function getAccountDetails(array $apiKeys, StoreInterface $store)
    {
        if ((!isset($apiKeys[static::CONFIG_FORM_JS_API_KEY]) || $apiKeys[static::CONFIG_FORM_JS_API_KEY] === '') &&
            (!isset($apiKeys[static::CONFIG_FORM_REST_API_KEY]) || $apiKeys[static::CONFIG_FORM_REST_API_KEY] === '')
        ) {
            return $this->accountDetailsFactory->create();
        }

        return $this->getAccountDetails->execute($apiKeys, $store->getId());
    }

    /**
     * @param Config $config
     *
     * @return void
     */
    private function removeApiDataFromSave(Config $config)
    {
        $groups = $config->getData('groups');
        if (isset($groups[static::GROUP_AUTHENTICATION_KEYS]['fields'][static::CONFIG_FORM_JS_API_KEY])) {
            unset($groups[static::GROUP_AUTHENTICATION_KEYS]['fields'][static::CONFIG_FORM_JS_API_KEY]);
        }
        if (isset($groups[static::GROUP_AUTHENTICATION_KEYS]['fields'][static::CONFIG_FORM_REST_API_KEY])) {
            unset($groups[static::GROUP_AUTHENTICATION_KEYS]['fields'][static::CONFIG_FORM_REST_API_KEY]);
        }
        $config->setData('groups', $groups);
    }

    /**
     * @param Config $config
     *
     * @return void
     */
    private function removeEndpointsFromSave(Config $config)
    {
        $groups = $config->getData('groups');
        if (isset($groups[static::GROUP_ENDPOINTS])) {
            unset($groups[static::GROUP_ENDPOINTS]);
        }

        $config->setData('groups', $groups);
    }
}
