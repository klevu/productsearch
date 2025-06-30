<?php

namespace Klevu\Search\Service\Account;

use Klevu\Registry\Api\ConfigRegistryInterface;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Api\Service\Account\GetKmcUrlServiceInterface;
use Magento\Framework\App\Config\ConfigSourceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

class GetKmcUrlService implements GetKmcUrlServiceInterface
{
    const KLEVU_MERCHANT_CENTER_URL_DEFAULT = 'box.klevu.com';

    /**
     * @var ConfigSourceInterface
     */
    private $configSource;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ConfigRegistryInterface
     */
    private $configRegistry;

    /**
     * @param ConfigSourceInterface $configSource
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param ConfigRegistryInterface|null $configRegistry
     */
    public function __construct(
        ConfigSourceInterface $configSource,
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request,
        LoggerInterface $logger,
        ?ConfigRegistryInterface $configRegistry = null
    ) {
        $this->configSource = $configSource;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->logger = $logger;
        $this->configRegistry = $configRegistry ?: ObjectManager::getInstance()->get(ConfigRegistryInterface::class);
    }

    /**
     * @param string|int|null $storeId
     *
     * @return string
     */
    public function execute($storeId = null)
    {
        list($scope, $scopeId) = $this->getScope($storeId);

        try {
            $hostname = $this->scopeConfig->getValue(
                ConfigHelper::XML_PATH_HOSTNAME,
                $scope !== '' ? $scope : null,
                $scopeId !== '' ? $scopeId : null
            ) ?: $this->configSource->get('default/' . ConfigHelper::XML_PATH_HOSTNAME);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $hostname = static::KLEVU_MERCHANT_CENTER_URL_DEFAULT;
        }

        $hostname = rtrim(preg_replace('#^https?://#', '', $hostname), '/');

        return 'https://' . $hostname;
    }

    /**
     * @param string|int|null $storeId
     *
     * @return array
     */
    private function getScope($storeId = null)
    {
        if ($this->configRegistry->isSingleStoreMode()) {
            $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $scopeId = Store::DEFAULT_STORE_ID;
        } else {
            $scopeType = ScopeInterface::SCOPE_STORES;
            $scopeId = $storeId;
        }
        if (null !== $scopeId) {
            return [$scopeType, $scopeId];
        }
        $scopeId = $this->request->getParam('store');
        if (!$scopeId && $scopeId !== '') {
            $scopeType = ScopeInterface::SCOPE_WEBSITES;
            $scopeId = $this->request->getParam('website');
            if (!$scopeId && $scopeId !== '') {
                $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            }
        }

        return [$scopeType, $scopeId];
    }
}
