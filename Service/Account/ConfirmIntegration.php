<?php

namespace Klevu\Search\Service\Account;

use Klevu\Registry\Api\ConfigRegistryInterface;
use Klevu\Search\Api\Service\Account\ConfirmIntegrationInterface;
use Klevu\Search\Api\Service\Account\GetAccountDetailsInterface;
use Klevu\Search\Api\Service\Account\UpdateEndpointsInterface;
use Klevu\Search\Exception\InvalidApiKeyException;
use Klevu\Search\Exception\InvalidApiResponseException;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Service\Account\KlevuApi\GetAccountDetails as ApiGetAccountDetails;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriterInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validator\ValidatorInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class ConfirmIntegration implements ConfirmIntegrationInterface
{
    /**
     * @var ScopeConfigWriterInterface
     */
    private $scopeConfigWriter;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var UpdateEndpointsInterface
     */
    private $updateEndpoints;
    /**
     * @var ValidatorInterface
     */
    private $jsApiKeyValidator;
    /**
     * @var ValidatorInterface
     */
    private $restApiKeyValidator;
    /**
     * @var GetAccountDetailsInterface
     */
    private $getAccountDetails;
    /**
     * @var ConfigRegistryInterface
     */
    private $configRegistry;
    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @param ScopeConfigWriterInterface $scopeConfigWriter
     * @param StoreManagerInterface $storeManager
     * @param GetAccountDetailsInterface $getAccountDetails
     * @param UpdateEndpointsInterface $updateEndpoints
     * @param ValidatorInterface $jsApiKeyValidator
     * @param ValidatorInterface $restApiKeyValidator
     * @param ConfigRegistryInterface|null $configRegistry
     * @param ReinitableConfigInterface|null $reinitableConfig
     */
    public function __construct(
        ScopeConfigWriterInterface $scopeConfigWriter,
        StoreManagerInterface $storeManager,
        GetAccountDetailsInterface $getAccountDetails,
        UpdateEndpointsInterface $updateEndpoints,
        ValidatorInterface $jsApiKeyValidator,
        ValidatorInterface $restApiKeyValidator,
        ?ConfigRegistryInterface $configRegistry = null,
        ?ReinitableConfigInterface $reinitableConfig = null
    ) {
        $this->scopeConfigWriter = $scopeConfigWriter;
        $this->storeManager = $storeManager;
        $this->getAccountDetails = $getAccountDetails;
        $this->updateEndpoints = $updateEndpoints;
        $this->jsApiKeyValidator = $jsApiKeyValidator;
        $this->restApiKeyValidator = $restApiKeyValidator;
        $objectManager = ObjectManager::getInstance();
        $this->configRegistry = $configRegistry ?: $objectManager->get(ConfigRegistryInterface::class);
        $this->reinitableConfig = $reinitableConfig ?: $objectManager->get(ReinitableConfigInterface::class);
    }

    /**
     * @param array $apiKeys
     * @param int $storeId
     *
     * @return void
     * @throws \Zend_Validate_Exception|NoSuchEntityException|InvalidApiResponseException|InvalidApiKeyException
     */
    public function execute(array $apiKeys, $storeId)
    {
        $this->validateApiKeys($apiKeys);
        $store = $this->storeManager->getStore($storeId);
        $accountDetails = $this->getAccountDetails->execute($apiKeys, $store->getId());
        $this->updateEndpoints->execute($accountDetails, $store->getId());
        $this->saveApiKeys($apiKeys, $store);
    }

    /**
     * @param array $apiKeys
     *
     * @return void
     * @throws \Zend_Validate_Exception|InvalidApiKeyException
     */
    private function validateApiKeys(array $apiKeys)
    {
        $jsApiKey = isset($apiKeys[ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY]) ?
            $apiKeys[ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY] :
            null;
        if (!$this->jsApiKeyValidator->isValid($jsApiKey)) {
            throw new InvalidApiKeyException(
                __('Invalid JS API Key: ' . implode('; ', $this->jsApiKeyValidator->getMessages())),
                null,
                400
            );
        }
        $restApiKey = isset($apiKeys[ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY]) ?
            $apiKeys[ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY] :
            null;
        if (!$this->restApiKeyValidator->isValid($restApiKey)) {
            throw new InvalidApiKeyException(
                __('Invalid Rest API Key: ' . implode('; ', $this->restApiKeyValidator->getMessages())),
                null,
                400
            );
        }
    }

    /**
     * @param array $apiKeys
     * @param StoreInterface $store
     *
     * @return void
     */
    private function saveApiKeys(array $apiKeys, StoreInterface $store)
    {
        if ($this->configRegistry->isSingleStoreMode()) {
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = Store::DEFAULT_STORE_ID;
        } else {
            $scopeType = ScopeInterface::SCOPE_STORES;
            $scopeId = $store->getId();
        }

        $this->scopeConfigWriter->save(
            ConfigHelper::XML_PATH_REST_API_KEY,
            $apiKeys[ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY],
            $scopeType,
            $scopeId
        );
        $this->scopeConfigWriter->save(
            ConfigHelper::XML_PATH_JS_API_KEY,
            $apiKeys[ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY],
            $scopeType,
            $scopeId
        );
        $this->reinitableConfig->reinit();
    }
}
