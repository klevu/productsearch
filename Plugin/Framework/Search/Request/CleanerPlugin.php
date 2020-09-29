<?php

namespace Klevu\Search\Plugin\Framework\Search\Request;

use Klevu\Search\Helper\Config as KlevuHelperConfig;
use Klevu\Search\Helper\Data as KlevuHelperData;
use Klevu\Search\Model\Api\Magento\Request\ProductInterface as KlevuProductApi;
use Magento\Catalog\Model\SessionFactory;
use Magento\Framework\App\Config\MutableScopeConfigInterface as MutableScopeConfig;
use Magento\Framework\App\Request\Http as MagentoRequest;
use Magento\Framework\Registry as MagentoRegistry;
use Magento\Framework\Search\Request\Cleaner as MagentoCleaner;
use Magento\Framework\Session\SessionManagerInterface as MageSessionManager;
use Magento\PageCache\Model\Config as MagentoPageCache;

/**
 * Class CleanerPlugin
 * @package Klevu\Search\Plugin\Framework\Search\Request
 */
class CleanerPlugin
{

    /**
     * @var \Magento\Catalog\Model\Session|MageSessionManager
     */
    protected $sessionObjectHandler;

    /**
     * @var MagentoRequest
     */
    protected $magentoRequest;

    /**
     * @var MagentoRegistry
     */
    protected $magentoRegistry;

    /**
     * @var MagentoCleaner
     */
    protected $magentoCleaner;

    /**
     * @var MutableScopeConfig
     */
    protected $mutableScopeConfig;

    /**
     * @var KlevuProductApi
     */
    protected $klevuProductRequest;

    /**
     * @var KlevuHelperData
     */
    protected $klevuHelperData;

    /**
     * @var KlevuHelperConfig
     */
    protected $klevuHelperConfig;

    public function __construct(
        MagentoRequest $magentoRequest,
        MagentoRegistry $mageRegistry,
        MagentoCleaner $mageCleaner,
        MagentoPageCache $magePageCache,
        MageSessionManager $sessionManager,
        MutableScopeConfig $mutableScopeConfig,
        SessionFactory $sessionFactory,
        KlevuProductApi $klevuProductRequest,
        KlevuHelperData $klevuHelperData,
        KlevuHelperConfig $klevuHelperConfig
    )
    {
        $this->magentoRequest = $magentoRequest;
        $this->magentoRegistry = $mageRegistry;
        $this->magentoCleaner = $mageCleaner;
        $this->sessionFactory = $sessionFactory;
        $this->mutableScopeConfig = $mutableScopeConfig;
        $this->klevuProductRequest = $klevuProductRequest;
        $this->klevuHelperData = $klevuHelperData;
        $this->klevuHelperConfig = $klevuHelperConfig;

        if ($magePageCache->isEnabled()) {
            $this->sessionObjectHandler = $this->sessionFactory->create();
        } else {
            $this->sessionObjectHandler = $sessionManager;
        }
    }

    /**
     * @param $subject
     * @param $result
     * @param $requestData
     */
    public function afterClean(MagentoCleaner $subject, $result)
    {
        try {
            //Check if query is for quick_search_container ( catalog search page )
            if (!isset($result['queries']['quick_search_container'])) {
            	return $result;
            }

            //Return if landing is not enabled or module found disabled
            if ($this->klevuHelperConfig->isLandingEnabled() != 1 || !$this->klevuHelperConfig->isExtensionConfigured()) {
            	return $result;
            }

            $klevuRequestData = $this->klevuQueryCleanup($result);
            return $klevuRequestData;
        } catch (\Exception $e) {
            $this->klevuHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Klevu_Search_Cleaner::Cleaner ERROR occured %s", $e->getMessage()));
            return $result;
        }
        return $result;
    }

