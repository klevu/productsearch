<?php

namespace Klevu\Search\Service\Account;

use Klevu\Search\Api\Service\Account\GetAccountDetailsInterface;
use Klevu\Search\Api\Service\Account\Model\AccountDetailsInterface;
use Klevu\Search\Api\Service\Account\KlevuApi\GetAccountDetailsInterface as GetAccountDetailsApiInterface;
use Klevu\Search\Exception\InactiveApiAccountException;
use Klevu\Search\Exception\IncorrectPlatformException;
use Klevu\Search\Exception\InvalidApiKeyException;
use Klevu\Search\Exception\InvalidApiResponseException;
use \Klevu\Search\Service\Account\KlevuApi\GetAccountDetails as ApiGetAccountDetails;
use Klevu\Search\Service\Account\Model\AccountDetailsFactory;
use Magento\Framework\Validator\ValidatorInterface;

class GetAccountDetails implements GetAccountDetailsInterface
{
    const CAT_NAV_TRACKING_URL = 'cnstats.ksearchnet.com';

    /**
     * @var ValidatorInterface
     */
    private $jsApiKeyValidator;
    /**
     * @var ValidatorInterface
     */
    private $restApiKeyValidator;
    /**
     * @var GetAccountDetailsApiInterface
     */
    private $getAccountDetailsApi;
    /**
     * @var AccountDetailsFactory
     */
    private $accountDetailsFactory;

    public function __construct(
        ValidatorInterface $jsApiKeyValidator,
        ValidatorInterface $restApiKeyValidator,
        GetAccountDetailsApiInterface $getAccountDetailsApi,
        AccountDetailsFactory $accountDetailsFactory
    ) {
        $this->jsApiKeyValidator = $jsApiKeyValidator;
        $this->restApiKeyValidator = $restApiKeyValidator;
        $this->getAccountDetailsApi = $getAccountDetailsApi;
        $this->accountDetailsFactory = $accountDetailsFactory;
    }

    /**
     * @param array $apiKeys
     * @param int $storeId
     *
     * @return AccountDetailsInterface
     * @throws InactiveApiAccountException
     * @throws IncorrectPlatformException
     * @throws InvalidApiKeyException
     * @throws InvalidApiResponseException
     * @throws \Zend_Validate_Exception
     */
    public function execute(array $apiKeys, $storeId)
    {
        $this->validateRequest($apiKeys);
        $response = $this->getAccountDetailsApi->execute($apiKeys, $storeId);
        $accountDetails = $this->setResponseOnDataModel($response);
        $this->validateResponse($accountDetails);

        return $accountDetails;
    }

    /**
     * @param array $apiKeys
     *
     * @return void
     * @throws \Zend_Validate_Exception
     * @throws InvalidApiKeyException
     */
    private function validateRequest(array $apiKeys)
    {
        if (!isset($apiKeys[ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY]) ||
            !$this->jsApiKeyValidator->isValid($apiKeys[ApiGetAccountDetails::REQUEST_PARAM_JS_API_KEY])
        ) {
            throw new InvalidApiKeyException(
                __('Invalid JS API Key: ' . implode('; ', $this->jsApiKeyValidator->getMessages())),
                null,
                400
            );
        }
        if (!isset($apiKeys[ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY]) ||
            !$this->restApiKeyValidator->isValid($apiKeys[ApiGetAccountDetails::REQUEST_PARAM_REST_API_KEY])
        ) {
            throw new InvalidApiKeyException(
                __('Invalid Rest API Key: ' . implode('; ', $this->restApiKeyValidator->getMessages())),
                null,
                400
            );
        }
    }

