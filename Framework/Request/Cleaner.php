<?php
/**
 * Klevu override of the request Cleaner for use on preserve layout
 */
namespace Klevu\Search\Framework\Request;

use Magento\Framework\Search\Request\Aggregation\StatusInterface as AggregationStatus;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\App\Config\MutableScopeConfigInterface as MutableScopeConfigInterface;
use Magento\Framework\Registry as MagentoRegistry;
use Magento\Store\Model\ScopeInterface as ScopeInterface;
use Magento\CatalogSearch\Model\ResourceModel\EngineInterface as EngineInterface;
use Klevu\Search\Model\Api\Magento\Request\ProductInterface as KlevuProductApi;
use Klevu\Search\Helper\Config as KlevuConfig;
use \Magento\Framework\App\Request\Http as Magento_Request;
use Klevu\Search\Model\ContextFE as KlevuCoreContext;

class Cleaner extends \Magento\Framework\Search\Request\Cleaner
{

    protected  $sessionManager;
    protected  $klevuRequest;
    protected  $klevuConfig;
    protected  $mutableScopeConfigInterface;
    protected  $magentoRegistry;
    protected  $magentoRequest;
    protected  $klevuCoreContext;

    /**
     * Cleaner constructor
     *
     * @param AggregationStatus $aggregationStatus
     * @param SessionManagerInterface $sessionManagerInterface
     * @param MutableScopeConfigInterface $mutableScopeConfigInterface
     * @param MagentoRegistry $magentoRegistry
     * @param KlevuProductApi $klevuRequest
     * @param KlevuConfig $klevuConfig
     * @param Magento_Request $magentoRequest
     * @param KlevuCoreContext $klevuCoreContext
     */
    public function __construct(
        AggregationStatus $aggregationStatus,
        SessionManagerInterface $sessionManagerInterface,
        MutableScopeConfigInterface $mutableScopeConfigInterface,
        MagentoRegistry $magentoRegistry,
        KlevuProductApi $klevuRequest,
        KlevuConfig $klevuConfig,
        Magento_Request $magentoRequest,
        KlevuCoreContext $klevuCoreContext
    )
    {

        $this->sessionManager = $sessionManagerInterface;
        $this->mutableScopeConfigInterface = $mutableScopeConfigInterface;
        $this->magentoRegistry = $magentoRegistry;
        $this->klevuRequest = $klevuRequest;
        $this->klevuConfig = $klevuConfig;
        $this->magentoRequest = $magentoRequest;
        $this->klevuCoreContext = $klevuCoreContext;
        if (is_callable('parent::__construct')) {
            parent::__construct($aggregationStatus);
        }
    }

    /**
     * Clean not binder queries and filters
     *
     * @param array $requestData
     * @return array
     */
    public function clean(array $requestData)
    {
        $requestData = parent::clean($requestData);
        $requestData = $this->klevuQueryCleanup($requestData);
        return $requestData;
    }

    public function getKlevuContext(){
        return $this->klevuCoreContext;
    }

    /**
     * @return string
     */
    private function getProductIdField() {
        //decide based on the engine settings what to use as filter
        $currentEngine = $this->mutableScopeConfigInterface->getValue(EngineInterface::CONFIG_ENGINE_PATH, ScopeInterface::SCOPE_STORE);
        switch ($currentEngine) {
            case "elasticsearch":
                return '_id';
                break;
            case "elasticsearch5":
                return '_id';
                break;
            case "solr":
                return '_id';
                break;
            default:
                return 'entity_id';
                break;
        }

    }

    /**
     * @param $requestData array
     * @return array
     */
    public function klevuQueryCleanup($requestData){
        $categoryFilter = $this->magentoRequest->getParam('productFilter');
        $categoryFilter = isset($categoryFilter) ? $categoryFilter : '';
        // check if we are in search page
        if(!isset($requestData['queries']['quick_search_container'])) return $requestData;
        //check if klevu is supposed to be on
        if ($this->klevuConfig->isLandingEnabled()!=1 || !$this->klevuConfig->isExtensionConfigured()) return $requestData;
        //save data in session so we do not request via api for filters
        $queryTerm = $requestData['queries']['search']['value'];
        $queryScope = $requestData['dimensions']['scope']['value'];
        $idList = $this->sessionManager->getData('ids_'.$queryScope.'_'.$queryTerm.$categoryFilter);
        if(!$idList){
            $idList = $this->klevuRequest->_getKlevuProductIds($queryTerm);
            if(empty($idList)) $idList = array(0);
            $this->sessionManager->setData('ids_'.$queryScope.'_'.$queryTerm.$categoryFilter,$idList );
        }
        //register the id list so it will be used when ordering
        $this->magentoRegistry->unregister('search_ids');
        $this->magentoRegistry->register('search_ids', $idList);
        //find the search query term and override the processor
        foreach($requestData['queries']['quick_search_container']['queryReference'] as $key => $filter){
            if($filter['ref'] == 'search') $requestData['queries']['quick_search_container']['queryReference'][$key] = array('clause' =>'must','ref' => 'klevu_id_search');
        }
        //unset original handler so it will not interfere with query
        unset($requestData['queries']['search']);
        //build the new handler for the ids
        $requestData['queries']['klevu_id_search'] = array(
            'name' => 'klevu_id_search',
            'filterReference' => array(
                array(
                    'ref' => 'pid',
                ),
            ),
            'type' => 'filteredQuery',
        );
        $requestData['filters']['pid'] = array(
            'name' => 'pid',
            'filterReference' => array(
                array(
                    'clause' => 'must',
                    'ref' => 'pidsh',
                ),
            ),
            'type' => 'boolFilter',
        );
        $requestData['filters']['pidsh'] = array(
            'name' => 'pidsh',
            'field' => $this->getProductIdField(),
            'type' => 'termFilter',
            'value' => $idList
        );
        return $requestData;
    }
}