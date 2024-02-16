<?php

namespace Klevu\Search\Model\Api\Action;

use Klevu\Search\Helper\Api as ApiHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Model\Api\Actionall as ApiActionall;
use Klevu\Search\Model\Api\Request\Xml as RequestXml;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Model\Api\Response\Invalid as ApiResponseInvalid;
use Klevu\Search\Model\Api\Response\Message as ResponseMessage;
use Klevu\Search\Model\Api\Response\Rempty as EmptyResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class Startsession extends ApiActionall
{
    const ENDPOINT = "/rest/service/startSession";
    const METHOD = "POST";
    const DEFAULT_REQUEST_MODEL = RequestXml::class;
    const DEFAULT_RESPONSE_MODEL = ResponseMessage::class;

    /**
     * @var ApiResponseInvalid
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
     * @param ApiResponseInvalid $apiResponseInvalid
     * @param ApiHelper $searchHelperApi
     * @param StoreManagerInterface $storeModelStoreManagerInterface
     * @param ConfigHelper $searchHelperConfig
     * @param string|null $requestModel
     * @param string|null $responseModel
     */
    public function __construct(
        ApiResponseInvalid $apiResponseInvalid,
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
     * @return Response|EmptyResponse
     * @throws LocalizedException
     */
    public function execute($parameters)
    {
        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return $this->_apiResponseInvalid->setErrors($validation_result);
        }
        if (isset($parameters['store'])) {
            switch (true) {
                case is_string($parameters['store']):
                case is_int($parameters['store']):
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
                        is_object($parameters['store'])
                            ? get_class($parameters['store'])
                            //phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                            : gettype($parameters['store'])
                    ));
            }
            $this->setDataUsingMethod('store', $store);
        }
        try {
            $store = $this->getStore();
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(
                __('An internal error occurred while retrieving the active store'),
                $e
            );
        }
        $endpoint = $this->buildEndpoint(
            static::ENDPOINT,
            $store,
            $this->_searchHelperConfig->getRestHostname($store)
        );
        $request = $this->getRequest();
        $request->setData([]);
        $request->setResponseModel($this->getResponse());
        $request->setEndpoint($endpoint);
        $request->setMethod(static::METHOD);
        $request->setHeader('Authorization', $parameters['api_key']);

        return $request->send();
    }

    /**
     * Get the store used for this request.
     *
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
     * @param array $parameters
     *
     * @return bool|string[]
     */
    protected function validate($parameters)
    {
        $errors = [];
        if (empty($parameters['api_key'])) {
            $errors[] = (string)__("Missing API key.");
        }

        return $errors ?: true;
    }

    /**
     * @param string $endpoint
     * @param StoreInterface|null $store
     * @param string $hostname
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