    /**
     * @param AccountDetailsInterface $response
     *
     * @return void
     * @throws InvalidApiResponseException
     * @throws InactiveApiAccountException
     * @throws IncorrectPlatformException
     */
    private function validateResponse(AccountDetailsInterface $response)
    {
        if (!$response->getEmail()) {
            throw new InvalidApiResponseException(
                __('The response did not include the email address.'),
                null,
                404
            );
        }
        if (!$response->getAnalyticsUrl()) {
            throw new InvalidApiResponseException(
                __('The response did not include the Analytics URL.'),
                null,
                404
            );
        }
        if (!$response->getCatNavUrl()) {
            throw new InvalidApiResponseException(
                __('The response did not include the Cat Nav URL.'),
                null,
                404
            );
        }
        if (!$response->getIndexingUrl()) {
            throw new InvalidApiResponseException(
                __('The response did not include the Indexing URL.'),
                null,
                404
            );
        }
        if (!$response->getJsUrl()) {
            throw new InvalidApiResponseException(
                __('The response did not include the JS URL.'),
                null,
                404
            );
        }
        if (!$response->getSearchUrl()) {
            throw new InvalidApiResponseException(
                __('The response did not include the Search URL.'),
                null,
                404
            );
        }
        if (!$response->getTiersUrl()) {
            throw new InvalidApiResponseException(
                __('The response did not include the Tiers URL.'),
                null,
                404
            );
        }
        if (!$response->isAccountActive()) {
            throw new InactiveApiAccountException(
                __('This account is not active.'),
                null,
                400
            );
        }
        if (!$response->isPlatformMagento()) {
            throw new IncorrectPlatformException(
                __(
                    'This account is can not be integrated with Magento. This account is for %1',
                    $response->getPlatform()
                ),
                null,
                400
            );
        }
    }

    /**
     * @param array $response
     *
     * @return AccountDetailsInterface
     */
    private function setResponseOnDataModel(array $response)
    {
        $accountDetails = $this->accountDetailsFactory->create();

        if (isset($response[ApiGetAccountDetails::RESPONSE_SUCCESS_COMPANY_NAME])) {
            $accountDetails->setCompany($response[ApiGetAccountDetails::RESPONSE_SUCCESS_COMPANY_NAME]);
        }
        if (isset($response[ApiGetAccountDetails::RESPONSE_SUCCESS_EMAIL])) {
            $accountDetails->setEmail($response[ApiGetAccountDetails::RESPONSE_SUCCESS_EMAIL]);
        }
        if (isset($response[ApiGetAccountDetails::RESPONSE_SUCCESS_ACTIVE])) {
            $accountDetails->setActive((bool)$response[ApiGetAccountDetails::RESPONSE_SUCCESS_ACTIVE]);
        }
        if (isset($response[ApiGetAccountDetails::RESPONSE_SUCCESS_PLATFORM])) {
            $accountDetails->setPlatform($response[ApiGetAccountDetails::RESPONSE_SUCCESS_PLATFORM]);
        }
        if (isset($response[ApiGetAccountDetails::RESPONSE_SUCCESS_URL_ANALYTICS])) {
            $accountDetails->setAnalyticsUrl($response[ApiGetAccountDetails::RESPONSE_SUCCESS_URL_ANALYTICS]);
        }
        if (isset($response[ApiGetAccountDetails::RESPONSE_SUCCESS_URL_CAT_NAV])) {
            $accountDetails->setCatNavUrl($response[ApiGetAccountDetails::RESPONSE_SUCCESS_URL_CAT_NAV]);
        }
        if (isset($response[ApiGetAccountDetails::RESPONSE_SUCCESS_URL_INDEXING])) {
            $accountDetails->setIndexingUrl($response[ApiGetAccountDetails::RESPONSE_SUCCESS_URL_INDEXING]);
        }
        if (isset($response[ApiGetAccountDetails::RESPONSE_SUCCESS_URL_JS])) {
            $accountDetails->setJsUrl($response[ApiGetAccountDetails::RESPONSE_SUCCESS_URL_JS]);
        }
        if (isset($response[ApiGetAccountDetails::RESPONSE_SUCCESS_URL_SEARCH])) {
            $accountDetails->setSearchUrl($response[ApiGetAccountDetails::RESPONSE_SUCCESS_URL_SEARCH]);
        }
        if (isset($response[ApiGetAccountDetails::RESPONSE_SUCCESS_URL_TIERS])) {
            $accountDetails->setTiersUrl($response[ApiGetAccountDetails::RESPONSE_SUCCESS_URL_TIERS]);
        }
        // Cat nav tracking is not returned from the API,
        // but should be set when clicking resync endpoints in the admin
        $accountDetails->setCatNavTrackingUrl(static::CAT_NAV_TRACKING_URL);

        return $accountDetails;
    }
}
