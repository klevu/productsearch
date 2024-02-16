<?php

namespace Klevu\Search\Model\Api\Action;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Api\Actionall;
use Klevu\Search\Model\Api\Response\Invalid as InvalidResponse;
use Magento\Store\Model\StoreManagerInterface;

class Adduser extends Actionall
{
    const ENDPOINT = "/n-search/addUser";
    const METHOD   = "POST";
    const DEFAULT_REQUEST_MODEL = "Klevu\Search\Model\Api\Request\Post";
    const DEFAULT_RESPONSE_MODEL = "Klevu\Search\Model\Api\Response\Data";

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
     * @param $parameters
     *
     * @return array|true
     */
    protected function validate($parameters)
    {
        $errors = [];
        if (!isset($parameters['email']) || empty($parameters['email'])) {
            $errors['email'] = "Missing email";
        }
        if (!isset($parameters['password']) || empty($parameters['password'])) {
            $errors['password'] = "Missing password";
        }
        if (!isset($parameters['url']) || empty($parameters['password'])) {
            $errors['url'] = "Missing url";
        }
        if (count($errors) === 0) {
            return true;
        }

        return $errors;
    }
}
