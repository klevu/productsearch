<?php

namespace Klevu\Search\Model\Api;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Data as SearchHelper;
use Magento\AdminNotification\Model\InboxFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use SimpleXMLElement;

/**
 * @method setMessage($message)
 */
class Response extends DataObject
{
    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;
    /**
     * @var InboxFactory
     */
    protected $_messageManager;
    /**
     * @var mixed
     */
    protected $raw_response;
    /**
     * @var bool
     */
    protected $successful;
    /**
     * @var SimpleXMLElement
     */
    protected $xml;

    /**
     * @param SearchHelper $searchHelperData
     * @param InboxFactory $messageManager
     */
    public function __construct(
        SearchHelper $searchHelperData,
        InboxFactory $messageManager
    ) {
        $this->_searchHelperData = $searchHelperData;
        $this->_messageManager = $messageManager;

        parent::__construct();
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        $this->successful = false;
    }

    /**
     * Set the raw response object representing this API response.
     *
     * @param mixed $response
     *
     * @return $this
     * @throws NoSuchEntityException
     */
    public function setRawResponse($response)
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
     * @param mixed $response
     *
     * @return $this
     * @throws NoSuchEntityException
     */
    protected function parseRawResponse($response)
    {
        if ($response->isSuccess()) {
            $content = $response->getBody();
            if (strlen((string)$content) > 0) {
                try {
                    $xml = simplexml_load_string($response->getBody());
                } catch (\Exception $e) {
                    // Failed to parse XML
                    $this->successful = false;
                    $this->setMessage("Failed to parse a response from Klevu.");
                    $this->_searchHelperData->log(
                        LoggerConstants::ZEND_LOG_ERR,
                        sprintf("Failed to parse XML response: %s", $e->getMessage()),
                    );

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
                    $message = "Klevu Product sync has issues indexing your products. "
                        . "<b>" . $this->_searchHelperData->getBaseDomain() . "</b>"
                        . " is not listed as an allowed base URL for the Klevu Search API key "
                        . "<b>'" . $this->_searchHelperData->getJsApiKey() . "'</b>
                        . Please "
                        . "<a href='https://help.klevu.com/support/solutions/articles/5000871512-secure-base-urls'"
                        . " target='_blank'>click here</a> for more information.";
                    break;
                default:
                    $message = "Unexpected error.";
            }
            if ($response->getStatusCode() == 400) {
                $storefromscope = $this->_searchHelperData->storeFromScopeId();

                $datatest[] = [
                    'severity' => "1",
                    'title' => "Klevu : Product Sync failed",
                    'date_added' => date('Y-m-d H:i:s'),
                    'description' => sprintf("Product Sync failed for %s (%s): %s",
                        $storefromscope->getWebsite()->getName(),
                        $storefromscope->getName(),
                        $message),
                    'url' => "https://help.klevu.com/support/solutions/articles/5000871512-secure-base-urls/",
                ];
                $this->_messageManager->create()->parse($datatest);

                $this->setMessage(sprintf("Product Sync failed for %s (%s): %s",
                    $storefromscope->getWebsite()->getName(),
                    $storefromscope->getName(),
                    $message));
            } else {
                $this->setMessage(sprintf("Failed to connect to Klevu: %s", $message));
            }
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf("Unsuccessful HTTP response: %s %s", $response->getStatusCode(), $response->toString()),
            );
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
