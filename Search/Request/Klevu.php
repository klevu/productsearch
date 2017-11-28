<?php 
namespace Klevu\Search\Search\Request;
use \Klevu\Search\Helper\Config as KlevuHelperConfig;
use \Klevu\Search\Helper\Data as KlevuHelperData;
use \Klevu\Search\Model\Api\Action\Idsearch as KlevuApiIdsearch;
use \Klevu\Search\Model\Api\Action\Searchtermtracking as KlevuApiSearchtermtracking;
use \Zend\Log\Logger as Logger;

class Klevu
{

    /**
     * Klevu Search API Parameters
     * @var array
     */
    protected $_klevu_parameters;
    protected $_klevu_tracking_parameters;
    protected $_klevu_type_of_records = 'KLEVU_PRODUCT';
    /**
     * Klevu Search API Product IDs
     * @var array
     */
    protected $_klevu_product_ids = [];
    protected $_klevu_parent_child_ids = [];
    /**
     * Klevu Search API Response
     * @var \Klevu\Search\Model\Api\Response
     */
    protected $_klevu_response;
    /**
     * Search query
     * @var string
     */
    protected $_query;
    /**
     * Total number of results found
     * @var int
     */
    protected $_klevu_size;
    /**
     * The XML Response from Klevu
     * @var SimpleXMLElement
     */
    protected $_klevu_response_xml;
    /**
     * @var \Klevu\Search\Model\Api\Action\Idsearch
     */
    protected $_apiActionIdsearch;

    
    public function __construct(
        KlevuHelperConfig $searchHelperConfig,
        KlevuHelperData $searchHelperData,
        KlevuApiIdsearch $apiActionIdsearch,
        KlevuApiSearchtermtracking $apiActionSearchtermtracking
    ) {
    
 
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_searchHelperData = $searchHelperData;
        $this->_apiActionIdsearch = $apiActionIdsearch;
        $this->_apiActionSearchtermtracking = $apiActionSearchtermtracking;
    }


    /**
     * Return the Klevu api search filters
     * @param $query
     * @return array
     */
    private function getSearchFilters($query)
    {
        if (empty($this->_klevu_parameters)) {
            $this->_klevu_parameters = [
                'ticket' => $this->_searchHelperConfig->getJsApiKey() ,
                'noOfResults' => 2000,
                'term' => $query,
                'paginationStartsFrom' => 0,
                'enableFilters' => 'false',
                'klevuShowOutOfStockProducts' => 'true',
                'category' => $this->_klevu_type_of_records
            ];
        }

        return $this->_klevu_parameters;
    }

    /**
     * Send the API Request and return the API Response.
     * @param $query
     * @return \Klevu\Search\Model\Api\Response
     */
    private function getKlevuResponse($query)
    {
        if (!$this->_klevu_response) {
            $this->_klevu_response = $this->_apiActionIdsearch->execute($this->getSearchFilters($query));
        }

        return $this->_klevu_response;
    }

    /**
     * This method executes the the Klevu API request if it has not already been called, and takes the result
     * with the result we get all the item IDs, pass into our helper which returns the child and parent id's.
     * We then add all these values to our class variable $_klevu_product_ids.
     *
     * @param $query
     * @return array
     */
    public function _getKlevuProductIds($query)
    {

        if (empty($this->_klevu_product_ids)) {
            // If no results, return an empty array

            if (!$this->getKlevuResponse($query)->hasData('result')) {
                return [];
            }

            foreach ($this->getKlevuResponse($query)->getData('result') as $key => $result) {
                if (isset($result['id'])) {
                    $item_id = $this->_searchHelperData->getMagentoProductId((string)$result['id']);
                    $this->_klevu_parent_child_ids[] = $item_id;
                    if ($item_id['parent_id'] != 0) {
                        $this->_klevu_product_ids[$item_id['parent_id']] = $item_id['parent_id'];
                    } else {
                        $this->_klevu_product_ids[$item_id['product_id']] = $item_id['product_id'];
                    }
                } else {
                    if ($key == "id") {
                        $item_id = $this->_searchHelperData->getMagentoProductId((string)$result);
                        $this->_klevu_parent_child_ids[] = $item_id;
                        if ($item_id['parent_id'] != 0) {
                            $this->_klevu_product_ids[$item_id['parent_id']] = $item_id['parent_id'];
                        } else {
                            $this->_klevu_product_ids[$item_id['product_id']] = $item_id['product_id'];
                        }
                    }
                }
            }

            $this->_klevu_product_ids = array_unique($this->_klevu_product_ids);
            $this->_searchHelperData->log(Logger::DEBUG, sprintf("Products count returned: %s", count($this->_klevu_product_ids)));
            $response_meta = $this->getKlevuResponse($query)->getData('meta');
            $this->_apiActionSearchtermtracking->execute($this->getSearchTracking(count($this->_klevu_product_ids), $query, $response_meta['typeOfQuery']));
        }
		$this->_klevu_product_ids = array_values($this->_klevu_product_ids);
        return $this->_klevu_product_ids;
    }

    /**
     * Return the Klevu api search filters
     * @param $noOfTrackingResults
     * @param $query
     * @param $queryType
     * @return array
     */
    private function getSearchTracking($noOfTrackingResults, $query, $queryType)
    {

        $this->_klevu_tracking_parameters = [
            'klevu_apiKey' => $this->_searchHelperConfig->getJsApiKey(),
            'klevu_term' => $query,
            'klevu_totalResults' => $noOfTrackingResults,
            'klevu_shopperIP' => $this->_searchHelperData->getIp(),
            'klevu_typeOfQuery' => $queryType,
            'klevu_sessionId' => md5(session_id()),
            'Klevu_typeOfRecord' => 'KLEVU_PRODUCT'
        ];
        $this->_searchHelperData->log(Logger::DEBUG, sprintf("Search tracking for term: %s", $query));
        return $this->_klevu_tracking_parameters;
    }
}
