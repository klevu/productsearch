<?php

namespace Klevu\Search\Service\Account;

use Klevu\Search\Api\Service\Account\GetStoresUsingApiKeysInterface;
use Klevu\Search\Exception\InvalidApiKeyException;
use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Validator\ValidatorInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class GetStoresUsingApiKeys implements GetStoresUsingApiKeysInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ValidatorInterface
     */
    private $restApiKeyValidator;
    /**
     * @var ValidatorInterface
     */
    private $jsApiKeyValidator;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ValidatorInterface $jsApiKeyValidator,
        ValidatorInterface $restApiKeyValidator
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->jsApiKeyValidator = $jsApiKeyValidator;
        $this->restApiKeyValidator = $restApiKeyValidator;
    }

    /**
     * @param string $restApiKey
     * @param string $jsApiKey
     *
     * @return string[][]
     * @throws InvalidApiKeyException
     * @throws \Zend_Validate_Exception
     */
    public function execute($restApiKey, $jsApiKey)
    {
        $this->validateInput($restApiKey, $jsApiKey);

        return array_map(function (StoreInterface $store) {
            return ['id' => $store->getId(), 'code' => $store->getCode(), 'name' => $store->getName()];
        }, $this->getStoresConfig($restApiKey, $jsApiKey));
    }

    /**
     * @param $restApiKey
     * @param $jsApiKey
     *
     * @return array
     */
    private function getStoresConfig($restApiKey, $jsApiKey)
    {
        $stores = $this->storeManager->getStores(true);
        $configuredStores = [];

        foreach ($stores as $store) {
            $configJsApiKey = $this->scopeConfig->getValue(
                ConfigHelper::XML_PATH_JS_API_KEY,
                ScopeInterface::SCOPE_STORE,
                $store->getCode()
            );
            if ($configJsApiKey === $jsApiKey) {
                $configuredStores[] = $store;
            }
            $configRestApiKey = $this->scopeConfig->getValue(
                ConfigHelper::XML_PATH_REST_API_KEY,
                ScopeInterface::SCOPE_STORE,
                $store->getCode()
            );
            if ($configRestApiKey === $restApiKey &&
                !in_array($store, $configuredStores, true)
            ) {
                $configuredStores[] = $store;
            }
        }

        return $configuredStores;
    }

    /**
     * @param $restApiKey
     * @param $jsApiKey
     *
     * @return void
     * @throws InvalidApiKeyException
     * @throws \Zend_Validate_Exception
     */
    private function validateInput($restApiKey, $jsApiKey)
    {
        if (!$this->restApiKeyValidator->isValid($restApiKey)) {
            throw new InvalidApiKeyException(
                __('Invalid Rest API Key: ' . implode('; ', $this->restApiKeyValidator->getMessages())),
                null,
                400
            );
        }
        if (!$this->jsApiKeyValidator->isValid($jsApiKey)) {
            throw new InvalidApiKeyException(
                __('Invalid JS API Key: ' . implode('; ', $this->jsApiKeyValidator->getMessages())),
                null,
                400
            );
        }
    }
}