    /**
     * Klevu Query Cleanup for Product Search
     *
     * @param $requestData
     * @return mixed
     */
    public function klevuQueryCleanup($requestData)
    {
        $categoryFilter = $this->magentoRequest->getParam('productFilter');
        $categoryFilter = isset($categoryFilter) ? $categoryFilter : '';

        //check if klevu is supposed to be on
        //if ($this->klevuHelperConfig->isLandingEnabled() != 1 || !$this->klevuHelperConfig->isExtensionConfigured()) return $requestData;

        //check if search array generated or not
        if (!isset($requestData['queries']['search'])) {
            $this->klevuHelperData->log(\Zend\Log\Logger::DEBUG,"Search array is not found in CleanerPlugin");
            return $requestData;
        } else {
            $queryTerm = $requestData['queries']['search']['value'];
        }

        //check if dimensions array found or not
        if (!isset($requestData['dimensions']['scope'])) {
            $this->klevuHelperData->log(\Zend\Log\Logger::DEBUG,"Dimension array is not found in CleanerPlugin");
            return $requestData;
        } else {
            $queryScope = $requestData['dimensions']['scope']['value'];
        }

        $idList = $this->sessionObjectHandler->getData('ids_' . $queryScope . '_' . $queryTerm . $categoryFilter);
        if (!$idList) {
            $idList = $this->klevuProductRequest->_getKlevuProductIds($queryTerm);
            if (empty($idList)) $idList = array();
            $this->sessionObjectHandler->setData('ids_' . $queryScope . '_' . $queryTerm . $categoryFilter, $idList);
        }
        //register the id list so it will be used when ordering
        $this->magentoRegistry->unregister('search_ids');
        $this->magentoRegistry->register('search_ids', $idList);

        //To get the Variant Selection on the Catalog Search Results page on Preserve Layout
        $parentChildIDs = $this->sessionObjectHandler->getData('parentChildIDs_' . $queryScope . '_' . $queryTerm . $categoryFilter);
        if (!$parentChildIDs) {
            $parentChildIDs = $this->klevuProductRequest->getKlevuVariantParentChildIds();
            if (empty($parentChildIDs)) $parentChildIDs = array();
            $this->sessionObjectHandler->setData('parentChildIDs_' . $queryScope . '_' . $queryTerm . $categoryFilter, $parentChildIDs);
        }
        $this->magentoRegistry->unregister('parentChildIDs');
        $this->magentoRegistry->register('parentChildIDs', $parentChildIDs);


        $currentEngine = $this->getCurrentSearchEngine();
        //if no ids there then no need to set new handler for mysql only
        if (empty($idList) && $currentEngine === 'mysql') {
            $this->klevuHelperData->log(\Zend\Log\Logger::DEBUG,"MySQL Search Engine is selected and No Ids were found in CleanerPlugin");
            return $requestData;
        }
        
        //find the search query term and override the processor
        foreach ($requestData['queries']['quick_search_container']['queryReference'] as $key => $filter) {
            if ($filter['ref'] == 'search') $requestData['queries']['quick_search_container']['queryReference'][$key] = array('clause' => 'must', 'ref' => 'klevu_id_search');
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


        if ($currentEngine !== "mysql") {
            if (isset($requestData['sort'])) {
                if (count($requestData['sort']) > 0) {
                    foreach ($requestData['sort'] as $key => $value) {
                        if ($value['field'] == "personalized") {
                            $this->magentoRegistry->unregister('current_order');
                            $this->magentoRegistry->register('current_order', "personalized");
                        }
                    }
                }
            }

            $current_order = $this->magentoRegistry->registry('current_order');
            if (!empty($current_order)) {
                if ($current_order == "personalized") {
                    $this->magentoRegistry->unregister('from');
                    $this->magentoRegistry->unregister('size');
                    $this->magentoRegistry->register('from', $requestData['from']);
                    $this->magentoRegistry->register('size', $requestData['size']);
                    $requestData['from'] = 0;
                    $requestData['size'] = count($idList);
                    $requestData['sort'] = array();
                }
            }
        }
        //convert requestData object into array
        $requestDataToArray = json_decode(json_encode($requestData), true);
        $this->klevuHelperData->log(
            \Zend\Log\Logger::DEBUG,
            "Request data in CleanerPlugin" . PHP_EOL . print_r($requestDataToArray, true)
        );
        return $requestData;
    }

    /**
     * Return the product id field
     * @return string
     */
    private function getProductIdField()
    {
        //decide based on the engine settings what to use as filter
        //$currentEngine = $this->mutableScopeConfigInterface->getValue(EngineInterface::CONFIG_ENGINE_PATH, ScopeInterface::SCOPE_STORE);
        $currentEngine = $this->getCurrentSearchEngine();
        if (strpos($currentEngine, 'elasticsearch') !== false) {
            $currentEngine = "elasticsearch";
        }
        switch ($currentEngine) {
            case "elasticsearch":
                return '_id';
                break;
            case "elasticsearch5":
                return '_id';
                break;
            case "elasticsearch6":
                return '_id';
                break;
            case "elasticsearch7":
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
     * Return current catalog search engine
     *
     * @return mixed
     */
    private function getCurrentSearchEngine()
    {
        return $this->klevuHelperConfig->getCurrentEngine();
    }


}
