<?php

namespace Klevu\Search\Model\Api\Action;

use Klevu\Search\Helper\Api as ApiHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Api\Actionall;
use Klevu\Search\Model\Api\Request\Post as PostRequest;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Model\Api\Response\Data as ResponseData;
use Klevu\Search\Model\Api\Response\Invalid as InvalidResponse;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class Producttracking extends Actionall
{
    const ENDPOINT = "/analytics/productTracking";
    const METHOD = "POST";
    const DEFAULT_REQUEST_MODEL = PostRequest::class;
    const DEFAULT_RESPONSE_MODEL = ResponseData::class;

    /**
     * @var InvalidResponse
     */
    protected $_apiResponseInvalid;
    /**
     * @var ApiHelper
     */
    protected $_searchHelperApi;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;

    /**
     * @param InvalidResponse $apiResponseInvalid
     * @param ApiHelper $searchHelperApi
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param ConfigHelper $searchHelperConfig
     */
    public function __construct(
        InvalidResponse $apiResponseInvalid,
        ApiHelper $searchHelperApi,
        StoreManagerInterface $storeModelStoreManagerInterface,
        ConfigHelper $searchHelperConfig
    ) {
        $this->_apiResponseInvalid = $apiResponseInvalid;
        $this->_searchHelperApi = $searchHelperApi;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
    }

    /**
     * @param array $parameters
     *
     * @return array|bool
     */
    protected function validate($parameters)
    {
        $errors = [];

        if (!isset($parameters["klevu_apiKey"]) || empty($parameters["klevu_apiKey"])) {
            $errors["klevu_apiKey"] = "Missing JS API key.";
        }

        if (!isset($parameters["klevu_type"]) || empty($parameters["klevu_type"])) {
            $errors["klevu_type"] = "Missing type.";
        }

        if (!isset($parameters["klevu_productId"]) || empty($parameters["klevu_productId"])) {
            $errors["klevu_productId"] = "Missing product ID.";
        }

        if (!isset($parameters["klevu_unit"]) || empty($parameters["klevu_unit"])) {
            $errors["klevu_unit"] = "Missing unit.";
        }

        if (!isset($parameters["klevu_salePrice"]) ||
            (!is_numeric($parameters["klevu_salePrice"]) && empty($parameters["klevu_salePrice"]))
        ) {
            $errors["klevu_salePrice"] = "Missing sale price.";
        }

        if (!isset($parameters["klevu_currency"]) || empty($parameters["klevu_currency"])) {
            $errors["klevu_currency"] = "Missing currency.";
        }

        if (count($errors) === 0) {
            return true;
        }

        return $errors;
    }

    /**
     * Execute the API action with the given parameters.
     *
     * @param array $parameters
     *
     * @return Response
     * @throws \Exception
     */
    public function execute($parameters = [])
    {
        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return $this->_apiResponseInvalid->setErrors($validation_result);
        }
        $store = $this->getStore();
        $endpoint = $this->buildEndpoint(
            static::ENDPOINT,
            $store,
            $this->_searchHelperConfig->getAnalyticsUrl($store)
        );

        $request = $this->getRequest();
        $request->setResponseModel($this->getResponse());
        $request->setEndpoint($endpoint);
        $request->setMethod(static::METHOD);
        $request->setData($parameters);

        return $request->send();
    }

    /**
     * @param string $endpoint
     * @param StoreInterface|string|int|null $store
     * @param string $hostname
     *
     * @return string
     */
    public function buildEndpoint($endpoint, $store = null, $hostname = null)
    {
        return static::ENDPOINT_PROTOCOL .
            ($hostname ?: $this->_searchHelperConfig->getHostname($store)) .
            $endpoint;
    }
}
