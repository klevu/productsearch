<?php

namespace Klevu\Search\Model\Api\Action;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Api\Actionall;
use Klevu\Search\Model\Api\Response\Invalid as InvalidResponse;
use Magento\Store\Model\StoreManagerInterface;

class Gettimezone extends Actionall
{
    const ENDPOINT = "/analytics/getTimezone";
    const METHOD   = "POST";
    const DEFAULT_REQUEST_MODEL  = "klevu_search/api_request_post";
    const DEFAULT_RESPONSE_MODEL = "klevu_search/api_response_timezone";

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
     * @return true
     */
    protected function validate($parameters)
    {
        return true;
    }
}
