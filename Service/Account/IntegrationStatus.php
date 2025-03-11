<?php

namespace Klevu\Search\Service\Account;

use Klevu\Registry\Api\ConfigRegistryInterface;
use Klevu\Search\Api\Service\Account\IntegrationStatusInterface;
use Klevu\Search\Helper\Config;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\Writer as ScopeConfigWriter;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validator\ValidatorInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class IntegrationStatus implements IntegrationStatusInterface
{
    const XML_CONFIG_PATH_INTEGRATION_STATUS = 'klevu_integration/integration/status';
    const INTEGRATION_STATUS_NOT_INTEGRATED = 0;
    const INTEGRATION_STATUS_JUST_INTEGRATED = 1;
    const INTEGRATION_STATUS_PREVIOUSLY_INTEGRATED = 2;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ScopeConfigWriter
     */
    private $scopeConfigWriter;
    /**
     * @var ValidatorInterface
     */
    private $jsApiKeyValidator;
    /**
     * @var ValidatorInterface
     */
    private $restApiKeyValidator;
    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;
    /**
     * @var ConfigRegistryInterface
     */
    private $configRegistry;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigWriter $scopeConfigWriter
     * @param LoggerInterface $logger
     * @param ValidatorInterface $jsApiKeyValidator
     * @param ValidatorInterface $restApiKeyValidator
     * @param ReinitableConfigInterface $reinitableConfig
     * @param ConfigRegistryInterface|null $configRegistry
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        ScopeConfigWriter $scopeConfigWriter,
        LoggerInterface $logger,
        ValidatorInterface $jsApiKeyValidator,
        ValidatorInterface $restApiKeyValidator,
        ReinitableConfigInterface $reinitableConfig,
        ConfigRegistryInterface $configRegistry = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->scopeConfigWriter = $scopeConfigWriter;
        $this->logger = $logger;
        $this->jsApiKeyValidator = $jsApiKeyValidator;
        $this->restApiKeyValidator = $restApiKeyValidator;
        $this->reinitableConfig = $reinitableConfig;
        $this->configRegistry = $configRegistry ?: ObjectManager::getInstance()->get(ConfigRegistryInterface::class);
    }

    /**
     * @param StoreInterface|null $store
     * @return bool
     */
    public function isJustIntegrated(StoreInterface $store = null)
    {
        if ($this->configRegistry->isSingleStoreMode()) {
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = 0;
        } else {
            $scopeType = ScopeInterface::SCOPE_STORES;
            try {
                $store = $store ?: $this->getStore();
            } catch (NoSuchEntityException $exception) {
                $this->logger->error('Could not load store to check integration status ' . $exception->getMessage());

                return false;
            }

            $scopeId = $store->getId();
        }
        if (!$this->isIntegrated($store)) {
            return false;
        }

        $status = (int)$this->scopeConfig->getValue(
            static::XML_CONFIG_PATH_INTEGRATION_STATUS,
            $scopeType,
            $scopeId
        );

        return $status === static::INTEGRATION_STATUS_JUST_INTEGRATED;
    }

    /**
     * @param StoreInterface $store
     *
     * @return void
     */
    public function setJustIntegrated(StoreInterface $store)
    {
        if ($this->configRegistry->isSingleStoreMode()) {
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = 0;
        } else {
            $scopeType = ScopeInterface::SCOPE_STORES;
            $scopeId = $store->getId();
        }

        $status = (int)$this->scopeConfig->getValue(
            static::XML_CONFIG_PATH_INTEGRATION_STATUS,
            $scopeType,
            $scopeId
        );
        if ($status === static::INTEGRATION_STATUS_PREVIOUSLY_INTEGRATED) {
            return;
        }

        $this->scopeConfigWriter->save(
            static::XML_CONFIG_PATH_INTEGRATION_STATUS,
            static::INTEGRATION_STATUS_JUST_INTEGRATED,
            $scopeType,
            $scopeId
        );
        $this->reinitableConfig->reinit();
    }

    /**
     * @param StoreInterface $store
     *
     * @return bool
     */
    public function isIntegrated(StoreInterface $store = null)
    {
        if ($this->configRegistry->isSingleStoreMode()) {
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = 0;
        } else {
            $scopeType = ScopeInterface::SCOPE_STORES;
            try {
                $store = $store ?: $this->getStore();
            } catch (NoSuchEntityException $e) {
                return false;
            }

            $scopeId = $store->getId();
        }

        $jsApiKey = $this->scopeConfig->getValue(
            Config::XML_PATH_JS_API_KEY,
            $scopeType,
            $scopeId
        );
        $restApiKey = $this->scopeConfig->getValue(
            Config::XML_PATH_REST_API_KEY,
            $scopeType,
            $scopeId
        );

        return $this->jsApiKeyValidator->isValid($jsApiKey) &&
            $this->restApiKeyValidator->isValid($restApiKey);
    }

    /**
     * @param StoreInterface $store
     *
     * @return void
     */
    public function setIntegrated(StoreInterface $store)
    {
        if ($this->configRegistry->isSingleStoreMode()) {
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = 0;
        } else {
            $scopeType = ScopeInterface::SCOPE_STORES;
            $scopeId = $store->getId();
        }

        $this->scopeConfigWriter->save(
            static::XML_CONFIG_PATH_INTEGRATION_STATUS,
            static::INTEGRATION_STATUS_PREVIOUSLY_INTEGRATED,
            $scopeType,
            $scopeId
        );
        $this->reinitableConfig->reinit();
    }

    /**
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore()
    {
        $storeId = $this->request->getParam('store');
        if ('' === (string)$storeId) {
            $storeId = $this->configRegistry->isSingleStoreMode() ? 0 : null;
        }

        return $this->storeManager->getStore($storeId);
    }
}
