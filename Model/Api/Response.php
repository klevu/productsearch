<?php

/**
 * Class \Klevu\Search\Model\Api\Response
 *
 * @method setMessage($message)
 * @method getMessage()
 */
namespace Klevu\Search\Model\Api;

use Klevu\Logger\Constants as LoggerConstants;

class Response extends \Magento\Framework\DataObject
{
    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

	/**
     * @var \Magento\AdminNotification\Model\InboxFactory
     */
    protected $_messageManager;



    public function __construct(\Klevu\Search\Helper\Data $searchHelperData,
	\Magento\AdminNotification\Model\InboxFactory $messageManager)
    {
        $this->_searchHelperData = $searchHelperData;
		$this->_messageManager = $messageManager;

        parent::__construct();
    }

    protected $raw_response;
    protected $successful;
    protected $xml;

    public function _construct()
    {
        parent::_construct();

        $this->successful = false;
    }

    /**
     * Set the raw response object representing this API response.
     *
     * @param \Zend\Http\Response $response
     *
     * @return $this
     */
    public function setRawResponse(\Zend\Http\Response $response)
    {
        $this->raw_response = $response;

        $this->parseRawResponse($response);

        return $this;
    }

    /**
     * Check if the API response indicates success.
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->successful;
    }

    /**
     * Return the response XML content.
     *
     * @return SimpleXMLElement
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * Extract the API response data from the given HTTP response object.
     *
     * @param \Zend\Http\Response $response
     *
     * @return $this
     */
    protected function parseRawResponse(\Zend\Http\Response $response)
    {
        if ($response->isSuccess()) {
            $content = $response->getBody();
            if (strlen($content) > 0) {
                try {
                    $xml = simplexml_load_string($response->getBody());
                } catch (\Exception $e) {
                    // Failed to parse XML
                    $this->successful = false;
                    $this->setMessage("Failed to parse a response from Klevu.");
                    $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, sprintf("Failed to parse XML response: %s", $e->getMessage()));
                    return $this;
                }

                $this->xml = $xml;
                $this->successful = true;
            } else {
                // Response contains no content
                $this->successful = false;
                $this->setMessage('Failed to parse a response from Klevu.');
                $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, "API response content is empty.");
            }
        } else {
            // Unsuccessful HTTP response
            $this->successful = false;
            switch ($response->getStatusCode()) {
                case 403:
                    $message = "Incorrect API keys.";
                    break;
                case 500:
                    $message = "API server error.";
                    break;
                case 503:
                    $message = "API server unavailable.";
                    break;
				case 400:
                    $message = "Klevu Product sync has issues indexing your products. <b>".$this->_searchHelperData->getBaseDomain()."</b> is not listed as an allowed base URL for the Klevu Search API key <b>'".$this->_searchHelperData->getJsApiKey()."'</b>. Please <a href='http://support.klevu.com/knowledgebase/base-urls
' target='_blank'>click here</a> for more information.";
					break;
                default:
                    $message = "Unexpected error.";
            }
            if($response->getStatusCode() == 400) {
				$storefromscope = $this->_searchHelperData->storeFromScopeId();

				$datatest[] = [
					'severity' => "1",
					'title' => "Klevu : Product Sync failed",
					'date_added' => date('Y-m-d H:i:s'),
					'description' => sprintf("Product Sync failed for %s (%s): %s",
						$storefromscope->getWebsite()->getName(),
						$storefromscope->getName(),
						$message),
					'url' => "http://support.klevu.com/knowledgebase/base-urls/"
				];

				$this->_messageManager->create()->parse($datatest);


				$this->setMessage(sprintf("Product Sync failed for %s (%s): %s",
						$storefromscope->getWebsite()->getName(),
						$storefromscope->getName(),
						$message));
			} else {
				$this->setMessage(sprintf("Failed to connect to Klevu: %s", $message));
			}
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, sprintf("Unsuccessful HTTP response: %s %s", $response->getStatusCode(), $response->toString()));
        }

        return $this;
    }

    /**
     * Added to allow test suite to mock object
     *
     * @return string|null
     */
    public function getMessage()
    {
        return $this->getData('message');
    }
}
