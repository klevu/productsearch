<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration\Confirmation;

use Klevu\Registry\Api\ConfigRegistryInterface;
use Klevu\Search\Api\SerializerInterface;
use Klevu\Search\Api\Service\WebRestApi\Admin\GetBearerTokenInterface;
use Klevu\Search\Helper\Config;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class Button extends Template
{
    const KLEVU_SEARCH_API_CONTINUE = 'klevu_search/integration/index';
    const KLEVU_SEARCH_API_CONFIRM = 'klevu_search/integration/confirm';
    const KLEVU_SEARCH_API_ENDPOINTS = 'klevu_search/integration/endpoints';

    /**
     * @var GetBearerTokenInterface
     */
    private $getBearerToken;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var ConfigRegistryInterface
     */
    private $configRegistry;

    /**
     * @param Context $context
     * @param array $data
     * @param GetBearerTokenInterface|null $getBearerToken
     * @param SerializerInterface|null $serializer
     * @param ConfigRegistryInterface|null $configRegistry
     */
    public function __construct(
        Context $context,
        array $data = [],
        GetBearerTokenInterface $getBearerToken = null,
        SerializerInterface $serializer = null,
        ConfigRegistryInterface $configRegistry = null
    ) {
        parent::__construct($context, $data);

        $objectManager = ObjectManager::getInstance();
        $this->getBearerToken = $getBearerToken ?: $objectManager->get(GetBearerTokenInterface::class);
        $this->serializer = $serializer ?: $objectManager->get(SerializerInterface::class);
        $this->configRegistry = $configRegistry ?: $objectManager->get(ConfigRegistryInterface::class);
    }

    /**
     * @return string
     */
    public function getIntegrationConfig()
    {
        return $this->serializer->serialize([
            'continueUrl' => $this->escapeUrl($this->getContinueUrl()),
            'confirmationUrl' => $this->escapeUrl($this->getConfirmationUrl()),
            'jsApiKey' => $this->escapeJsQuote($this->getJsApiKey(), '\"'),
            'restApiKey' => $this->escapeJsQuote($this->getRestApiKey(), '\"'),
            'bearerToken' => $this->escapeJsQuote($this->getBearerToken->execute())
        ]);
    }

    /**
     * @return string
     */
    public function getReSyncConfig()
    {
        return $this->serializer->serialize([
            'endpointUrl' => $this->escapeUrl($this->getEndpointsUrl()),
            'jsApiKey' => $this->escapeJsQuote($this->getJsApiKey(), '\"'),
            'restApiKey' => $this->escapeJsQuote($this->getRestApiKey(), '\"')
        ]);
    }

    /**
     * @return string
     */
    private function getContinueUrl()
    {
        $request = $this->getRequest();
        $storeId = $request->getParam('store', Store::DEFAULT_STORE_ID);

        return $this->getUrl(
            static::KLEVU_SEARCH_API_CONTINUE,
            ['store_id' => $storeId]
        );
    }

    /**
     * @return string
     */
    private function getConfirmationUrl()
    {
        $request = $this->getRequest();
        $storeId = $request->getParam('store', Store::DEFAULT_STORE_ID);

        return $this->getUrl(
            static::KLEVU_SEARCH_API_CONFIRM,
            ['store_id' => $storeId]
        );
    }

    /**
     * @return string
     */
    private function getEndpointsUrl()
    {
        $request = $this->getRequest();
        $storeId = $request->getParam('store', Store::DEFAULT_STORE_ID);

        return $this->getUrl(
            static::KLEVU_SEARCH_API_ENDPOINTS,
            ['store_id' => $storeId]
        );
    }

    /**
     * @return string
     */
    private function getJsApiKey()
    {
        list($scopeType, $scopeId) = $this->getScope();

        return $this->configRegistry->getValue(
            Config::XML_PATH_JS_API_KEY,
            $scopeType,
            $scopeId
        );
    }

    /**
     * @return string
     */
    private function getRestApiKey()
    {
        list($scopeType, $scopeId) = $this->getScope();

        return $this->configRegistry->getValue(
            Config::XML_PATH_REST_API_KEY,
            $scopeType,
            $scopeId
        );
    }

    /**
     * @return array<int|string>
     */
    private function getScope()
    {
        $singleStoreMode = $this->configRegistry->isSingleStoreMode();
        $request = $this->getRequest();

        return [
            $singleStoreMode ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : ScopeInterface::SCOPE_STORES,
            $singleStoreMode ? Store::DEFAULT_STORE_ID : (int)$request->getParam('store', Store::DEFAULT_STORE_ID)
        ];
    }
}
