<?php
/**
 * Klevu Product API model for preserve layout
 */

namespace Klevu\Search\Model\Api\Magento\Request;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Config as KlevuHelperConfig;
use Klevu\Search\Helper\Data as KlevuHelperData;
use Klevu\Search\Model\Api\Action\Idsearch as KlevuApiIdsearch;
use Klevu\Search\Model\Api\Action\Searchtermtracking as KlevuApiSearchtermtracking;
use Klevu\Search\Model\Api\Response;
use \Magento\Framework\App\Request\Http as Magento_Request;
use \Magento\Catalog\Model\CategoryFactory as Magento_CategoryFactory;
use \Magento\Catalog\Model\Category as Category_Model;

class Product implements ProductInterface
{
    /**
     * Klevu Search API Parameters
     * @var array
     */
    protected $_klevu_parameters;
    /**
     * @var mixed
     */
    protected $_klevu_tracking_parameters;
    /**
     * @var string
     */
    protected $_klevu_type_of_records = 'KLEVU_PRODUCT';
    /**
     * Klevu Search API Product IDs
     * @var array
     */
    protected $_klevu_product_ids = [];
    /**
     * @var array
     */
    protected $_klevu_parent_child_ids = [];
    /**
     * @var array
     */
    protected $_klevu_variant_parent_child_ids = [];
    /**
     * Klevu Search API Response
     * @var Response
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
     * @var KlevuHelperConfig
     */
    protected $_searchHelperConfig;
    /**
     * @var KlevuHelperData
     */
    protected $_searchHelperData;
    /**
     * @var KlevuApiIdsearch
     */
    protected $_apiActionIdsearch;
    /**
     * @var KlevuApiSearchtermtracking
     */
    protected $_apiActionSearchtermtracking;
    /**
     * @var Magento_Request
     */
    protected $_magentoRequest;
    /**
     * @var Magento_CategoryFactory
     */
    protected $_magentoCategoryFactory;
    /**
     * @var Category_Model
     */
    protected $_categoryModel;

    /**
     * @param KlevuHelperConfig $searchHelperConfig
     * @param KlevuHelperData $searchHelperData
     * @param KlevuApiIdsearch $apiActionIdsearch
     * @param KlevuApiSearchtermtracking $apiActionSearchtermtracking
     * @param Magento_Request $magentoRequest
     * @param Magento_CategoryFactory $magentoCategoryFactory
     * @param Category_Model $categoryModel
     */
    public function __construct(
        KlevuHelperConfig $searchHelperConfig,
        KlevuHelperData $searchHelperData,
        KlevuApiIdsearch $apiActionIdsearch,
        KlevuApiSearchtermtracking $apiActionSearchtermtracking,
        Magento_Request $magentoRequest,
        Magento_CategoryFactory $magentoCategoryFactory,
        Category_Model $categoryModel
    ) {
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_searchHelperData = $searchHelperData;
        $this->_apiActionIdsearch = $apiActionIdsearch;
        $this->_apiActionSearchtermtracking = $apiActionSearchtermtracking;
        $this->_magentoRequest = $magentoRequest;
        $this->_magentoCategoryFactory = $magentoCategoryFactory;
        $this->_categoryModel = $categoryModel;
    }

    /**
     * This method executes the Klevu API request if it has not already been called, and takes the result
     * with the result we get all the item IDs, pass into our helper which returns the child and parent id's.
     * We then add all these values to our class variable $_klevu_product_ids.
     *
     * @param mixed $query
     *
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
                        $this->_klevu_variant_parent_child_ids[$item_id['parent_id']] = $item_id['product_id'];
                    } else {
                        $this->_klevu_product_ids[$item_id['product_id']] = $item_id['product_id'];
                    }
                } else {
                    if ($key === "id") {
                        $item_id = $this->_searchHelperData->getMagentoProductId((string)$result);
                        $this->_klevu_parent_child_ids[] = $item_id;
                        if ($item_id['parent_id'] != 0) {
                            $this->_klevu_product_ids[$item_id['parent_id']] = $item_id['parent_id'];
                            $this->_klevu_variant_parent_child_ids[$item_id['parent_id']] = $item_id['product_id'];
                        } else {
                            $this->_klevu_product_ids[$item_id['product_id']] = $item_id['product_id'];
                        }
                    }
                }
            }

            $this->_klevu_product_ids = array_unique($this->_klevu_product_ids);
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_DEBUG,
                sprintf("Products count returned: %s", count($this->_klevu_product_ids))
            );
        }
        $this->_klevu_product_ids = array_values($this->_klevu_product_ids);

        return $this->_klevu_product_ids;
    }

    /**
     * Send the API Request and return the API Response.
     *
     * @param mixed $query
     *
     * @return Response
     */
    private function getKlevuResponse($query)
    {
        if (!$this->_klevu_response) {
            $this->_klevu_response = $this->_apiActionIdsearch->execute($this->getSearchFilters($query));
        }

        return $this->_klevu_response;
    }

    /**
     * Return the Klevu api search filters
     *
     * @param mixed $query
     *
     * @return array
     */
    private function getSearchFilters($query)
    {
        $category = $this->_klevu_type_of_records;
        try {
            $categoryFilter = $this->_magentoRequest->getParam('productFilter');
            $categoryFilter = isset($categoryFilter)
                ? $categoryFilter
                : '';
            $categoryFilterValue = explode(":", $categoryFilter);
            if (is_array($categoryFilterValue)) {
                if (isset($categoryFilterValue[1])) {
                    $category_name = $categoryFilterValue[1];
                    $currentCategory = $this->_magentoCategoryFactory->create()
                        ->loadByAttribute('name', $category_name);
                    $catNames = [];
                    foreach ($currentCategory->getParentCategories() as $parent) {
                        $catNames[] = $parent->getName();
                    }
                    $allCategoryNames = implode(";", $catNames);
                    $catNames = [];
                    $pathIds = $currentCategory->getPathIds();
                    if (!empty($pathIds)) {
                        unset($pathIds[0]);
                        unset($pathIds[1]);
                        foreach ($pathIds as $key => $value) {
                            $catNames[] = $this->_categoryModel->load($value)->getName();
                        }
                        $allCategoryNames = implode(";", $catNames);
                    }

                    $category = $this->_klevu_type_of_records . " " . $allCategoryNames;
                }
            } else {
                $category = $this->_klevu_type_of_records;
            }
        } catch (\Exception $e) {
            // Catch the exception that was thrown, log it
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage())
            );
            $category = $this->_klevu_type_of_records;
        }

        if (empty($this->_klevu_parameters)) {
            $this->_klevu_parameters = [
                'ticket' => $this->_searchHelperConfig->getJsApiKey(),
                'noOfResults' => 2000,
                'term' => $query,
                'paginationStartsFrom' => 0,
                'enableFilters' => 'false',
                'klevuShowOutOfStockProducts' => 'true',
                'category' => $category,
                'visibility' => 'search',
            ];
        }

        return $this->_klevu_parameters;
    }

    /**
     * This method resets the saved $_klevu_product_ids.
     * @return bool
     */
    public function reset()
    {
        $this->_klevu_product_ids = null;

        return true;
    }

    /**
     * This method will return the parent child ids
     * @return array
     */
    public function getKlevuVariantParentChildIds()
    {
        if (!empty($this->_klevu_variant_parent_child_ids)) {
            return $this->_klevu_variant_parent_child_ids;
        }

        return [];
    }
}
