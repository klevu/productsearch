<?php

namespace Klevu\Search\Model\Api;

use Klevu\Search\Helper\Api as ApiHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Api\Response\Invalid as InvalidResponse;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class Actionall extends DataObject
{
    const ENDPOINT = "";
    const METHOD = "GET";
    const ENDPOINT_PROTOCOL = 'https://';
    const ENDPOINT_DEFAULT_HOSTNAME = 'box.klevu.com';
    const DEFAULT_REQUEST_MODEL = "Klevu\Search\Model\Api\Request";
    const DEFAULT_RESPONSE_MODEL = "Klevu\Search\Model\Api\Response";

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
     * @deprecated is never used
     */
    protected $_searchModelApi;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var Request $request
     */
    protected $request;
    /**
     * @var Response $response
     */
    protected $response;
    /**
     * @var string|null
     */
    private $requestModel;
    /**
     * @var string|null
     */
    private $responseModel;

    /**
     * @param InvalidResponse $apiResponseInvalid
     * @param ConfigHelper $searchHelperConfig
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param string|null $requestModel
     * @param string|null $responseModel
     * @param array|null $data
     */
    public function __construct(
        InvalidResponse $apiResponseInvalid,
        ConfigHelper $searchHelperConfig,
        StoreManagerInterface $storeModelStoreManagerInterface,
        $requestModel = null,
        $responseModel = null,
        $data = []
    ) {
        $this->_apiResponseInvalid = $apiResponseInvalid;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->requestModel = $requestModel ?: static::DEFAULT_REQUEST_MODEL;
        $this->responseModel = $responseModel ?: static::DEFAULT_RESPONSE_MODEL;

        parent::__construct($data);
    }

    /**
     * Set the request model to use for this API action.
     *
     * @param Request $request_model
     *
     * @return $this
     *
     * @deprecated due to hardcoded type hint
     * @see setRequestModel
     */
    public function setRequest(Request $request_model)
    {
        $this->request = $request_model;

        return $this;
    }

    /**
     * @param mixed $requestModel
     *
     * @return void
     */
    public function setRequestModel($requestModel)
    {
        $this->request = $requestModel;
    }

    /**
     * Return the request model used for this API action.
     *
     * @return Request
     */
    public function getRequest()
    {
        if (!$this->request) {
            $this->request = ObjectManager::getInstance()->get($this->requestModel);
        }

        return $this->request;
    }

    /**
     * Set the response model to use for this API action.
     *
     * @param Response $response_model
     *
     * @return $this
     *
     * @deprecated due to hardcoded type hint
     * @see setResponseModel
     */
    public function setResponse(Response $response_model)
    {
        $this->response = $response_model;

        return $this;
    }

    /**
     * @param mixed $responseModel
     *
     * @return void
     */
    public function setResponseModel($responseModel)
    {
        $this->response = $responseModel;
    }

    /**
     * Return the response model used for this API action.
     *
     * @return Response
     */
    public function getResponse()
    {
        if (!$this->response) {
            $this->response = ObjectManager::getInstance()->get($this->responseModel);
        }

        return $this->response;
    }

    /**
     * Execute the API action with the given parameters.
     *
     * @param array $parameters
     *
     * @return Response
     * @throws LocalizedException
     */
    public function execute($parameters)
    {
        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return $this->_apiResponseInvalid->setErrors($validation_result);
        }
        switch (true) {
            case !isset($parameters['store']):
                $store = $this->getStore();
                break;

            case is_numeric($parameters['store']):
                try {
                    $store = $this->_storeModelStoreManagerInterface->getStore($parameters['store']);
                } catch (NoSuchEntityException $e) {
                    throw new LocalizedException(__('Could not find store with id %1', $parameters['store']));
                }
                break;

            case $parameters['store'] instanceof StoreInterface:
                $store = $parameters['store'];
                break;

            default:
                throw new LocalizedException(__(
                    'Invalid store parameter: %1',
                    is_object($parameters['store']) ? get_class($parameters['store']) : gettype($parameters['store'])
                ));
                break;
        }
        $endpoint = $this->buildEndpoint(
            static::ENDPOINT,
            $store,
            $this->_searchHelperConfig->getHostname($store)
        );
        $request = $this->getRequest();
        $request->setResponseModel($this->getResponse());
        $request->setEndpoint($endpoint);
        $request->setMethod(static::METHOD);
        $request->setData($parameters);

        return $request->send();
    }

    /**
     * Get the store used for this request
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
     * Validate the given parameters against the API action specification and
     * return true if validation passed or an array of validation error messages
     * otherwise.
     *
     * @param $parameters
     *
     * @return bool|array
     */
    protected function validate($parameters)
    {
        return true;
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
        return static::ENDPOINT_PROTOCOL
            . ($hostname ?: $this->_searchHelperConfig->getHostname($store))
            . $endpoint;
    }
}
