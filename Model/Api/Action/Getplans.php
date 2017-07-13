<?php

namespace Klevu\Search\Model\Api\Action;

class Getplans extends \Klevu\Search\Model\Api\Actionall
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
    
    /**
      * @var \Magento\Store\Model\StoreManagerInterface
      */
    protected $_storeModelStoreManagerInterface;

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

    const ENDPOINT = "/analytics/getBillingDetailsOfUser";
    const METHOD   = "POST";

    const DEFAULT_REQUEST_MODEL = "Klevu\Search\Model\Api\Request\Post";
    const DEFAULT_RESPONSE_MODEL = "Klevu\Search\Model\Api\Response\Data";

    protected function validate($parameters) {

        $errors = array();
       
        if (!isset($parameters["store"])) {
            $errors["store"] = "Missing store value.";
        }
          
        if (count($errors) == 0) {
            return true;
        }
        return $errors;
    }
	
	/**
     * Execute the API action with the given parameters.
     *
     * @param array $parameters
     *
     * @return Klevu_Search_Model_Api_Response
     */
    public function execute($parameters = array())
	{

        $validation_result = $this->validate($parameters);
        if ($validation_result !== true) {
            return $this->_apiResponseInvalid->setErrors($validation_result);
        }

        $request = $this->getRequest();
     
		$endpoint = $this->buildEndpoint(
            static::ENDPOINT,
            $this->getStore(),
            $this->_searchHelperConfig->getHostname($this->getStore())
        );
		
        $request
            ->setResponseModel($this->getResponse())
            ->setEndpoint($endpoint)
            ->setMethod(static::METHOD)
            ->setData($parameters);
        return $request->send();
       
    }
	
	public function buildEndpoint($endpoint, $store = null, $hostname = null)
    {
        return static::ENDPOINT_PROTOCOL . (($hostname) ? $hostname : $this->_searchHelperConfig->getHostname($store)) . $endpoint;
    }
}
