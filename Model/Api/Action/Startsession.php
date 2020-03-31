<?php

namespace Klevu\Search\Model\Api\Action;

class Startsession extends \Klevu\Search\Model\Api\Actionall
{
    /**
     * @var \Klevu\Search\Model\Api\Response\Invalid
     */
    protected $_apiResponseInvalid;

    /**
     * @var \Klevu\Search\Helper\Api
     */
    protected $_searchHelperApi;

    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;

    public function __construct(
        \Klevu\Search\Model\Api\Response\Invalid $apiResponseInvalid,
        \Klevu\Search\Helper\Api $searchHelperApi,
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Klevu\Search\Helper\Config $searchHelperConfig
    ) {

        $this->_apiResponseInvalid = $apiResponseInvalid;
        $this->_searchHelperApi = $searchHelperApi;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
    }

    const ENDPOINT = "/rest/service/startSession";
    const METHOD   = "POST";

    const DEFAULT_REQUEST_MODEL  = "Klevu\Search\Model\Api\Request\Xml";
    const DEFAULT_RESPONSE_MODEL = "Klevu\Search\Model\Api\Response\Message";

    public function execute($parameters)
    {
        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return $this->_apiResponseInvalid->setErrors($validation_result);
        }

        $request = $this->getRequest();
        $endpoint = $this->buildEndpoint(static::ENDPOINT, $this->getStore(), $this->_searchHelperConfig->getRestHostname($this->getStore()));
        $request
            ->setResponseModel($this->getResponse())
            ->setEndpoint($endpoint)
            ->setMethod(static::METHOD)
            ->setHeader('Authorization', $parameters['api_key']);
            //->setData($parameters);

        return $request->send();
    }

    /**
     * Get the store used for this request.
     * @return \Magento\Framework\Model\Store
     */
    public function getStore()
    {
        if (!$this->hasData('store')) {
            $this->setData('store', $this->_storeModelStoreManagerInterface->getStore());
        }

        return $this->getData('store');
    }

    protected function validate($parameters)
    {
        if (!isset($parameters['api_key']) || empty($parameters['api_key'])) {
            return ["Missing API key."];
        } else {
            return true;
        }
    }

    public function buildEndpoint($endpoint, $store = null, $hostname = null)
    {

        return static::ENDPOINT_PROTOCOL . (($hostname) ? $hostname : $this->_searchHelperConfig->getHostname($store)) . $endpoint;
    }
}
