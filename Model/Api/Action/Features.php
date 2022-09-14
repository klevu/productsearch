<?php

namespace Klevu\Search\Model\Api\Action;

use Klevu\Search\Helper\Api as ApiHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Api\Actionall;
use Klevu\Search\Model\Api\Request\Post as ApiPostRequest;
use Klevu\Search\Model\Api\Response as KlevuApiResponse;
use Klevu\Search\Model\Api\Response\Data as ApiResponseData;
use Klevu\Search\Model\Api\Response\Invalid as InvalidApiResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class Features extends Actionall
{
    const ENDPOINT = "/uti/getFeaturesAndUpgradeLink";
    const METHOD = "POST";
    const DEFAULT_REQUEST_MODEL = ApiPostRequest::class;
    const DEFAULT_RESPONSE_MODEL = ApiResponseData::class;

    /**
     * @var InvalidApiResponse
     */
    protected $_apiResponseInvalid;
    /**
     * @var Store
     */
    protected $_frameworkModelStore;
    /**
     * @var ApiHelper
     */
    protected $_searchHelperApi;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;

    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;

    /**
     * @param InvalidApiResponse $apiResponseInvalid
     * @param ApiHelper $searchHelperApi
     * @param ConfigHelper $searchHelperConfig
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param SearchHelper $searchHelperData
     * @param Store $frameworkModelStore
     */
    public function __construct(
        InvalidApiResponse $apiResponseInvalid,
        ApiHelper $searchHelperApi,
        ConfigHelper $searchHelperConfig,
        StoreManagerInterface $storeModelStoreManagerInterface,
        SearchHelper $searchHelperData,
        Store $frameworkModelStore
    ) {
        $this->_apiResponseInvalid = $apiResponseInvalid;
        $this->_searchHelperApi = $searchHelperApi;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_searchHelperData = $searchHelperData;
        $this->_frameworkModelStore = $frameworkModelStore;
    }

    /**
     * @param $parameters
     *
     * @return array|bool
     */
    protected function validate($parameters)
    {
        $errors = [];
        if (empty($parameters["restApiKey"])) {
            $errors["restApiKey"] = "Missing Rest API key.";
        }

        return $errors ?: true;
    }

    /**
     * Execute the API action with the given parameters.
     *
     * @param array $parameters
     *
     * @return KlevuApiResponse
     * @throws LocalizedException
     * @throws \Exception
     */
    public function execute($parameters = [])
    {
        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return $this->_apiResponseInvalid->setErrors($validation_result);
        }
        $storeParam = isset($parameters['store']) ? $parameters['store'] : null;
        $store = $this->_frameworkModelStore->load($storeParam);

        $endpoint = $this->buildEndpoint(
            isset($parameters['endpoint']) ? $parameters['endpoint'] : static::ENDPOINT,
            $store,
            $this->_searchHelperConfig->getTiresUrl($store)
        );
        $request = $this->getRequest();
        $request->setResponseModel($this->getResponse())
            ->setEndpoint($endpoint)
            ->setMethod(static::METHOD)
            ->setData($parameters);

        return $request->send();
    }

    /**
     * @param $endpoint
     * @param $store
     * @param $hostname
     *
     * @return string
     */
    public function buildEndpoint($endpoint, $store = null, $hostname = null)
    {
        if (!$hostname) {
            $hostname = $this->_searchHelperConfig->getHostname($store);
        }

        return static::ENDPOINT_PROTOCOL
            . rtrim(preg_replace('#^https?://#', '', $hostname), '/')
            . '/'
            . ltrim($endpoint, '/');
    }
}
