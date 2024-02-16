<?php

namespace Klevu\Search\Model\Api\Action;

use Klevu\Search\Helper\Api as ApiHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Api\Actionall;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Model\Api\Response\Invalid as InvalidResponse;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class Idsearch extends Actionall
{
    const ENDPOINT = "/cloud-search/n-search/idsearch";
    const METHOD   = "GET";
    const DEFAULT_REQUEST_MODEL = "Klevu\Search\Model\Api\Request\Get";
    const DEFAULT_RESPONSE_MODEL = "Klevu\Search\Model\Api\Response\Data";

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
      * @var StoreManagerInterface
      */
    protected $_storeModelStoreManagerInterface;

    /**
     * @param InvalidResponse $apiResponseInvalid
     * @param ApiHelper $searchHelperApi
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param ConfigHelper $searchHelperConfig
     * @param $requestModel
     * @param $responseModel
     */
    public function __construct(
        InvalidResponse $apiResponseInvalid,
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
            $responseModel ?: static::DEFAULT_RESPONSE_MODEL
        );
    }

    /**
     * Get the store used for this request.
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getStore()
    {
        if (!$this->hasData('store')) {
            $this->setData('store', $this->_storeModelStoreManagerInterface->getStore());
        }

        return $this->getData('store');
    }

    /**
     * @param $parameters
     *
     * @return array|true
     */
    protected function validate($parameters)
    {
        $errors = [];
        if (!isset($parameters['ticket']) || empty($parameters['ticket'])) {
            $errors['ticket'] = "Missing ticket (Search API Key)";
        }
        if (!isset($parameters['noOfResults']) || empty($parameters['noOfResults'])) {
            $errors['noOfResults'] = "Missing number of results to return";
        }
        if (!isset($parameters['term']) || empty($parameters['term'])) {
            $errors['term'] = "Missing search term";
        }
        if (!isset($parameters['paginationStartsFrom'])) {
            $errors['paginationStartsFrom'] = "Missing pagination start from value ";
        } elseif ((int)$parameters['paginationStartsFrom'] < 0) {
            $errors['paginationStartsFrom'] = "Pagination needs to start from 0 or higher";
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
     * @throws \Exception
     */
    public function execute($parameters = [])
    {
        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return $this->_apiResponseInvalid->setErrors($validation_result);
        }
        $endpoint = $this->buildEndpoint(
            static::ENDPOINT,
            $this->getStore(),
            $this->_searchHelperConfig->getCloudSearchUrl($this->getStore())
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
