<?php

namespace Klevu\Search\Model\Api;

use Exception;
use Klevu\Logger\Constants as LoggerConstants;
use Magento\Framework\App\ObjectManager;

class Request extends \Magento\Framework\DataObject
{
    /**
     * @var Response
     */
    protected $_modelApiResponse;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;

    /**
     * @var \Klevu\Search\Model\Api\Response\Empty
     */
    protected $_apiResponseEmpty;
    /**
     * @var string[]
     */
    private $maskFields = array('restApiKey', 'email', 'password', 'Authorization');
    public function __construct(
        Response $modelApiResponse,
        \Klevu\Search\Helper\Data $searchHelperData,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        \Klevu\Search\Model\Api\Response\Rempty $apiResponseEmpty
    ) {

        $this->_modelApiResponse = $modelApiResponse;
        $this->_searchHelperData = $searchHelperData;
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_apiResponseEmpty = $apiResponseEmpty;

        parent::__construct();
    }

    protected $endpoint;

    protected $method;

    protected $headers;

    protected $response_model;

    public function _construct()
    {
        parent::_construct();

        $this->method = \Zend\Http\Client::GET;
        $this->headers = [];
        $this->response_model = $this->_modelApiResponse;
    }

    /**
     * Set the target endpoint URL for this API request.
     *
     * @param $url
     *
     * @return $this
     */
    public function setEndpoint($url)
    {
        $this->endpoint = $url;

        return $this;
    }

    /**
     * Return the target endpoint for this API request.
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Set the HTTP method to use for this API request.
     *
     * @param $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Get the HTTP method configured for this API request.
     *
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set a HTTP header for this API request.
     *
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers = [$name => $value];

        return $this;
    }

    /**
     * Get the array of HTTP headers configured for this API request.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Set the response model to use for this API request.
     *
     * @param Response $response_model
     *
     * @return $this
     */
    public function setResponseModel(Response $response_model)
    {
        $this->response_model = $response_model;

        return $this;
    }

    /**
     * Return the response model used for this API request.
     *
     * @return Response
     */
    public function getResponseModel()
    {
        return $this->response_model;
    }

    /**
     * Perform the API request and return the received response.
     *
     * @return Response
     * @throws Exception
     */
    public function send()
    {
        if (!$this->getEndpoint()) {
            // Can't make a request without a URL
            throw new Exception("Unable to send a Klevu Search API request: No URL specified.");
        }
        $logLevel = $this->_searchHelperConfig->getLogLevel();

        $raw_request = $this->build();
        if ($logLevel === LoggerConstants::ZEND_LOG_DEBUG) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_DEBUG,
                sprintf("API EndPoint: %s", $this->getEndpoint())
            );
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_DEBUG,
                sprintf("API request:\n%s", $this->__toString())
            );
        }
        try {
            $raw_response = $raw_request->send();
        } catch (\Zend\Http\Client\Exception $e) {
            // Return an empty response
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf("HTTP error: %s", $e->getMessage())
            );
            return $this->_apiResponseEmpty;
        }
        $content = $raw_response->getBody();
        if ($logLevel >= LoggerConstants::ZEND_LOG_DEBUG) {
            $content = $this->applyMaskingOnResponse($content);
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_DEBUG, sprintf(
                "API response:\n%s",
                $content
            ));
        }
        $response = $this->getResponseModel();
        $response->setRawResponse($raw_response);

        return $response;
    }

    /**
     * Return the string representation of the API request.
     *
     * @return string
     */
    public function __toString()
    {
        $headers = $this->getHeaders();
        if (!$headers) {
            return '';
        }

        array_walk($headers, function (&$value, $key) {
            $value = ($value !== null && $value !== false) ? sprintf("%s: %s", $key, $value) : null;
            if (in_array($key, $this->maskFields)) {
                $value = sprintf("%s: %s", $key, '***************');
            }
        });

        return sprintf(
            "%s %s\n%s\n",
            $this->getMethod(),
            $this->getEndpoint(),
            implode("\n", array_filter($headers))
        );
    }

    /**
     * Build the HTTP request to be sent.
     *
     * @return \Zend\Http\Client
     */
    protected function build()
    {
        $client = ObjectManager::getInstance()->get('Zend\Http\Client');
        if (!empty($this->getHeaders())) {
            $client
                ->setUri($this->getEndpoint())
                ->setMethod($this->getMethod())
                ->setOptions(['sslverifypeer' => false])
                ->setHeaders($this->getHeaders());
        } else {
            $client
                ->setUri($this->getEndpoint())
                ->setOptions(['sslverifypeer' => false])
                ->setMethod($this->getMethod());
        }

        return $client;
    }

    /**
     * Applying masking for sensitive fields
     *
     * @param $content
     * @return string
     */
    private function applyMaskingOnResponse($content)
    {
        $originalString = $content;
        try {
            switch ($content) {
                case strpos($content, 'email') !== false:
                    $emailPattern = '/[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})/';
                    if (preg_match($emailPattern, $content, $email_string)) {
                        //checking for first full pattern only
                        $content = str_replace($email_string[0], '***********', $content);
                    }
                    break;
                case strpos($content, 'restApiKey') !== false:
                    if (preg_match_all("%(<restApiKey>).*?(</restApiKey>)%i", $content, $restApi)) {
                        $content = str_replace($restApi[0], '<restApiKey>**********</restApiKey>', $content);
                    }
                    break;
                default:
                    break;
            }
        } catch (Exception $e) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, sprintf("Exception while masking: %s", $e->getMessage()));
            return $originalString;
        }
        return $content;
    }
}
