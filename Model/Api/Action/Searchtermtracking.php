<?php

namespace Klevu\Search\Model\Api\Action;

use Klevu\Search\Helper\Api as ApiHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Api\Actionall;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Model\Api\Response\Invalid;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class Searchtermtracking extends Actionall
{
    const ENDPOINT = "/analytics/n-search/search";
    const METHOD = "POST";
    const DEFAULT_REQUEST_MODEL = "Klevu\Search\Model\Api\Request\Post";
    const DEFAULT_RESPONSE_MODEL = "Klevu\Search\Model\Api\Response\Data";

    /**
     * @var Invalid
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

    public function __construct(
        Invalid $apiResponseInvalid,
        ApiHelper $searchHelperApi,
        StoreManagerInterface $storeModelStoreManagerInterface,
        ConfigHelper $searchHelperConfig,
        $requestModel = null,
        $responseModel = null
    ) {
        $this->_apiResponseInvalid = $apiResponseInvalid;
        $this->_searchHelperApi = $searchHelperApi;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;

        parent::__construct(
            $apiResponseInvalid,
            $searchHelperConfig,
            $storeModelStoreManagerInterface,
            $requestModel ?: static::DEFAULT_REQUEST_MODEL,
            $responseModel ?: static::DEFAULT_RESPONSE_MODEL,
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
        if (!isset($parameters["klevu_apiKey"]) || empty($parameters["klevu_apiKey"])) {
            $errors["klevu_apiKey"] = "Missing JS API key.";
        }
        if (!isset($parameters["klevu_term"]) || empty($parameters["klevu_term"])) {
            $errors["klevu_term"] = "Missing klevu term.";
        }
        if (!isset($parameters["klevu_totalResults"]) || empty($parameters["klevu_totalResults"])) {
            $errors["klevu_type"] = "Missing Total Results.";
        }
        if (!isset($parameters["klevu_shopperIP"]) || empty($parameters["klevu_shopperIP"])) {
            $errors["klevu_shopperIP"] = "Missing klevu shopperIP.";
        }
        if (!isset($parameters["klevu_typeOfQuery"]) || empty($parameters["klevu_typeOfQuery"])) {
            $errors["klevu_unit"] = "Missing Type of Query.";
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
     * @throws NoSuchEntityException
     */
    public function execute($parameters = [])
    {
        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return $this->_apiResponseInvalid->setErrors($validation_result);
        }
        $endpoint = $this->buildEndpoint(
            static::ENDPOINT,
            $this->_storeModelStoreManagerInterface->getStore(),
            $this->_searchHelperConfig->getAnalyticsUrl(),
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
     * @param string|null $hostname
     *
     * @return string
     */
    public function buildEndpoint($endpoint, $store = null, $hostname = null)
    {
        return static::ENDPOINT_PROTOCOL
            . ($hostname ?: $this->_searchHelperConfig->getHostname($store))
            . $endpoint;
    }
}
