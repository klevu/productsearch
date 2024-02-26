<?php

namespace Klevu\Search\Model\Api\Action;

use Klevu\Search\Helper\Api as ApiHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Api\Actionall;
use Klevu\Search\Model\Api\Request\Post as ApiPostRequest;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Model\Api\Response\Data as ApiResponseData;
use Klevu\Search\Model\Api\Response\Invalid as InvalidResponse;
use Klevu\Search\Model\Api\Response\Rempty;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class Debuginfo extends Actionall
{
    const ENDPOINT = "/n-search/logReceiver";
    const METHOD = "POST";
    const DEFAULT_REQUEST_MODEL = ApiPostRequest::class;
    const DEFAULT_RESPONSE_MODEL = ApiResponseData::class;

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
     * @param string|null $requestModel
     * @param string|null $responseModel
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
     * @param array $parameters
     *
     * @return Response|Rempty
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    public function debugKlevu($parameters)
    {
        $endpoint = $this->buildEndpoint(
            static::ENDPOINT,
            $this->_storeModelStoreManagerInterface->getStore(),
            $this->_searchHelperConfig->getHostname($this->_storeModelStoreManagerInterface->getStore()),
        );
        $response = $this->getResponse();

        $request = $this->getRequest();
        $request->setResponseModel($response);
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
            ($hostname ?: $this->_searchHelperConfig->getHostname($store))
            . $endpoint;
    }
}
