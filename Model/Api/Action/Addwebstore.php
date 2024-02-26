<?php

namespace Klevu\Search\Model\Api\Action;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Api\Actionall;
use Klevu\Search\Model\Api\Request\Post as ApiPostRequest;
use Klevu\Search\Model\Api\Response\Data as ApiResponseData;
use Klevu\Search\Model\Api\Response\Invalid as InvalidResponse;
use Magento\Store\Model\StoreManagerInterface;

class Addwebstore extends Actionall
{
    const ENDPOINT = "/n-search/addWebstore";
    const METHOD = "POST";
    const DEFAULT_REQUEST_MODEL = ApiPostRequest::class;
    const DEFAULT_RESPONSE_MODEL = ApiResponseData::class;

    /**
     * @param InvalidResponse $apiResponseInvalid
     * @param ConfigHelper $searchHelperConfig
     * @param StoreManagerInterface $storeManagerInterface
     * @param string|null $requestModel
     * @param string|null $responseModel
     */
    public function __construct(
        InvalidResponse $apiResponseInvalid,
        ConfigHelper $searchHelperConfig,
        StoreManagerInterface $storeManagerInterface,
        $requestModel = null,
        $responseModel = null
    ) {
        parent::__construct(
            $apiResponseInvalid,
            $searchHelperConfig,
            $storeManagerInterface,
            $requestModel ?: static::DEFAULT_REQUEST_MODEL,
            $responseModel ?: static::DEFAULT_RESPONSE_MODEL
        );
    }

    /**
     * @param array $parameters
     *
     * @return array|true
     */
    protected function validate($parameters)
    {
        $errors = [];
        if (!isset($parameters['customerId']) || empty($parameters['customerId'])) {
            $errors['customerId'] = "Missing customer id.";
        }
        if (!isset($parameters['storeName']) || empty($parameters['storeName'])) {
            $errors['storeName'] = "Missing store name.";
        }
        if (!isset($parameters['language']) || empty($parameters['language'])) {
            $errors['language'] = "Missing language.";
        }
        if (!isset($parameters['timezone']) || empty($parameters['timezone'])) {
            $errors['timezone'] = "Missing timezone.";
        }
        if (!isset($parameters['version']) || empty($parameters['version'])) {
            $errors['version'] = "Missing module version";
        }
        if (!isset($parameters['country']) || empty($parameters['country'])) {
            $errors['country'] = "Missing country.";
        }
        if (!isset($parameters['locale']) || empty($parameters['locale'])) {
            $errors['locale'] = "Missing locale.";
        }
        if (count($errors) == 0) {
            return true;
        }

        return $errors;
    }
}
