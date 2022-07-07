<?php

namespace Klevu\Search\Model\Api\Action;

use Klevu\Search\Model\Api\Response as KlevuApiResponse;
use Magento\Framework\Exception\LocalizedException;

class Features extends \Klevu\Search\Model\Api\Actionall
{
    /**
     * @var \Klevu\Search\Model\Api\Response\Invalid
     */
    protected $_apiResponseInvalid;
    /**
     * @var \Magento\Store\Model\Store
     */
    protected $_frameworkModelStore;
    /**
     * @var \Klevu\Search\Helper\Api
     */
    protected $_searchHelperApi;
    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;
    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    public function __construct(
        \Klevu\Search\Model\Api\Response\Invalid $apiResponseInvalid,
        \Klevu\Search\Helper\Api $searchHelperApi,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Klevu\Search\Helper\Data $searchHelperData,
        \Magento\Store\Model\Store $frameworkModelStore
    ) {
        $this->_apiResponseInvalid = $apiResponseInvalid;
        $this->_searchHelperApi = $searchHelperApi;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_searchHelperData = $searchHelperData;
        $this->_frameworkModelStore = $frameworkModelStore;
    }

    const ENDPOINT = "/uti/getFeaturesAndUpgradeLink";
    const METHOD = "POST";
    const DEFAULT_REQUEST_MODEL = "Klevu\Search\Model\Api\Request\Post";
    const DEFAULT_RESPONSE_MODEL = "Klevu\Search\Model\Api\Response\Data";

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
        $request = $this->getRequest();
        $store = $this->_frameworkModelStore->load($parameters['store']);
        $endpoint = $this->buildEndpoint(
            isset($parameters['endpoint']) ? $parameters['endpoint'] : static::ENDPOINT,
            $store,
            $this->_searchHelperConfig->getTiresUrl($store)
        );
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
