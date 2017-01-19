<?php
namespace Klevu\Search\Model\Api\Action;

class Debuginfo extends \Klevu\Search\Model\Api\Actionall {
	
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


    public function __construct(\Klevu\Search\Model\Api\Response\Invalid $apiResponseInvalid, 
        \Klevu\Search\Helper\Api $searchHelperApi,
		\Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,		
        \Klevu\Search\Helper\Config $searchHelperConfig)
    {
        $this->_apiResponseInvalid = $apiResponseInvalid;
        $this->_searchHelperApi = $searchHelperApi;
        $this->_searchHelperConfig = $searchHelperConfig;
		$this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;

    }
  
  
    const ENDPOINT = "/n-search/logReceiver";
    const METHOD   = "POST";
    
    const DEFAULT_REQUEST_MODEL = "Klevu\Search\Model\Api\Request\Post";
    const DEFAULT_RESPONSE_MODEL = "Klevu\Search\Model\Api\Response\Data";
	
	public function debugKlevu($parameters)
	{
        $endpoint = $this->buildEndpoint(static::ENDPOINT, $this->_storeModelStoreManagerInterface->getStore(),$this->_searchHelperConfig->getHostname($this->_storeModelStoreManagerInterface->getStore()));
	   $response = $this->getResponse();
	   $request = $this->getRequest();
       $request
            ->setResponseModel($response)
            ->setEndpoint($endpoint)
            ->setMethod(static::METHOD)
            ->setData($parameters);
        return $request->send();
	}
	
	
	public function buildEndpoint($endpoint, $store = null, $hostname = null) {
       
        return static::ENDPOINT_PROTOCOL . (($hostname) ? $hostname : $this->_searchHelperConfig->getHostname($store)) . $endpoint;
    }
}
