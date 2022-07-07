<?php

namespace Klevu\Search\Service\Account\KlevuApi;

use Klevu\Search\Api\Service\Account\KlevuApi\GetAccountDetailsInterface;
use Klevu\Search\Exception\InvalidApiResponseException;
use Klevu\Search\Exception\MissingApiUrlException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\ClientFactory as HttpClientFactory;
use Magento\Store\Model\ScopeInterface;

class GetAccountDetails implements GetAccountDetailsInterface
{
    const XML_PATH_HOSTNAME = "klevu_search/general/hostname";
    const XML_PATH_API_URL = 'klevu_search/general/api_url';
    const ENDPOINT = '/user-account/public/platform/account/details';
    const REQUEST_PARAM_JS_API_KEY = 'js_api_key';
    const REQUEST_PARAM_REST_API_KEY = 'rest_api_key';
    const REQUEST_HEADER_JS_API = 'X-KLEVU-JSAPIKEY';
    const REQUEST_HEADER_REST_API = 'X-KLEVU-RESTAPIKEY';
    const RESPONSE_ERROR_ERROR = 'error';
    const RESPONSE_ERROR_MESSAGE = 'message';
    const RESPONSE_ERROR_STATUS = 'status';
    const RESPONSE_SUCCESS_ACTIVE = 'active';
    const RESPONSE_SUCCESS_COMPANY_NAME = 'companyName';
    const RESPONSE_SUCCESS_EMAIL = 'email';
    const RESPONSE_SUCCESS_PLATFORM = 'platform';
    const RESPONSE_SUCCESS_URL_ANALYTICS = 'analyticsUrl';
    const RESPONSE_SUCCESS_URL_CAT_NAV = 'catNavUrl';
    const RESPONSE_SUCCESS_URL_INDEXING = 'indexingUrl';
    const RESPONSE_SUCCESS_URL_JS = 'jsUrl';
    const RESPONSE_SUCCESS_URL_SEARCH = 'searchUrl';
    const RESPONSE_SUCCESS_URL_TIERS = 'tiersUrl';

    /**
     * @var HttpClientFactory
     */
    private $httpClientFactory;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        HttpClientFactory $httpClientFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param array $apiKeys
     * @param int $storeId
     *
     * @return array
     * @throws InvalidApiResponseException
     * @throws MissingApiUrlException
     */
    public function execute(array $apiKeys, $storeId)
    {
        $httpClient = $this->httpClientFactory->create();

        $httpClient->addHeader(
            static::REQUEST_HEADER_JS_API,
            isset($apiKeys[static::REQUEST_PARAM_JS_API_KEY]) ? $apiKeys[static::REQUEST_PARAM_JS_API_KEY] : null
        );
        $httpClient->addHeader(
            static::REQUEST_HEADER_REST_API,
            isset($apiKeys[static::REQUEST_PARAM_REST_API_KEY]) ? $apiKeys[static::REQUEST_PARAM_REST_API_KEY] : null
        );

        $httpClient->get(
            $this->getUrl($storeId)
        );
        $responseStatus = $httpClient->getStatus();
        $responseBody = json_decode($httpClient->getBody(), true);
        if ($this->isSuccessfulResponseCode($responseStatus)) {
            return $responseBody;
        }
        $this->handleFailure($responseBody, $responseStatus);
    }

    /**
     * @param int $storeId
     *
     * @return string
     * @throws MissingApiUrlException
     */
    private function getUrl($storeId)
    {
        $apiUrl = $this->scopeConfig->getValue(
            static::XML_PATH_API_URL,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
        if (!$apiUrl) {
            throw new MissingApiUrlException(
                __('API URL is not set for store %s', $storeId),
                null,
                400
            );
        }
        $apiUrl = rtrim(preg_replace('#^https?://#', '', $apiUrl), '/');

        return 'https://' . $apiUrl . static::ENDPOINT;
    }

    /**
     * @param $responseStatus
     *
     * @return bool
     */
    private function isSuccessfulResponseCode($responseStatus)
    {
        return strpos($responseStatus, '2') === 0;
    }

    /**
     * @param $responseBody
     * @param $errorCode
     *
     * @return void
     * @throws InvalidApiResponseException
     */
    private function handleFailure($responseBody, $errorCode)
    {
        if ($errorCode === 401) {
            $errorMessage = __('Invalid Keys. Please Cancel and try again.');
        } else {
            $errorMessage = isset($responseBody[static::RESPONSE_ERROR_MESSAGE]) ?
                $responseBody[static::RESPONSE_ERROR_MESSAGE] :
                'An error occurred when calling the Klevu API';
        }

        throw new InvalidApiResponseException(
            __($errorMessage),
            null,
            $errorCode
        );
    }
}
