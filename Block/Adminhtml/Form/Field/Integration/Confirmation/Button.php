<?php

namespace Klevu\Search\Block\Adminhtml\Form\Field\Integration\Confirmation;

use Klevu\Search\Api\SerializerInterface;
use Klevu\Search\Api\Service\WebRestApi\Admin\GetBearerTokenInterface;
use Klevu\Search\Helper\Config;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;

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

    public function __construct(
        Context $context,
        array $data = [],
        GetBearerTokenInterface $getBearerToken = null,
        SerializerInterface $serializer = null
    ) {
        parent::__construct($context, $data);
        $this->getBearerToken = $getBearerToken ?: ObjectManager::getInstance()->get(GetBearerTokenInterface::class);
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
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
        $storeId = $request->getParam('store', 0);

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
        $storeId = $request->getParam('store', 0);

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
        $storeId = $request->getParam('store', 0);

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
        $request = $this->getRequest();
        $storeId = $request->getParam('store', 0);

        return $this->_scopeConfig->getValue(
            Config::XML_PATH_JS_API_KEY,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * @return string
     */
    private function getRestApiKey()
    {
        $request = $this->getRequest();
        $storeId = $request->getParam('store', 0);

        return $this->_scopeConfig->getValue(
            Config::XML_PATH_REST_API_KEY,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }
}
