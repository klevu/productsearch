<?php
/**
 * Class \Klevu\Search\Model\Product\Sync
 * @method \Magento\Framework\Db\Adapter\Interface getConnection()
 * @method \Magento\Store\Model\Store getStore()
 * @method string getKlevuSessionId()
 */
namespace Klevu\Search\Model\Product;
use \Magento\Framework\Db\Adapter\AdapterInterface;
use \Magento\Catalog\Model\ResourceModel\Category\Collection;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\Event\ManagerInterface;
use \Klevu\Search\Helper\Config;
use \Klevu\Search\Model\Session;
use \Klevu\Search\Helper\Data;
use \Magento\Cron\Model\Schedule;
use \Psr\Log\LoggerInterface;
use \Klevu\Search\Model\Api\Action\Startsession;
use \Klevu\Search\Model\Api\Action\Deleterecords;
use \Klevu\Search\Model\Api\Action\Updaterecords;
use \Klevu\Search\Helper\Compat;
use \Klevu\Search\Model\Api\Action\Addrecords;
use \Magento\Catalog\Model\Product;
use \Magento\Framework\Filesystem;
use \Magento\Customer\Model\Group;
use \Magento\Tax\Model\Calculation;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Stdlib\DateTime\DateTime;
use \Magento\Eav\Model\Entity\Type;
use \Magento\Eav\Model\Entity\Attribute;
use \Magento\Catalog\Model\Product\Action;
use \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use \Klevu\Search\Model\Sync as KlevuSync;

class Sync extends AbstractModel
{
    
    /**
     * @var \Magento\Framework\Model\Resource
     */
    protected $_frameworkModelResource;
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_frameworkEventManagerInterface;
    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;
    /**
     * @var \Klevu\Search\Model\Session
     */
    protected $_searchModelSession;
    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;
    /**
     * @var \Magento\Cron\Model\Schedule
     */
    protected $_cronModelSchedule;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_psrLogLoggerInterface;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavModelConfig;
    /**
     * @var \Klevu\Search\Model\Api\Action\Startsession
     */
    protected $_apiActionStartsession;
    /**
     * @var \Klevu\Search\Model\Api\Action\Deleterecords
     */
    protected $_apiActionDeleterecords;
    /**
     * @var \Klevu\Search\Model\Api\Action\Updaterecords
     */
    protected $_apiActionUpdaterecords;
    /**
     * @var \Klevu\Search\Helper\Compat
     */
    protected $_searchHelperCompat;
    /**
     * @var \Klevu\Search\Model\Api\Action\Addrecords
     */
    protected $_apiActionAddrecords;
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_catalogModelProduct;
    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    protected $_productMediaConfig;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_magentoFrameworkFilesystem;
    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $_catalogHelperProduct;
    /**
     * @var \Magento\Catalog\Model\Resource\Category\Collection
     */
    protected $_resourceCategoryCollection;
    /**
     * @var \Magento\Customer\Model\Group
     */
    protected $_customerModelGroup;
    /**
     * @var \Magento\Catalog\Model\Resource\Product\Attribute\Collection
     */
    protected $_productAttributeCollection;
    /**
     * @var \Magento\Tax\Model\Calculation
     */
    protected $_taxModelCalculation;
    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxHelperData;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_appConfigScopeConfigInterface;
    /**
     * @var \Magento\CatalogInventory\Helper\Data
     */
    protected $_catalogInventoryHelperData;
    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $_taxModelConfig;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_frameworkModelDate;
    /**
     * @var \Magento\Framework\App\Config\Value
     */
    protected $_modelConfigData;
    /**
     * @var \Magento\Eav\Model\Entity\Type
     */
    protected $_modelEntityType;
    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    protected $_modelEntityAttribute;
	
    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $_modelProductAction;
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;
    
    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $_catalogModelCategory;
    
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_frameworkAppRequestInterface;
    
    /**
     * @var \Magento\Store\Model\Store
     */
    protected $_frameworkModelStore;
   
    /**
     * @var \Klevu\Search\Model\Api\Action\Features
     */
    protected $_apiActionFeatures;
	
	/**
     * @var \Klevu\Search\Helper\Image
     */
	protected $_imageHelper;
	
	/**
     * @var \Klevu\Search\Helper\Stock
     */
	protected $_stockHelper;
	
	
	protected $_klevu_features_response;
    protected $_klevu_enabled_feature_response;
    protected $_entity_value;
    protected $_klevuSyncModel;
    
    public function __construct(
        \Magento\Framework\App\ResourceConnection $frameworkModelResource,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        \Magento\Backend\Model\Session $searchModelSession,
        \Klevu\Search\Helper\Data $searchHelperData,
        \Magento\Cron\Model\Schedule $cronModelSchedule,
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Magento\Eav\Model\Config $eavModelConfig,
        \Klevu\Search\Model\Api\Action\Startsession $apiActionStartsession,
        \Klevu\Search\Model\Api\Action\Deleterecords $apiActionDeleterecords,
        \Klevu\Search\Model\Api\Action\Updaterecords $apiActionUpdaterecords,
        \Klevu\Search\Helper\Compat $searchHelperCompat,
        \Klevu\Search\Model\Api\Action\Addrecords $apiActionAddrecords,
        \Magento\Catalog\Model\Product $catalogModelProduct,
        \Magento\Catalog\Model\Product\Media\Config $productMediaConfig,
        \Magento\Framework\Filesystem $magentoFrameworkFilesystem,
        \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator $catalogHelperProduct,
        \Magento\Catalog\Model\ResourceModel\Category\Collection $resourceCategoryCollection,
        \Magento\Customer\Model\Group $customerModelGroup,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $productAttributeCollection,
        \Magento\Tax\Model\Calculation $taxModelCalculation,
        \Magento\Catalog\Helper\Data $taxHelperData,
        \Magento\Framework\App\Config\ScopeConfigInterface $appConfigScopeConfigInterface,
        \Magento\CatalogInventory\Helper\Data $catalogInventoryHelperData,
        \Magento\Tax\Model\Config $taxModelConfig,
        \Magento\Framework\Stdlib\DateTime\DateTime $frameworkModelDate,
        \Magento\Framework\App\Config\Value $modelConfigData,
        \Magento\Eav\Model\Entity\Type $modelEntityType,
        \Magento\Eav\Model\Entity\Attribute $modelEntityAttribute,
        \Magento\Catalog\Model\Product\Action $modelProductAction,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Catalog\Model\Category $catalogModelCategory,
        \Magento\Framework\App\RequestInterface $frameworkAppRequestInterface,
        \Magento\Store\Model\Store $frameworkModelStore,
        \Klevu\Search\Model\Api\Action\Features $apiActionFeatures,
		\Magento\Framework\App\ProductMetadataInterface $productMetadataInterface,
		\Klevu\Search\Helper\Image $imageHelper,
		\Klevu\Search\Helper\Price $priceHelper,
		\Klevu\Search\Helper\Stock $stockHelper,
        KlevuSync $klevuSyncModel,
        // abstract parent
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_klevuSyncModel = $klevuSyncModel;
        $this->_klevuSyncModel->setJobCode($this->getJobCode());


        $this->_apiActionFeatures = $apiActionFeatures;
        $this->_frameworkModelStore = $frameworkModelStore;
        $this->_frameworkAppRequestInterface = $frameworkAppRequestInterface;
        $this->_frameworkModelResource = $frameworkModelResource;
        $this->_frameworkEventManagerInterface = $context->getEventDispatcher();
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_searchModelSession = $searchModelSession;
        $this->_searchHelperData = $searchHelperData;
        $this->_cronModelSchedule = $cronModelSchedule;
        $this->_psrLogLoggerInterface = $context->getLogger();
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_eavModelConfig = $eavModelConfig;
        $this->_apiActionStartsession = $apiActionStartsession;
        $this->_apiActionDeleterecords = $apiActionDeleterecords;
        $this->_apiActionUpdaterecords = $apiActionUpdaterecords;
        $this->_searchHelperCompat = $searchHelperCompat;
        $this->_apiActionAddrecords = $apiActionAddrecords;
        $this->_catalogModelProduct = $catalogModelProduct;
        $this->_productMediaConfig = $productMediaConfig;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_catalogHelperProduct = $catalogHelperProduct;
        $this->_resourceCategoryCollection = $resourceCategoryCollection;
        $this->_customerModelGroup = $customerModelGroup;
        $this->_productAttributeCollection = $productAttributeCollection;
        $this->_taxModelCalculation = $taxModelCalculation;
        $this->_taxHelperData = $taxHelperData;
        $this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
        $this->_catalogInventoryHelperData = $catalogInventoryHelperData;
        $this->_taxModelConfig = $taxModelConfig;
        $this->_frameworkModelDate = $frameworkModelDate;
        $this->_modelConfigData = $modelConfigData;
        $this->_modelEntityType = $modelEntityType;
        $this->_modelEntityAttribute = $modelEntityAttribute;
        $this->_modelProductAction = $modelProductAction;
        $this->localeDate = $localeDate;
        $this->_catalogModelCategory = $catalogModelCategory;
        $this->_ProductMetadataInterface = $productMetadataInterface;
		$this->_imageHelper = $imageHelper;
		$this->_priceHelper = $priceHelper;
		$this->_stockHelper = $stockHelper;
        
        if (version_compare($this->_ProductMetadataInterface->getVersion(), '2.1.0', '>=')===true) {
            // you're on 2.0.13 later version
            $this->_galleryReadHandler = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Catalog\Model\Product\Gallery\ReadHandler');
        } else {
            $this->_galleryReadHandler = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Catalog\Model\Product\Gallery\GalleryManagement');
        }
        
        if (in_array($this->_ProductMetadataInterface->getEdition(),array("Enterprise","B2B")) && version_compare($this->_ProductMetadataInterface->getVersion(), '2.1.0', '>=')===true) {
            $this->_klevuSyncModel->setData("entity_value","row_id");
        } else {
            $this->_klevuSyncModel->setData("entity_value","entity_id");
        }
    }
    /**
     * It has been determined during development that Product Sync uses around
     * 120kB of memory for each product it syncs, or around 10MB of memory for
     * each 100 product page.
     */
    const RECORDS_PER_PAGE = 100;
    const NOTIFICATION_GLOBAL_TYPE = "product_sync";
    const NOTIFICATION_STORE_TYPE_PREFIX = "product_sync_store_";
    public function getJobCode()
    {
        return "klevu_search_product_sync";
    }
    /**
     * Perform Product Sync on any configured stores, adding new products, updating modified and
     * deleting removed products since last sync.
     */
    public function run()
    {
        try {
			
            /* mark for update special price product */
            $this->markProductForUpdate();
            
            // Sync Data only for selected store from config wizard
            $firstSync = $this->_searchModelSession->getFirstSync();
			$this->_searchModelSession->setKlevuFailedFlag(0);

            if (!empty($firstSync)) {
                /** @var \Magento\Store\Model\Store $store */
                $onestore = $this->_storeModelStoreManagerInterface->getStore($firstSync);
                if (!$this->setupSession($onestore)) {
                    return;
                }
                
                $this->syncData($onestore);
                $this->runCategory($onestore);
                $this->reset();
                return;
            }
            
            if ($this->isRunning(2)) {
                // Stop if another copy is already running
                $this->log(\Zend\Log\Logger::INFO, "Stopping because another copy is already running.");
                return;
            }
            
            $stores = $this->_storeModelStoreManagerInterface->getStores();
            $config = $this->_searchHelperConfig;
            
            foreach ($stores as $store) {
                if (!$this->setupSession($store)) {
                    continue;
                }
                $this->syncData($store);
                $this->runCategory($store);
                $this->reset();
            }
            
            // update rating flag after all store view sync
            $rating_upgrade_flag = $config->getRatingUpgradeFlag();
            if ($rating_upgrade_flag==0) {
                $config->saveRatingUpgradeFlag(1);
            }
        } catch (\Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            $this->_searchHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            throw $e;
        }
    }
	
	/**
     * Sync store view data.
     *
     * @param \Magento\Store\Model\Store|int $store If passed, will only update products for the given store.
     *
     * @return $this
     */
	public function syncStoreView($store){
		if (!$this->setupSession($store)) {
            return;
        }
		
		$this->syncData($store);
        $this->runCategory($store);
        $this->reset();
		$records_count['numberOfRecord_add'] =  $this->_klevuSyncModel->getRegistry()->registry("numberOfRecord_add");
		$records_count['numberOfRecord_update'] =  $this->_klevuSyncModel->getRegistry()->registry("numberOfRecord_update");
		$records_count['numberOfRecord_delete'] =  $this->_klevuSyncModel->getRegistry()->registry("numberOfRecord_delete");
		return $records_count;
	}

    public function runStore($store)
    {
        try{
            if (!$this->setupSession($store)) {
                return false;
            }

            $this->syncData($store);
            $this->runCategory($store);
            $this->reset();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    public function runCron(){
        try {
            /* mark for update special price product */
            $this->markProductForUpdate();

            // Sync Data only for selected store from config wizard
            $firstSync = $this->_searchModelSession->getFirstSync();
            $this->_searchModelSession->setKlevuFailedFlag(0);

            if (!empty($firstSync)) {
                /** @var \Magento\Store\Model\Store $store */
                $oneStore = $this->_storeModelStoreManagerInterface->getStore($firstSync);
                if (!$this->setupSession($oneStore)) {
                    return;
                }

                $this->syncData($oneStore);
                $this->runCategory($oneStore);
                $this->reset();
                return;
            }

            if ($this->isRunning(2)) {
                // Stop if another copy is already running
                $this->log(\Zend\Log\Logger::INFO, "Stopping because another copy is already running.");
                return;
            }

            $config = $this->_searchHelperConfig;

            $storeList = $this->_storeModelStoreManagerInterface->getStores();
            $websiteList = array();
            foreach ($storeList as $store) {
                if(!isset($websiteList[$store->getWebsiteId()])) $websiteList[$store->getWebsiteId()] = array();
                $websiteList[$store->getWebsiteId()] = array_unique(array_merge($websiteList[$store->getWebsiteId()], array($store->getCode())));
            }

            foreach ($websiteList as $storeList){
                $this->_klevuSyncModel->executeSubProcess('klevu:syncstore:storecode '.implode(",",$storeList));
            }

            // update rating flag after all store view sync
            $rating_upgrade_flag = $config->getRatingUpgradeFlag();
            if ($rating_upgrade_flag==0) {
                $config->saveRatingUpgradeFlag(1);
            }
        } catch (\Exception $e) {
            // Catch the exception that was thrown, log it, then throw a new exception to be caught the Magento cron.
            $this->_searchHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
            throw $e;
        }
    }

    protected function getSyncDataSqlForEEDelete($store)
	{
		$limit = $this->_klevuSyncModel->getSessionVariable("limit");
		$smtp =   $this->_frameworkModelResource->getConnection()
                    ->select()
                    ->union([
                        $this->_frameworkModelResource->getConnection()
                        ->select()
                        /*
                         * Select synced products in the current store/mode that are no longer enabled
                         * (don't exist in the products table, or have status disabled for the current
                         * store, or have status disabled for the default store) or are not visible
                         * (in the case of configurable products, check the parent visibility instead).
                         */
                        ->from(
                            ['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                            ['product_id' => "k.product_id", 'parent_id' => "k.parent_id"]
                        )
                        ->joinLeft(
                            ['v' => $this->_frameworkModelResource->getTableName("catalog_category_product_index")],
                            "v.product_id = k.product_id AND v.store_id = :store_id",
                            ""
                        )
                        ->joinLeft(
                            ['p' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                            "p.entity_id = k.product_id",
                            ""
                        )
                        ->joinLeft(
                            ['ss' => $this->getProductStatusAttribute()->getBackendTable()],
                            "ss.attribute_id = :status_attribute_id AND ss.".$this->_klevuSyncModel->getData("entity_value")." = p.".$this->_klevuSyncModel->getData("entity_value")." AND ss.store_id = :store_id",
                            ""
                        )
                        ->joinLeft(
                            ['sd' => $this->getProductStatusAttribute()->getBackendTable()],
                            "sd.attribute_id = :status_attribute_id AND sd.".$this->_klevuSyncModel->getData("entity_value")." = p.".$this->_klevuSyncModel->getData("entity_value")." AND sd.store_id = :default_store_id",
                            ""
                        )
                        ->where(
                            "(k.store_id = :store_id) AND (k.type = :type) AND ((p.entity_id IS NULL) OR (CASE WHEN ss.value_id > 0 THEN ss.value ELSE sd.value END != :status_enabled) OR (CASE WHEN k.parent_id = 0 THEN k.product_id ELSE k.parent_id END NOT IN (?)) )",
                            $this->_frameworkModelResource->getConnection()
                                ->select()
                                ->from(
                                    ['i' => $this->_frameworkModelResource->getTableName("catalog_category_product_index")],
                                    ['id' => "i.product_id"]
                                )
                                ->where("(i.store_id = :store_id) AND (i.visibility IN (:visible_both, :visible_search))")
                        ),
                        $this->_frameworkModelResource->getConnection()
                            ->select()
                            /*
                             * Select products which are not associated with parent 
                             * but still parent exits in klevu product sync table with parent id
                             * 
                             */
                            ->from(
                                ['ks' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                                ['product_id' => "ks.product_id","parent_id" => 'ks.parent_id']
                            )
                            ->where(
								"(ks.parent_id !=0 AND CONCAT(ks.product_id,'-',ks.parent_id) NOT IN (?) AND ks.store_id = :store_id)",
								$this->_frameworkModelResource->getConnection()
								->select()
								/*
								 * Select products from catalog super link table
								 */
                                ->from(
                                    ['e1' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                                    ['product_id' => "CONCAT(e2.entity_id,'-',e1.entity_id)"]
                                )
                                ->join(
                                    ['s1' => $this->_frameworkModelResource->getTableName("catalog_product_super_link")],
                                    "e1.row_id= s1.parent_id",
                                    ""
                                )
                                ->join(
                                    ['e2' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                                    "e2.entity_id = s1.product_id",
                                    ""
                                )
							)
                        ]);
                    $smtp->group(['k.product_id', 'k.parent_id']);
					if(!empty($limit)){
						$smtp->limit($limit);
					}
                    $smtp->bind([
                        'type'          => "products",
                        'store_id'       => $store->getId(),
                        'default_store_id' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                        'status_attribute_id' => $this->getProductStatusAttribute()->getId(),
                        'status_enabled' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED,
                        'visible_both'   => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
                        'visible_search' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH
                    ]);
					return $smtp;
	}
	protected function getSyncDataSqlForEEUpdate($store) 
	{
		$limit = $this->_klevuSyncModel->getSessionVariable("limit");
		$smtp = $this->_frameworkModelResource->getConnection("core_write")
                    ->select()
                    ->union([
                        // Select products without parents that need to be updated
                        $this->_frameworkModelResource->getConnection("core_write")
                            ->select()
                            /*
                             * Select synced non-configurable products for the current store/mode
                             * that are visible (using the category product index) and have been
                             * updated since last sync.
                             */
                            ->from(
                                ['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                                ['product_id' => "k.product_id", 'parent_id' => "k.parent_id"]
                            )
                            ->join(
                                ['p' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                                "p.entity_id = k.product_id",
                                ""
                            )
                            ->join(
                                ['i' => $this->_frameworkModelResource->getTableName("catalog_category_product_index")],
                                "i.product_id = k.product_id AND k.store_id = i.store_id AND i.visibility IN (:visible_both, :visible_search)",
                                ""
                            )
                            ->where("(k.store_id = :store_id) AND (k.type = :type) AND (p.type_id != :configurable) AND (p.updated_at > k.last_synced_at)"),
                        // Select products with parents (configurable) that need to be updated
                $this->_frameworkModelResource->getConnection("core_write")
                            ->select()
                            /*
                             * Select configurable product children that are enabled (for the current
                             * store or for the default store), have visible parents (using the category
                             * product index) and have not been synced yet for the current store with
                             * the current parent.
                             */
                            ->from(
                                ['e1' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                                ['product_id' => "s1.product_id", 'parent_id' => "e1.entity_id"]
                            )
                            ->join(
                                ['s1' => $this->_frameworkModelResource->getTableName("catalog_product_super_link")],
                                "e1.row_id= s1.parent_id",
                                ""
                            )
                            ->join(
                                ['i' => $this->_frameworkModelResource->getTableName("catalog_category_product_index")],
                                "i.product_id= e1.entity_id AND i.store_id = :store_id AND i.visibility IN (:visible_both, :visible_search)",
                                ""
                            )
                            ->join(
                                ['e2' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                                "e2.entity_id = s1.product_id",
                                ""
                            )
                            ->joinLeft(
                                ['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                                "e1.entity_id = k.parent_id AND s1.product_id = k.product_id AND k.store_id = :store_id AND k.type = :type",
                                ""
                            )
                            ->joinLeft(
                                ['ss' => $this->getProductStatusAttribute()->getBackendTable()],
                                "ss.attribute_id = :status_attribute_id AND e2.row_id = ss.row_id AND ss.store_id = :default_store_id",
                                ""
                            )
                            ->joinLeft(
                                ['sd' => $this->getProductStatusAttribute()->getBackendTable()],
                                "sd.attribute_id = :status_attribute_id AND sd.row_id = e2.row_id AND sd.store_id = :store_id",
                                ""
                            )
                            ->where("(CASE WHEN sd.value_id > 1 THEN sd.value ELSE ss.value END = :status_enabled) AND ((e1.updated_at > k.last_synced_at) OR (e2.updated_at > k.last_synced_at))")
                    ])
                    ->group(['k.product_id', 'k.parent_id']);
					if(!empty($limit)){
						$smtp->limit($limit);
					}
                    $smtp->bind([
                        'type'          => "products",
                        'store_id' => $store->getId(),
                        'default_store_id' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                        'configurable' => \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
                        'visible_both' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
                        'visible_search' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH,
                        'status_attribute_id' => $this->getProductStatusAttribute()->getId(),
                        'status_enabled' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED,
                    ]);
			return $smtp;
	}
    protected function getSyncDataSqlForEEAdd($store) 
	{
		$limit = $this->_klevuSyncModel->getSessionVariable("limit");
		$smtp = $this->_frameworkModelResource->getConnection("core_write")
                    ->select()
                    ->union([
                        // Select non-configurable products that need to be added
                        $this->_frameworkModelResource->getConnection("core_write")
                            ->select()
                            /*
                             * Select non-configurable products that are visible in the current
                             * store (using the category product index), but have not been synced
                             * for this store yet.
                             */
                            ->from(
                                ['p' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                                ['product_id' => "p.entity_id", 'parent_id' => "k.parent_id"]
                            )
                            ->join(
                                ['i' => $this->_frameworkModelResource->getTableName("catalog_category_product_index")],
                                "p.entity_id = i.product_id AND i.store_id = :store_id AND i.visibility IN (:visible_both, :visible_search)",
                                ""
                            )
                            ->joinLeft(
                                ['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                                "p.entity_id = k.product_id AND k.parent_id = 0 AND i.store_id = k.store_id AND k.type = :type",
                                ""
                            )
                            ->where("(p.type_id != :configurable) AND (k.product_id IS NULL)"),
                        // Select configurable parent & product pairs that need to be added
                        $this->_frameworkModelResource->getConnection("core_write")
                            ->select()
                            /*
                             * Select configurable product children that are enabled (for the current
                             * store or for the default store), have visible parents (using the category
                             * product index) and have not been synced yet for the current store with
                             * the current parent.
                             */
                            ->from(
                                ['e1' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                                ['product_id' => "s1.product_id", 'parent_id' => "e1.entity_id"]
                            )
                            ->join(
                                ['s1' => $this->_frameworkModelResource->getTableName("catalog_product_super_link")],
                                "e1.row_id= s1.parent_id",
                                ""
                            )
                            ->join(
                                ['i' => $this->_frameworkModelResource->getTableName("catalog_category_product_index")],
                                "i.product_id= e1.entity_id AND i.store_id = :store_id AND i.visibility IN (:visible_both, :visible_search)",
                                ""
                            )
                            ->join(
                                ['e2' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                                "e2.entity_id = s1.product_id",
                                ""
                            )
                            ->join(
                                ['wb' => $this->_frameworkModelResource->getTableName("catalog_product_website")],
                                "wb.product_id = s1.product_id and wb.website_id = :website_id",
                                ""
                            )
                            ->joinLeft(
                                ['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                                "e1.entity_id = k.parent_id AND s1.product_id = k.product_id AND k.store_id = :store_id AND k.type = :type",
                                ""
                            )
                            ->joinLeft(
                                ['ss' => $this->getProductStatusAttribute()->getBackendTable()],
                                "ss.attribute_id = :status_attribute_id AND e2.row_id = ss.row_id AND ss.store_id = :default_store_id",
                                ""
                            )
                            ->joinLeft(
                                ['sd' => $this->getProductStatusAttribute()->getBackendTable()],
                                "sd.attribute_id = :status_attribute_id AND sd.row_id = e2.row_id AND sd.store_id = :store_id",
                                ""
                            )
                            ->where("(CASE WHEN sd.value_id > 1 THEN sd.value ELSE ss.value END = :status_enabled) AND (k.product_id IS NULL)")
                    ])
                    ->group(['product_id', 'parent_id']);
					if(!empty($limit)){
						$smtp->limit($limit);
					}
                    $smtp->bind([
                        'type' => "products",
                        'store_id' => $store->getId(),
                        'website_id' => $store->getWebsiteId(),
                        'default_store_id' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                        'configurable' => \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
                        'visible_both' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
                        'visible_search' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH,
                        'status_attribute_id' => $this->getProductStatusAttribute()->getId(),
                        'status_enabled' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
                    ]);
				return $smtp;
	}
	protected function getSyncDataSqlForCEDelete($store) 
	{
		$limit = $this->_klevuSyncModel->getSessionVariable("limit");
		
		$smtp = $this->_frameworkModelResource->getConnection()
            ->select()
            ->union([
                $this->_frameworkModelResource->getConnection()
                ->select()
                /*
                 * Select synced products in the current store/mode that are no longer enabled
                 * (don't exist in the products table, or have status disabled for the current
                 * store, or have status disabled for the default store) or are not visible
                 * (in the case of configurable products, check the parent visibility instead).
                 */
                ->from(
                    ['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                    ['product_id' => "k.product_id", 'parent_id' => "k.parent_id"]
                )
                ->joinLeft(
                    ['v' => $this->_frameworkModelResource->getTableName("catalog_category_product_index")],
                    "v.product_id = k.product_id AND v.store_id = :store_id",
                    ""
                )
                ->joinLeft(
                    ['p' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                    "p.entity_id = k.product_id",
                    ""
                )
                ->joinLeft(
                    ['ss' => $this->getProductStatusAttribute()->getBackendTable()],
                    "ss.attribute_id = :status_attribute_id AND ss.".$this->_klevuSyncModel->getData("entity_value")." = k.product_id AND ss.store_id = :store_id",
                    ""
                )
                ->joinLeft(
                    ['sd' => $this->getProductStatusAttribute()->getBackendTable()],
                    "sd.attribute_id = :status_attribute_id AND sd.".$this->_klevuSyncModel->getData("entity_value")." = k.product_id AND sd.store_id = :default_store_id",
                    ""
                )
                ->where(
                    "(k.store_id = :store_id) AND (k.type = :type) AND ((p.entity_id IS NULL) OR (CASE WHEN ss.value_id > 0 THEN ss.value ELSE sd.value END != :status_enabled) OR (CASE WHEN k.parent_id = 0 THEN k.product_id ELSE k.parent_id END NOT IN (?)) )",
                    $this->_frameworkModelResource->getConnection()
                        ->select()
                        ->from(
                            ['i' => $this->_frameworkModelResource->getTableName("catalog_category_product_index")],
                            ['id' => "i.product_id"]
                        )
                        ->where("(i.store_id = :store_id) AND (i.visibility IN (:visible_both, :visible_search))")
                ),
                $this->_frameworkModelResource->getConnection()
                    ->select()
                    /*
                     * Select products which are not associated with parent 
                     * but still parent exits in klevu product sync table with parent id
                     * 
                     */
                    ->from(
                        ['ks' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                        ['product_id' => "ks.product_id","parent_id" => 'ks.parent_id']
                    )
                    ->where(
                        "(ks.parent_id !=0 AND CONCAT(ks.product_id,'-',ks.parent_id) NOT IN (?) AND ks.store_id = :store_id)",
                        $this->_frameworkModelResource->getConnection()
                        ->select()
                        /*
                         * Select products from catalog super link table
                         */
                        ->from(
                            ['s' => $this->_frameworkModelResource->getTableName("catalog_product_super_link")],
                            ['product_id' => "CONCAT(product_id,'-',parent_id)"]
                        )
                    )
                ])
            ->group(['k.product_id', 'k.parent_id']);
			if(!empty($limit)){
				$smtp->limit($limit);
			}
            $smtp->bind([
                'type'          => "products",
                'store_id'       => $store->getId(),
                'default_store_id' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                'status_attribute_id' => $this->getProductStatusAttribute()->getId(),
                'status_enabled' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED,
                'visible_both'   => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
                'visible_search' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH
            ]);
			return $smtp;
	}
	protected function getSyncDataSqlForCEUpdate($store) 
	{
		$limit = $this->_klevuSyncModel->getSessionVariable("limit");
		$smtp = $this->_frameworkModelResource->getConnection("core_write")
            ->select()
            ->union([
                // Select products without parents that need to be updated
                $this->_frameworkModelResource->getConnection("core_write")
                    ->select()
                    /*
                     * Select synced non-configurable products for the current store/mode
                     * that are visible (using the category product index) and have been
                     * updated since last sync.
                     */
                    ->from(
                        ['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                        ['product_id' => "k.product_id", 'parent_id' => "k.parent_id"]
                    )
                    ->join(
                        ['p' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                        "p.entity_id = k.product_id",
                        ""
                    )
                    ->join(
                        ['i' => $this->_frameworkModelResource->getTableName("catalog_category_product_index")],
                        "i.product_id = k.product_id AND k.store_id = i.store_id AND i.visibility IN (:visible_both, :visible_search)",
                        ""
                    )
                    ->where("(k.store_id = :store_id) AND (k.type = :type) AND (p.type_id != :configurable) AND (p.updated_at > k.last_synced_at)"),
                // Select products with parents (configurable) that need to be updated
                $this->_frameworkModelResource->getConnection("core_write")
                    ->select()
                    /*
                     * Select synced products for the current store/mode that are configurable
                     * children (have entries in the super link table), are enabled for the current
                     * store (or the default store), have visible parents (using the category product
                     * index) and, either the product or the parent, have been updated since last sync.
                     */
                    ->from(
                        ['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                        ['product_id' => "k.product_id", 'parent_id' => "k.parent_id"]
                    )
                    ->join(
                        ['s' => $this->_frameworkModelResource->getTableName("catalog_product_super_link")],
                        "k.parent_id = s.parent_id AND k.product_id = s.product_id",
                        ""
                    )
                    ->join(
                        ['i' => $this->_frameworkModelResource->getTableName("catalog_category_product_index")],
                        "k.parent_id = i.product_id AND k.store_id = i.store_id AND i.visibility IN (:visible_both, :visible_search)",
                        ""
                    )
                    ->join(
                        ['p1' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                        "k.product_id = p1.entity_id",
                        ""
                    )
                    ->join(
                        ['p2' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                        "k.parent_id = p2.entity_id",
                        ""
                    )
                    ->joinLeft(
                        ['ss' => $this->getProductStatusAttribute()->getBackendTable()],
                        "ss.attribute_id = :status_attribute_id AND ss.".$this->_klevuSyncModel->getData("entity_value")." = k.product_id AND ss.store_id = :store_id",
                        ""
                    )
                    ->joinLeft(
                        ['sd' => $this->getProductStatusAttribute()->getBackendTable()],
                        "sd.attribute_id = :status_attribute_id AND sd.".$this->_klevuSyncModel->getData("entity_value")." = k.product_id AND sd.store_id = :default_store_id",
                        ""
                    )
                    ->where("(k.store_id = :store_id) AND (k.type = :type) AND (CASE WHEN ss.value_id > 0 OR ss.value = NULL THEN ss.value ELSE sd.value END = :status_enabled) AND ((p1.updated_at > k.last_synced_at) OR (p2.updated_at > k.last_synced_at))")
            ])
            ->group(['k.product_id', 'k.parent_id']);
			if(!empty($limit)){
				$smtp->limit($limit);
			}
            $smtp->bind([
                'type'          => "products",
                'store_id' => $store->getId(),
                'default_store_id' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                'configurable' => \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
                'visible_both' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
                'visible_search' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH,
                'status_attribute_id' => $this->getProductStatusAttribute()->getId(),
                'status_enabled' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED,
            ]);
			return $smtp;
	}
	protected function getSyncDataSqlForCEAdd($store) 
	{
		$limit = $this->_klevuSyncModel->getSessionVariable("limit");
		$smtp = $this->_frameworkModelResource->getConnection("core_write")
            ->select()
            ->union([
                // Select non-configurable products that need to be added
                $this->_frameworkModelResource->getConnection("core_write")
                    ->select()
                    /*
                     * Select non-configurable products that are visible in the current
                     * store (using the category product index), but have not been synced
                     * for this store yet.
                     */
                    ->from(
                        ['p' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                        ['product_id' => "p.entity_id", 'parent_id' => "k.parent_id"]
                    )
                    ->join(
                        ['i' => $this->_frameworkModelResource->getTableName("catalog_category_product_index")],
                        "p.entity_id = i.product_id AND i.store_id = :store_id AND i.visibility IN (:visible_both, :visible_search)",
                        ""
                    )
                    ->joinLeft(
                        ['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                        "p.entity_id = k.product_id AND k.parent_id = 0 AND i.store_id = k.store_id AND k.type = :type",
                        ""
                    )
                    ->where("(p.type_id != :configurable) AND (k.product_id IS NULL)"),
                // Select configurable parent & product pairs that need to be added
                $this->_frameworkModelResource->getConnection("core_write")
                    ->select()
                    /*
                     * Select configurable product children that are enabled (for the current
                     * store or for the default store), have visible parents (using the category
                     * product index) and have not been synced yet for the current store with
                     * the current parent.
                     */
                    ->from(
                        ['s' => $this->_frameworkModelResource->getTableName("catalog_product_super_link")],
                        ['product_id' => "s.product_id", 'parent_id' => "s.parent_id"]
                    )
                    ->join(
                        ['i' => $this->_frameworkModelResource->getTableName("catalog_category_product_index")],
                        "s.parent_id = i.product_id AND i.store_id = :store_id AND i.visibility IN (:visible_both, :visible_search)",
                        ""
                    )
                    ->join(
                        ['wb' => $this->_frameworkModelResource->getTableName("catalog_product_website")],
                        "wb.product_id = s.product_id and wb.website_id = :website_id",
                        ""
                    )
                    ->joinLeft(
                        ['ss' => $this->getProductStatusAttribute()->getBackendTable()],
                        "ss.attribute_id = :status_attribute_id AND ss.".$this->_klevuSyncModel->getData("entity_value")." = s.product_id AND ss.store_id = :store_id",
                        ""
                    )
                    ->joinLeft(
                        ['sd' => $this->getProductStatusAttribute()->getBackendTable()],
                        "sd.attribute_id = :status_attribute_id AND sd.".$this->_klevuSyncModel->getData("entity_value")." = s.product_id AND sd.store_id = :default_store_id",
                        ""
                    )
                    ->joinLeft(
                        ['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                        "s.parent_id = k.parent_id AND s.product_id = k.product_id AND k.store_id = :store_id AND k.type = :type",
                        ""
                    )
                    ->where("(CASE WHEN ss.value_id > 0 THEN ss.value ELSE sd.value END = :status_enabled) AND (k.product_id IS NULL)")
            ])
            ->group(['k.product_id', 'k.parent_id']);
			if(!empty($limit)){
				$smtp->limit($limit);
			}
            $smtp->bind([
                'type' => "products",
                'store_id' => $store->getId(),
                'website_id' => $store->getWebsiteId(),
                'default_store_id' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                'configurable' => \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
                'visible_both' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH,
                'visible_search' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH,
                'status_attribute_id' => $this->getProductStatusAttribute()->getId(),
                'status_enabled' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
            ]);
			return $smtp;
	}
	protected function getSyncDataSqlForAction($store, $action , $version = 'CE') 
	{
		if($version == 'EE') {
			switch ($action) {
				case "delete":
					return $this->getSyncDataSqlForEEDelete($store);
				break;
				case "update":
					return $this->getSyncDataSqlForEEUpdate($store);
				break;
				case "add":
					return $this->getSyncDataSqlForEEAdd($store);
				break;
				default: return false;break;
			}
		} else {
			switch ($action) {
				case "delete":
					return $this->getSyncDataSqlForCEDelete($store);
				break;
				case "update":
					return $this->getSyncDataSqlForCEUpdate($store);
				break;
				case "add":
					return $this->getSyncDataSqlForCEAdd($store);
				break;
				default: return false;break;
			}
		}
	}
	protected function getSyncDataActions($store)
	{
        if ($this->_ProductMetadataInterface->getEdition() == "Enterprise" && version_compare($this->_ProductMetadataInterface->getVersion(), '2.1.0', '>=')===true) {
            $actions = [
                'delete' => $this->getSyncDataSqlForAction($store , 'delete' , 'EE' ),
                'update' => $this->getSyncDataSqlForAction($store , 'update' , 'EE' ),
                'add' =>  $this->getSyncDataSqlForAction($store , 'add' , 'EE' )
            ];
        } elseif ($this->_ProductMetadataInterface->getEdition() == "B2B" && version_compare($this->_ProductMetadataInterface->getVersion(), '2.1.0', '>=')===true) {
            $actions = [
                'delete' => $this->getSyncDataSqlForAction($store , 'delete' , 'EE' ),
                'update' => $this->getSyncDataSqlForAction($store , 'update' , 'EE' ),
                'add' =>  $this->getSyncDataSqlForAction($store , 'add' , 'EE' )
            ];
        } else {
            $actions = [
				'delete' => $this->getSyncDataSqlForAction($store , 'delete' , 'CE' ),
				'update' => $this->getSyncDataSqlForAction($store , 'update' , 'CE' ),
				'add' => $this->getSyncDataSqlForAction($store , 'add' , 'CE' )
            ];
        }
		return $actions;
	}
	public function syncData($store)
    {
                
        if ($this->rescheduleIfOutOfMemory()) {
            return;
        }
                
        $config = $this->_searchHelperConfig;
        $session = $this->_searchModelSession;
        $firstSync = $session->getFirstSync();

        try {
            $rating_upgrade_flag = $config->getRatingUpgradeFlag();
            if (!empty($firstSync) || $rating_upgrade_flag==0) {
                $this->updateProductsRating($store);
            }
        } catch (\Exception $e) {
            $this->_searchHelperData->log(\Zend\Log\Logger::WARN, sprintf("Unable to update rating attribute %s", $store->getName()));
        }
                
        //set current store so will get proper bundle price
        $this->_storeModelStoreManagerInterface->setCurrentStore($store->getId());
        $this->log(\Zend\Log\Logger::INFO, sprintf("Starting sync for %s (%s).", $store->getWebsite()->getName(), $store->getName()));
        
		$actions = $this->getSyncDataActions($store);

		$errors = 0;

        foreach ($actions as $action => $statement) {
            if ($this->rescheduleIfOutOfMemory()) {
                return;
            }
                        
            $method = $action . "Products";
            $products = $this->_frameworkModelResource->getConnection()->fetchAll($statement, $statement->getBind());
            $total = count($products);
    		$this->log(\Zend\Log\Logger::INFO, sprintf("Found %d products to %s.", $total, $action));
            $pages = ceil($total / static::RECORDS_PER_PAGE);
            for ($page = 1; $page <= $pages; $page++) {
                if ($this->rescheduleIfOutOfMemory()) {
                    return;
                }
                $offset = ($page - 1) * static::RECORDS_PER_PAGE;
                $result = $this->$method(array_slice($products, $offset, static::RECORDS_PER_PAGE));
                if ($result !== true) {
                    $errors++;
                    $this->log(\Zend\Log\Logger::ERR, sprintf(
                        "Errors occurred while attempting to %s products %d - %d: %s",
                        $action,
                        $offset + 1,
                        ($offset + static::RECORDS_PER_PAGE <= $total) ? $offset + static::RECORDS_PER_PAGE : $total,
                        $result
                    ));
                }
            }
        }
        $this->log(\Zend\Log\Logger::INFO, sprintf("Finished sync for %s (%s).", $store->getWebsite()->getName(), $store->getName()));
                
        if (!$config->isExtensionEnabled($store)) {
            // Enable Klevu Search after the first sync
            if (!empty($firstSync)) {
                $config->setExtensionEnabledFlag(true, $store);
                $this->log(\Zend\Log\Logger::INFO, sprintf(
                    "Automatically enabled Klevu Search on Frontend for %s (%s).",
                    $store->getWebsite()->getName(),
                    $store->getName()
                ));
            }
        }
    }
    /**
     * Run the product sync manually, creating a cron schedule entry
     * to prevent other syncs from running.
     */
    public function runManually()
    {
        $scheduler = $this->_klevuSyncModel->getScheduler();

        $operations = array(
            "setJobCode"    =>  $this->getJobCode(),
            "setStatus"     =>  $scheduler->getStatusByCode('running'),
            "setExecutedAt" =>  $scheduler->getSchedulerTimeMysql()
        );
        $schedule = $scheduler->manageSchedule($operations);

        try {
            $this->run();
        } catch (\Exception $e) {
            $this->_psrLogLoggerInterface->error($e);
            $operations = array(
                "setMessages"    =>  $e->getMessage(),
                "setStatus"     =>  $scheduler->getStatusByCode('error')
            );
            $scheduler->manageSchedule($operations,$schedule);
            return;
        }
        $operations = array(
            "setFinishedAt"    =>  $scheduler->getSchedulerTimeMysql(),
            "setStatus"     =>  $scheduler->getStatusByCode('success')
        );
        $scheduler->manageSchedule($operations,$schedule);

        return;
    }
    /**
     * Mark all products to be updated the next time Product Sync runs.
     *
     * @param \Magento\Store\Model\Store|int $store If passed, will only update products for the given store.
     *
     * @return $this
     */
    public function markAllProductsForUpdate($store = null)
    {
        $where = "";
        if ($store !== null) {
            $store = $this->_storeModelStoreManagerInterface->getStore($store);
            $where = $this->_frameworkModelResource->getConnection("core_write")->quoteInto("store_id =  ?", $store->getId());
        }
        $this->_frameworkModelResource->getConnection("core_write")->update(
            $this->_frameworkModelResource->getTableName('klevu_product_sync'),
            ['last_synced_at' => '0'],
            $where
        );
        return $this;
    }
    /**
     * Forget the sync status of all the products for the given Store and test mode.
     * If no store or test mode status is given, clear products for all stores and modes respectively.
     *
     * @param \Magento\Store\Model\Store|int|null $store
     *
     * @return int
     */
    public function clearAllProducts($store = null)
    {
        $select = $this->_frameworkModelResource->getConnection("core_write")
            ->select()
            ->from(
                ["k" => $this->_frameworkModelResource->getTableName("klevu_product_sync")]
            );
        if ($store) {
            $store = $this->_storeModelStoreManagerInterface->getStore($store);
            $select->where("k.store_id = ?", $store->getId());
        }
        
        $result = $this->_frameworkModelResource->getConnection("core_write")->query($select->deleteFromSelect("k"));
        return $result->rowCount();
    }
    /**
     * Return the product status attribute model.
     *
     * @return \Magento\Catalog\Model\Resource\Eav\Attribute
     */
    protected function getProductStatusAttribute()
    {
        if (!$this->hasData("status_attribute")) {
            $this->setData("status_attribute", $this->_eavModelConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'status'));
        }
        return $this->getData("status_attribute");
    }
    /**
     * Return the product visibility attribute model.
     *
     * @return \Magento\Catalog\Model\Resource\Eav\Attribute
     */
    protected function getProductVisibilityAttribute()
    {
        if (!$this->hasData("visibility_attribute")) {
            $this->setData("visibility_attribute", $this->_eavModelConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'visibility'));
        }
        return $this->getData("visibility_attribute");
    }
    /**
     * Setup an API session for the given store. Sets the store and session ID on self. Returns
     * true on success or false if Product Sync is disabled, store is not configured or the
     * session API call fails.
     *
     * @param \Magento\Store\Model\Store $store
     *
     * @return bool
     */
    public function setupSession($store)
    {
        $config = $this->_searchHelperConfig;
        if (!$config->isProductSyncEnabled($store->getId())) {
            $this->log(\Zend\Log\Logger::INFO, sprintf("Disabled for %s (%s).", $store->getWebsite()->getName(), $store->getName()));
            return null;
        }
        $api_key = $config->getRestApiKey($store->getId());
        if (!$api_key) {
            $this->log(\Zend\Log\Logger::INFO, sprintf("No API key found for %s (%s).", $store->getWebsite()->getName(), $store->getName()));
            return null;
        }
     
        $response = $this->_apiActionStartsession->execute([
            'api_key' => $api_key,
            'store' => $store,
        ]);
		
        if ($response->isSuccess()) {
            $this->addData([
                'store'      => $store,
                'session_id' => $response->getSessionId()
            ]);
			$this->_searchModelSession->setKlevuSessionId($response->getSessionId());
            return true;
        } else {
            $this->log(\Zend\Log\Logger::ERR, sprintf(
                "Failed to start a session for %s (%s): %s",
                $store->getWebsite()->getName(),
                $store->getName(),
                $response->getMessage()
            ));
            if ($response instanceof \Klevu\Search\Model\Api\Response\Rempty) {
                $this->log(\Zend\Log\Logger::ERR, sprintf(
                    "Product Sync failed for %s (%s): Could not contact Klevu.",
                    $store->getWebsite()->getName(),
                    $store->getName()
                ));
            } else {
                $this->log(\Zend\Log\Logger::ERR, sprintf(
                    "Product Sync failed for %s (%s): %s",
                    $store->getWebsite()->getName(),
                    $store->getName(),
                    $response->getMessage()
                ));
            }
            return false;
        }
    }
	
	
	/**
	* Build the delete SQL , separated for easier override
	*/
	protected function getDeleteProductsSuccessSql(array $data, $skipped_record_ids)
	{
		$connection = $this->_frameworkModelResource->getConnection("core_write");
		$select = $connection
                ->select()
                ->from(['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")])
                ->where("k.store_id = ?", $this->_storeModelStoreManagerInterface->getStore()->getId())
                ->where("k.type = ?", "products");

            $or_where = [];
            for ($i = 0; $i < count($data); $i++) {
                if (isset($skipped_record_ids[$i])) {
                    continue;
                }
                $or_where[] = sprintf(
                    "(%s AND %s)",
                    $connection->quoteInto("k.product_id = ?", $data[$i]['product_id']),
                    $connection->quoteInto("k.parent_id = ?", $data[$i]['parent_id']),
                    $connection->quoteInto("k.type = ?", "products")
                );
            }
            $select->where(implode(" OR ", $or_where));
		return $select;
	}
	
	/**
	* Delete success processing , separated for easier override
	*/
	protected function executeDeleteProductsSuccess(array $data, $response)
	{
	    $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_update");
		$this->_klevuSyncModel->getRegistry()->register("numberOfRecord_delete",count($data));
		$skipped_record_ids = [];
		if ($skipped_records = $response->getSkippedRecords()) {
			$skipped_record_ids = array_flip($skipped_records["index"]);
		}
		
		$connection = $this->_frameworkModelResource->getConnection("core_write");

		$select = $this->getDeleteProductsSuccessSql($data, $skipped_record_ids);

		$connection->query($select->deleteFromSelect("k"));

		$skipped_count = count($skipped_record_ids);
		if ($skipped_count > 0) {
			return sprintf(
				"%d product%s failed (%s)",
				$skipped_count,
				($skipped_count > 1) ? "s" : "",
				implode(", ", $skipped_records["messages"])
			);
		} else {
			return true;
		}
	}
    /**
     * Delete the given products from Klevu Search. Returns true if the operation was
     * successful, or the error message if the operation failed.
     *
     * @param array $data List of products to delete. Each element should be an array
     *                    containing an element with "product_id" as the key and product id as
     *                    the value and an optional "parent_id" element with the parent id.
     *
     * @return bool|string
     */
    protected function deleteProducts(array $data)
    {
        $total = count($data);
        $response = $this->_apiActionDeleterecords
            ->setStore($this->_storeModelStoreManagerInterface->getStore())
            ->execute([
            'sessionId' => $this->_searchModelSession->getKlevuSessionId(),
            'records'   => array_map(function ($v) {
                return ['id' => $this->_searchHelperData->getKlevuProductId($v['product_id'], $v['parent_id'])];
            }, $data)
            ]);
        if ($response->isSuccess()) {
            return $this->executeDeleteProductsSuccess($data,$response);
        } else {
			$this->_searchModelSession->setKlevuFailedFlag(1);
            return sprintf(
                "%d product%s failed (%s)",
                $total,
                ($total > 1) ? "s" : "",
                $response->getMessage()
            );
        }
    }
	/**
	* Update success processing , separated for easier override
	*/
	protected function executeUpdateProductsSuccess(array $data, $response)
	{
		$helper = $this->_searchHelperData;
		$connection = $this->_frameworkModelResource->getConnection("core_write");
		$this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_update");
		$this->_klevuSyncModel->getRegistry()->register("numberOfRecord_update",count($data));

		$skipped_record_ids = [];
		if ($skipped_records = $response->getSkippedRecords()) {
			$skipped_record_ids = array_flip($skipped_records["index"]);
		}

		$where = [];
		for ($i = 0; $i < count($data); $i++) {
			if (isset($skipped_record_ids[$i])) {
				continue;
			}
			
			if (isset($data[$i]['id'])) {
				$ids = $helper->getMagentoProductId($data[$i]['id']);
				if (!empty($ids)) {
					$where[] = sprintf(
						"(%s AND %s AND %s)",
						$connection->quoteInto("product_id = ?", $ids['product_id']),
						$connection->quoteInto("parent_id = ?", $ids['parent_id']),
						$connection->quoteInto("type = ?", "products")
					);
				}
			}
		}
		
		if (!empty($where)) {
			$where = sprintf(
				"(%s) AND (%s)",
				$connection->quoteInto("store_id = ?", $this->_storeModelStoreManagerInterface->getStore()->getId()),
				implode(" OR ", $where)
			);

			$this->_frameworkModelResource->getConnection("core_write")->update(
				$this->_frameworkModelResource->getTableName('klevu_product_sync'),
				['last_synced_at' => $this->_searchHelperCompat->now()],
				$where
			);
		}

		$skipped_count = count($skipped_record_ids);
		if ($skipped_count > 0) {
			return sprintf(
				"%d product%s failed (%s)",
				$skipped_count,
				($skipped_count > 1) ? "s" : "",
				implode(", ", $skipped_records["messages"])
			);
		} else {
			return true;
		}
	}
    /**
     * Update the given products on Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of products to update. Each element should be an array
     *                    containing an element with "product_id" as the key and product id as
     *                    the value and an optional "parent_id" element with the parent id.
     *
     * @return bool|string
     */
    protected function updateProducts(array $data)
    {
		
        $total = count($data);
       
        $dataToSend = $this->addProductSyncData($data);
        if (!empty($dataToSend) && is_numeric($dataToSend)) {
            $data = array_slice($data, 0, $dataToSend);
        }
        $response = $this->_apiActionUpdaterecords
            ->setStore($this->_storeModelStoreManagerInterface->getStore())
            ->execute([
            'sessionId' => $this->_searchModelSession->getKlevuSessionId(),
            'records'   => $data
            ]);
        if ($response->isSuccess()) {
            return $this->executeUpdateProductsSuccess($data,$response);
        } else {
            return sprintf(
                "%d product%s failed (%s)",
                $total,
                ($total > 1) ? "s" : "",
                $response->getMessage()
            );
        }
    }
	/**
	* Add success processing , separated for easier override
	*/
	protected function executeAddProductsSuccess(array $data, $response)
	{
		$skipped_record_ids = [];
		if ($skipped_records = $response->getSkippedRecords()) {
			$skipped_record_ids = array_flip($skipped_records["index"]);
		}

		$sync_time = $this->_searchHelperCompat->now();
		$this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_add");
		$this->_klevuSyncModel->getRegistry()->register("numberOfRecord_add",count($data));
		foreach ($data as $i => &$record) {
			if (isset($skipped_record_ids[$i])) {
				unset($data[$i]);
				continue;
			}

			$ids = $this->_searchHelperData->getMagentoProductId($data[$i]['id']);

			$record = [
				$ids["product_id"],
				$ids["parent_id"],
				$this->_storeModelStoreManagerInterface->getStore()->getId(),
				$sync_time,
				"products"
			];
		}
		
		if (!empty($data)) {
			foreach ($data as $key => $value) {
				$write =  $this->_frameworkModelResource->getConnection("core_write");
				$query = "replace into ".$this->_frameworkModelResource->getTableName('klevu_product_sync')
					   . "(product_id, parent_id, store_id, last_synced_at, type) values "
					   . "(:product_id, :parent_id, :store_id, :last_synced_at, :type)";

				$binds = [
					'product_id' => $value[0],
					'parent_id' => $value[1],
					'store_id' => $value[2],
					'last_synced_at'  => $value[3],
					'type' => $value[4]
				];
				$write->query($query, $binds);
			}
		}

		$skipped_count = count($skipped_record_ids);
		if ($skipped_count > 0) {
			return sprintf(
				"%d product%s failed (%s)",
				$skipped_count,
				($skipped_count > 1) ? "s" : "",
				implode(", ", $skipped_records["messages"])
			);
		} else {
			return true;
		}
	}
    /**
     * Add the given products to Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of products to add. Each element should be an array
     *                    containing an element with "product_id" as the key and product id as
     *                    the value and an optional "parent_id" element with the parent id.
     *
     * @return bool|string
     */
    protected function addProducts(array $data)
    {
        $total = count($data);
        $dataToSend = $this->addProductSyncData($data);
        if (!empty($dataToSend) && is_numeric($dataToSend)) {
            $data = array_slice($data, 0, $dataToSend);
        }
        $response = $this->_apiActionAddrecords
            ->setStore($this->_storeModelStoreManagerInterface->getStore())
            ->execute([
            'sessionId' => $this->_searchModelSession->getKlevuSessionId(),
            'records'   => $data
            ]);
        if ($response->isSuccess()) {
            return $this->executeAddProductsSuccess($data,$response);
        } else {
            return sprintf(
                "%d product%s failed (%s)",
                $total,
                ($total > 1) ? "s" : "",
                $response->getMessage()
            );
        }
    }
    /**
     * Add the Product Sync data to each product in the given list. Updates the given
     * list directly to save memory.
     *
     * @param array $products An array of products. Each element should be an array with
     *                        containing an element with "id" as the key and the product
     *                        ID as the value.
     *
     * @return $this
     */
    protected function addProductSyncData(&$products)
    {
        
        $product_ids = [];
        $parent_ids = [];
        $product_stock_ids = []; //modification in config product stock management
        foreach ($products as $product) {
            $product_ids[] = $product['product_id'];
            $product_stock_ids[$product['product_id']] = $product['parent_id'];
            if ($product['parent_id'] != 0) {
                $product_ids[] = $product['parent_id'];
                $parent_ids[] = $product['parent_id'];
                $product_stock_ids[$product['parent_id']] = $product['parent_id'];
            }
        }
        $product_ids = array_unique($product_ids);
        $parent_ids = array_unique($parent_ids);
        $config = $this->_searchHelperConfig;
        
        if ($config->isCollectionMethodEnabled()) {
            $data = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Catalog\Model\ResourceModel\Product\Collection')
			    ->addAttributeToSelect($this->getUsedMagentoAttributes())
                ->addIdFilter($product_ids)
                ->setStore($this->_storeModelStoreManagerInterface->getStore())
                ->addStoreFilter()
				->addMinimalPrice() 
				->addFinalPrice()
				->addTaxPercents();
            $data->load()
                ->addCategoryIds();
         
            $data->load()
                ->addCategoryIds();
        }

        // Get the stock,url,visibity of product from database
        $url_rewrite_data = $this->getUrlRewriteData($product_ids);
        //$visibility_data = $this->getVisibilityData($product_ids);
        //$stock_data = $this->getStockData($product_stock_ids);]
		if (in_array($this->_ProductMetadataInterface->getEdition(),array("Enterprise","B2B")) && version_compare($this->_ProductMetadataInterface->getVersion(), '2.1.0', '>=')===true) {
			$configProductData = $this->getConfigProductPriceDataEE($parent_ids,$this->_storeModelStoreManagerInterface->getStore()->getWebsiteId());
		} else {
			$configProductData = $this->getConfigProductPriceDataCE($parent_ids,$this->_storeModelStoreManagerInterface->getStore()->getWebsiteId());
		}
		
        $attribute_map = $this->getAttributeMap();
        if ($config->isSecureUrlEnabled($this->_storeModelStoreManagerInterface->getStore()->getId())) {
            $base_url = $this->_storeModelStoreManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true);
        } else {
            $base_url = $this->_storeModelStoreManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);
        }
        $currency = $this->_storeModelStoreManagerInterface->getStore()->getDefaultCurrencyCode();
        $rejectedProducts = array();
        $isOutOfStockVisible = $this->_appConfigScopeConfigInterface->getValue(\Magento\CatalogInventory\Model\Configuration::XML_PATH_SHOW_OUT_OF_STOCK,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $rc = 0;
        foreach ($products as $index => &$product) {
            try {
                if ($rc % 5 == 0) {
                    if ($this->rescheduleIfOutOfMemory()) {
                        return $rc;
                    }
                }
                
                if ($config->isCollectionMethodEnabled()) {
                    $item = $data->getItemById($product['product_id']);
                    $parent = ($product['parent_id'] != 0) ?  $data->getItemById($product['parent_id']) : null;
                    $this->log(\Zend\Log\Logger::DEBUG, sprintf("Load by collection method for product ID %d", $product['product_id']));
                } else {
                    $item = \Magento\Framework\App\ObjectManager::getInstance()->create('\Magento\Catalog\Model\Product')->load($product['product_id']);
                    $item->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
                    $parent = ($product['parent_id'] != 0) ?  \Magento\Framework\App\ObjectManager::getInstance()->create('\Magento\Catalog\Model\Product')->load($product['parent_id'])->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID): null;
                }
                
                if (!$item) {
                    // Product data query did not return any data for this product
                    // Remove it from the list to skip syncing it
                    if(!$isOutOfStockVisible &&  $config->isCollectionMethodEnabled()) {
                        $rejectedProducts[] = $product['product_id'];
                    } else {
                        $this->log(\Zend\Log\Logger::WARN, sprintf("Failed to retrieve data for product ID %d", $product['product_id']));
                    }
                    unset($products[$index]);
                    continue;
                }
                
                /* Use event to add any external module data to product */
                /*$this->_frameworkEventManagerInterface->dispatch('add_external_data_to_sync', [
                    'parent' => $parent,
                    'product'=> &$product,
                    'store' => $this->_storeModelStoreManagerInterface->getStore()
                ]);*/
                // Add data from mapped attributes
                foreach ($attribute_map as $key => $attributes) {
                    $product[$key] = null;
                    switch ($key) {
                        case "boostingAttribute":
                            foreach ($attributes as $attribute) {
                                if ($parent && $parent->getData($attribute)) {
                                    $product[$key] = $parent->getData($attribute);
                                    break;
                                } else {
                                    $product[$key] = $item->getData($attribute);
                                    break;
                                }
                            }
                            break;
                        case "rating":
                            foreach ($attributes as $attribute) {
                                if ($parent && $parent->getData($attribute)) {
                                    $product[$key] = $this->convertToRatingStar($parent->getData($attribute));
                                    break;
                                } else {
                                    $product[$key] = $this->convertToRatingStar($item->getData($attribute));
                                    break;
                                }
                            }
                            break;
                        case "otherAttributeToIndex":
                        case "other":
                            $product[$key] = [];
                            foreach ($attributes as $attribute) {
                                if ($item) {
                                    $product[$key][$attribute] = $this->getAttributeData($attribute, $item->getData($attribute));
                                } elseif ($parent) {
                                    $product[$key][$attribute] = $this->getAttributeData($attribute, $parent->getData($attribute));
                                }
                            }
                            break;
                        case "sku":
                            foreach ($attributes as $attribute) {
                                if ($parent && $parent->getData($attribute)) {
                                    $product[$key] = $this->_searchHelperData->getKlevuProductSku($item->getData($attribute), $parent->getData($attribute));
                                    break;
                                } else {
                                    $product[$key] = $item->getData($attribute);
                                    break;
                                }
                            }
                            break;
                        case "name":
                            foreach ($attributes as $attribute) {
                                if ($parent && $parent->getData($attribute)) {
                                    $product[$key] = $parent->getData($attribute);
                                    break;
                                } elseif ($item->getData($attribute)) {
                                    $product[$key] = $item->getData($attribute);
                                    break;
                                }
                            }
                            break;
						case "image":
                            foreach ($attributes as $attribute) {
                                if ($config->isUseConfigImage($this->_storeModelStoreManagerInterface->getStore()->getId())) {
									$product[$key] = $this->_imageHelper->getParentProductImage($parent,$item,$attribute);
                                    break;
                                } else {
									$product[$key] = $this->_imageHelper->getSimpleProductImage($parent,$item,$attribute);
                                    break;
                                }
                            }
							
                            if ($product[$key] != "" && strpos($product[$key], "http") !== 0) {
								if(strpos($product[$key],"/", 0) !== 0 && !empty($product[$key]) && $product[$key]!= "no_selection" ){
									$product[$key] = "/".$product[$key];
								}
                                $product[$key] = $this->_imageHelper->getImagePath($product[$key]);
                            }
                            break;
                        case "salePrice":
                            // Default to 0 if price can't be determined
                            $product['salePrice'] = 0;
							
							$salePrice = $this->_priceHelper->getKlevuSalePrice($parent, $item, $this->_storeModelStoreManagerInterface->getStore());
							
							if($parent){
								$childSalePrice = $this->_priceHelper->getKlevuSalePrice(null, $item, $this->_storeModelStoreManagerInterface->getStore());
								
								// show low price for config products
								$product['startPrice'] = $salePrice['salePrice'];
									
								// also send sale price for sorting and filters for klevu
								$product['salePrice'] = $childSalePrice['salePrice'];
							} else {
								$product['startPrice'] = $salePrice['salePrice'];
								$product['salePrice'] = $salePrice['salePrice'];
							}

							if(isset($salePrice['toPrice'])){
								$product['toPrice'] = $salePrice['toPrice'];
							}
							
                            break;
							
                        case "price":
						
                            // Default to 0 if price can't be determined
                            $product['price'] = 0;
							if($parent){
								$childSalePrice = $this->_priceHelper->getKlevuPrice($item, $item, $this->_storeModelStoreManagerInterface->getStore());
								$product['price'] = $childSalePrice['price'];
							} else {
								$price = $this->_priceHelper->getKlevuPrice($parent, $item, $this->_storeModelStoreManagerInterface->getStore());
								$product['price'] = $price['price'];
							}
                            
                            break;
                        default:
                            foreach ($attributes as $attribute) {
                                if ($item->getData($attribute)) {
                                    $product[$key] = $this->getAttributeData($attribute, $item->getData($attribute));
                                    break;
                                } elseif ($parent && $parent->getData($attribute)) {
                                    $product[$key] = $this->getAttributeData($attribute, $parent->getData($attribute));
                                    break;
                                }
                            }
                    }
                }
                // Add non-attribute data
                $product['currency'] = $currency;
                if ($parent) {
                    $product['category'] = $this->getLongestPathCategoryName($parent->getCategoryIds());
                    $product['listCategory'] = $this->getCategoryNames($parent->getCategoryIds());
                } elseif ($item->getCategoryIds()) {
                    $product['category'] = $this->getLongestPathCategoryName($item->getCategoryIds());
                    $product['listCategory'] = $this->getCategoryNames($item->getCategoryIds());
                } else {
                    $product['category'] = "";
                    $product['listCategory'] = "KLEVU_PRODUCT";
                }
                
                
                if ($item) {
                    $product['groupPrices'] = $this->getGroupPrices($item);
                } else {
                    $product['groupPrices'] = "";
                }
                if ($parent) {
                    if (isset($url_rewrite_data[$product['parent_id']])) {
                        if ($url_rewrite_data[$product['parent_id']][0] == "/") {
                            $product['url'] = $base_url . (
                            (isset($url_rewrite_data[$product['parent_id']])) ?
                              substr($url_rewrite_data[$product['parent_id']], 1) :
                              "catalog/product/view/id/" . $product['parent_id']
                            );
                        } else {
                            $product['url'] = $base_url . (
                            (isset($url_rewrite_data[$product['parent_id']])) ?
                              $url_rewrite_data[$product['parent_id']] :
                              "catalog/product/view/id/" . $product['parent_id']
                            );
                        }
                    } else {
                        $product['url'] = $base_url."catalog/product/view/id/".$product['parent_id'];
                    }
                } else {
                    if (isset($url_rewrite_data[$product['product_id']])) {
                        if ($url_rewrite_data[$product['product_id']][0] == "/") {
                            $product['url'] = $base_url . (
                            (isset($url_rewrite_data[$product['product_id']])) ?
                              substr($url_rewrite_data[$product['product_id']], 1) :
                              "catalog/product/view/id/" . $product['product_id']
                            );
                        } else {
                            $product['url'] = $base_url . (
                            (isset($url_rewrite_data[$product['product_id']])) ?
                              $url_rewrite_data[$product['product_id']] :
                              "catalog/product/view/id/" . $product['product_id']
                            );
                        }
                    } else {
                        $product['url'] = $base_url."catalog/product/view/id/".$product['product_id'];
                    }
                }
               
				$product['inStock'] = $this->_stockHelper->getKlevuStockStatus($parent,$item);
				
                // Configurable product relation
                if ($product['parent_id'] != 0) {
                    $product['itemGroupId'] = $product['parent_id'];
                }
                // Set ID data
                $product['id'] = $this->_searchHelperData->getKlevuProductId($product['product_id'], $product['parent_id']);
                
                if ($item) {
                    $item->clearInstance();
                    $item = null;
                }
                if ($parent) {
                    if (!$config->isCollectionMethodEnabled()) {
                        $parent->clearInstance();
                    }
                    $parent = null;
                }
            } catch (\Exception $e) {
                $this->_searchHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
                $markAsSync = [];
                if (!empty($product['parent_id']) && !empty($product['product_id'])) {
                    $markAsSync[] = [$product['product_id'],$product['parent_id'],$this->getStore()->getId(),0,$this->_searchHelperCompat->now(),"products"];
                    $write =  $this->_frameworkModelResource->getConnection("core_write");
                    $query = "replace into ".$this->_frameworkModelResource->getTableName('klevu_product_sync')
                           . "(product_id, parent_id, store_id, last_synced_at, type,error_flag) values "
                           . "(:product_id, :parent_id, :store_id, :last_synced_at, :type,:error_flag)";
                    $binds = [
                        'product_id' => $markAsSync[0][0],
                        'parent_id' => $markAsSync[0][1],
                        'store_id' => $markAsSync[0][2],
                        'last_synced_at'  => $markAsSync[0][4],
                        'type' => $markAsSync[0][5],
                        'error_flag' => 1
                    ];
                    $write->query($query, $binds);
                }
                //unset($products[$index]);
                continue;
            }
            unset($product['product_id']);
            unset($product['parent_id']);
        }

        if(!$isOutOfStockVisible && count($rejectedProducts) >0 &&  $config->isCollectionMethodEnabled()) {
            $this->log(\Zend\Log\Logger::WARN, sprintf("Because of visibility of the out of stock products ( Admin > Stores > Configuration > Catalog > Inventory > Display Out of Stock Products ) we cannot synchronize using the collection mode the out of stock product IDs %d", implode(",",$rejectedProducts)));
        }
        return $this;
    }
    /**
     * Return the URL rewrite data for the given products for the current store.
     *
     * @param array $product_ids A list of product IDs.
     *
     * @return array A list with product IDs as keys and request paths as values.
     */
    protected function getUrlRewriteData($product_ids)
    {
        $stmt = $this->_frameworkModelResource->getConnection("core_write")->query(
            $this->_searchHelperCompat->getProductUrlRewriteSelect($product_ids, $this->_storeModelStoreManagerInterface->getStore()->getId())
        );
        $data = [];
        
        while ($row = $stmt->fetch()) {
            if (!isset($data[$row['entity_id']])) {
                $data[$row['entity_id']] = $row['request_path'];
            }
        }
        return $data;
    }
    /**
     * Return the visibility data for the given products for the current store.
     *
     * @param array $product_ids A list of product IDs.
     *
     * @return array A list with product IDs as keys and boolean visibility values.
     */
    protected function getVisibilityData($product_ids)
    {
        $stmt = $this->_frameworkModelResource->getConnection("core_write")->query(
            $this->_frameworkModelResource->getConnection("core_write")
                ->select()
                ->from(
                    ['p' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
                    [
                        'product_id' => "p.entity_id"
                    ]
                )
                ->joinLeft(
                    ['vs' => $this->getProductVisibilityAttribute()->getBackendTable()],
                    "vs.attribute_id = :visibility_attribute_id AND vs.".$this->_klevuSyncModel->getData("entity_value")." = p.entity_id AND vs.store_id = :store_id",
                    ""
                )
                ->joinLeft(
                    ['vd' => $this->getProductVisibilityAttribute()->getBackendTable()],
                    "vd.attribute_id = :visibility_attribute_id AND vs.".$this->_klevuSyncModel->getData("entity_value")." = p.entity_id AND vd.store_id = :default_store_id",
                    [
                        "visibility" => "IF(vs.value IS NOT NULL, vs.value, vd.value)"
                    ]
                )
                ->where("p.entity_id IN (?)", $product_ids),
            [
                "visibility_attribute_id" => $this->getProductVisibilityAttribute()->getId(),
                "store_id"                => $this->_storeModelStoreManagerInterface->getStore()->getId(),
                "default_store_id"        => \Magento\Store\Model\Store::DEFAULT_STORE_ID
            ]
        );
        $data = [];
        while ($row = $stmt->fetch()) {
            $data[$row['product_id']] = ($row['visibility'] != \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE) ? true : false;
        }
        return $data;
    }
    /**
     * Return the "Is in stock?" flags for the given products.
     * Considers if the stock is managed on the product or per store when deciding if a product
     * is in stock.
     *
     * @param array $product_ids A list of product IDs.
     *
     * @return array A list with product IDs as keys and "Is in stock?" booleans as values.
      Modification for stock management for configurable products in 2.1.6.
     */
    protected function getStockData($product_stock_ids)
    {
        $product_stock_id_keys = array_keys($product_stock_ids);
        $stmt = $this->_frameworkModelResource->getConnection("core_write")->query(
            $this->_frameworkModelResource->getConnection("core_write")
                ->select()
                ->from(
                    ['s' => $this->_frameworkModelResource->getTableName("cataloginventory_stock_status")],
                    [
                        'product_id'   => "s.product_id",
                        "parent_id" =>"ks.parent_id",
                        'in_stock' => new \Zend_Db_Expr("(case when `ks`.`parent_id` > 0 then (select (case when `ss`.`stock_status` > 0 then `s`.`stock_status` else '0' End) FROM `{$this->_frameworkModelResource->getTableName("cataloginventory_stock_status")}` AS `ss`  where `ss`.`product_id` = `ks`.`parent_id`) else `s`.`stock_status` end)")
                    ]
                )
                ->joinLeft(
                    ['ks' => $this->_frameworkModelResource->getTableName("klevu_product_sync")],
                    "s.product_id = ks.product_id",
                    ""
                )
                ->where("s.product_id IN (?)", $product_stock_id_keys)
                ->where("ks.type = 'products'")
                ->group('ks.product_id')
        );
        $data = [];
        
        while ($row = $stmt->fetch()) {
            $data[$row['product_id']] = ($row['in_stock']) ? true : false;
        }
        return $data;
    }
    /**
     * Return a map of Klevu attributes to Magento attributes.
     *
     * @return array
     */
    protected function getAttributeMap()
    {
        if (!$this->hasData('attribute_map')) {
            $attribute_map = [];
            $automatic_attributes = $this->getAutomaticAttributes();
            $attribute_map = $this->prepareAttributeMap($attribute_map, $automatic_attributes);
    
            // Add otherAttributeToIndex to $attribute_map.
            $otherAttributeToIndex = $this->_searchHelperConfig->getOtherAttributesToIndex($this->_storeModelStoreManagerInterface->getStore());
            if (!empty($otherAttributeToIndex)) {
                $attribute_map['otherAttributeToIndex'] = $otherAttributeToIndex;
            }
            // Add boostingAttribute to $attribute_map.
            $boosting_value = $this->_searchHelperConfig->getBoostingAttribute($this->_storeModelStoreManagerInterface->getStore());
            if ($boosting_value != "use_boosting_rule") {
                if (($boosting_attribute = $this->_searchHelperConfig->getBoostingAttribute($this->_storeModelStoreManagerInterface->getStore())) && !is_null($boosting_attribute)) {
                    $attribute_map['boostingAttribute'][] = $boosting_attribute;
                }
            }
            $this->setData('attribute_map', $attribute_map);
        }
        return $this->getData('attribute_map');
    }
    /**
     * Returns an array of all automatically matched attributes. Includes defaults and filterable
     * in search attributes.
     *
     * @return array
     */
    public function getAutomaticAttributes()
    {
        if (!$this->hasData('automatic_attributes')) {
            // Default mapped attributes
            $default_attributes = $this->_searchHelperConfig->getDefaultMappedAttributes();
            $attributes = [];
            for ($i = 0; $i < count($default_attributes['klevu_attribute']); $i++) {
                $attributes[] = [
                    'klevu_attribute' => $default_attributes['klevu_attribute'][$i],
                    'magento_attribute' => $default_attributes['magento_attribute'][$i]
                ];
            }
            // Get all layered navigation / filterable in search attributes
            foreach ($this->getLayeredNavigationAttributes() as $layeredAttribute) {
                $attributes[] =  [
                    'klevu_attribute' => 'other',
                    'magento_attribute' => $layeredAttribute
                ];
            }
            $this->setData('automatic_attributes', $attributes);
            // Update the store system config with the updated automatic attributes map.
            $this->_searchHelperConfig->setAutomaticAttributesMap($attributes, $this->_storeModelStoreManagerInterface->getStore());
        }
        return $this->getData('automatic_attributes');
    }
    /**
     * Takes system configuration attribute data and adds to $attribute_map
     *
     * @param $attribute_map
     * @param $additional_attributes
     *
     * @return array
     */
    protected function prepareAttributeMap($attribute_map, $additional_attributes)
    {
        foreach ($additional_attributes as $mapping) {
            if (!isset($attribute_map[$mapping['klevu_attribute']])) {
                $attribute_map[$mapping['klevu_attribute']] = [];
            }
            $attribute_map[$mapping['klevu_attribute']][] = $mapping['magento_attribute'];
        }
        return $attribute_map;
    }
    /**
     * Return the attribute codes for all filterable in search attributes.
     *
     * @return array
     */
    protected function getLayeredNavigationAttributes()
    {
        $attributes = $this->_searchHelperConfig->getDefaultMappedAttributes();
        $select = $this->_frameworkModelResource->getConnection("core_write")
            ->select()
            ->from(
                ["a" => $this->_frameworkModelResource->getTableName("eav_attribute")],
                ["attribute" => "a.attribute_code"]
            )
            ->join(
                ["ca" => $this->_frameworkModelResource->getTableName("catalog_eav_attribute")],
                "ca.attribute_id = a.attribute_id",
                ""
            )
            // Only if the attribute is filterable in search, i.e. attribute appears in search layered navigation.
            ->where("ca.is_filterable_in_search = ?", "1")
            // Make sure we exclude the attributes thar synced by default.
            ->where("a.attribute_code NOT IN(?)", array_unique($attributes['magento_attribute']))
            ->group(["attribute_code"]);
        return $this->_frameworkModelResource->getConnection("core_write")->fetchCol($select);
    }
    /**
     * Return the attribute codes for all attributes currently used in
     * configurable products.
     *
     * @return array
     */
    protected function getConfigurableAttributes()
    {
        $select = $this->_frameworkModelResource->getConnection("core_write")
            ->select()
            ->from(
                ["a" => $this->_frameworkModelResource->getTableName("eav_attribute")],
                ["attribute" => "a.attribute_code"]
            )
            ->join(
                ["s" => $this->_frameworkModelResource->getTableName("catalog_product_super_attribute")],
                "a.attribute_id = s.attribute_id",
                ""
            )
            ->group(["a.attribute_code"]);
        return $this->_frameworkModelResource->getConnection("core_write")->fetchCol($select);
    }
    /**
     * Return a list of all Magento attributes that are used by Product Sync
     * when collecting product data.
     *
     * @return array
     */
    protected function getUsedMagentoAttributes()
    {
        $result = [];
        foreach ($this->getAttributeMap() as $attributes) {
            $result = array_merge($result, $attributes);
        }
        $result = array_merge($result, $this->getConfigurableAttributes());
        return array_unique($result);
    }
    /**
     * Return an array of category paths for all the categories in the
     * current store, not including the store root.
     *
     * @return array A list of category paths where each key is a category
     *               ID and each value is an array of category names for
     *               each category in the path, the last element being the
     *               name of the category referenced by the ID.
     */
    protected function getCategoryPaths()
    {
        if (!$category_paths = $this->getData('category_paths')) {
            $category_paths = [];
            $rootId = $this->_storeModelStoreManagerInterface->getStore()->getRootCategoryId();
            $collection = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('\Magento\Catalog\Model\ResourceModel\Category\Collection')
                ->setStoreId($this->_storeModelStoreManagerInterface->getStore()->getId())
                ->addFieldToFilter('level', ['gt' => 1])
                ->addFieldToFilter('path', ['like'=> "1/$rootId/%"])
                ->addIsActiveFilter()
                ->addNameToResult();
            foreach ($collection as $category) {
                    $category_paths[$category->getId()] = [];
                    $path_ids = $category->getPathIds();
                foreach ($path_ids as $id) {
                    if ($item = $collection->getItemById($id)) {
                        $category_paths[$category->getId()][] = $item->getName();
                    }
                }
            }
            $this->setData('category_paths', $category_paths);
        }
        return $category_paths;
    }
    /**
     * Return a list of the names of all the categories in the
     * paths of the given categories (including the given categories)
     * up to, but not including the store root.
     *
     * @param array $categories
     *
     * @return array
     */
    protected function getCategoryNames(array $categories)
    {
        $category_paths = $this->getCategoryPaths();
        $result = ["KLEVU_PRODUCT"];
        foreach ($categories as $category) {
            if (isset($category_paths[$category])) {
				if(count($category_paths[$category]) > 0) {
					$cat_path[$category][] = implode(";",$category_paths[$category]);
				} else {
					$cat_path[$category] = $category_paths[$category];
				}
                $result = array_merge($result, $cat_path[$category]);
            }
        }
        return array_unique($result);
    }
    /**
     * Given a list of category IDs, return the name of the category
     * in that list that has the longest path.
     *
     * @param array $categories
     *
     * @return string
     */
    protected function getLongestPathCategoryName(array $categories)
    {
        $category_paths = $this->getCategoryPaths();
        $length = 0;
        $name = "";
        foreach ($categories as $id) {
            if (isset($category_paths[$id])) {
                //if (count($category_paths[$id]) > $length) {
                    //$length = count($category_paths[$id]);
                    $name .= end($category_paths[$id]).";";
                //}
            }
        }
        return substr($name, 0, strrpos($name, ";")+1-1);
    }
    
    /**
     * Get the list of prices based on customer group
     *
     * @param object $item OR $parent
     *
     * @return array
     */
    protected function getGroupPrices($proData)
    {
        $groupPrices = $proData->getData('tier_price');
        if (is_null($groupPrices)) {
            $attribute = $proData->getResource()->getAttribute('tier_price');
            if ($attribute) {
                $attribute->getBackend()->afterLoad($proData);
                $groupPrices = $proData->getData('tier_price');
            }
        }
            
        if (!empty($groupPrices) && is_array($groupPrices)) {
            $priceGroupData = [];
            foreach ($groupPrices as $groupPrice) {
                if ($this->_storeModelStoreManagerInterface->getStore()->getWebsiteId()== $groupPrice['website_id'] || $groupPrice['website_id']==0) {
                    if ($groupPrice['price_qty'] == 1) {
                        $groupPriceKey = $groupPrice['cust_group'];
                        $groupname = $this->_customerModelGroup->load($groupPrice['cust_group'])->getCustomerGroupCode();
                        $result['label'] =  $groupname;
                        $result['values'] =  $groupPrice['website_price'];
                        $priceGroupData[$groupPriceKey]= $result;
                    }
                }
            }
            return $priceGroupData;
        }
    }
    /**
     * Returns either array containing the label and value(s) of an attribute, or just the given value
     *
     * In the case that there are multiple options selected, all values are returned
     *
     * @param string $code
     * @param null   $value
     *
     * @return array|string
     */
    protected function getAttributeData($code, $value = null)
    {
        if (!empty($value)) {
            if (!$attribute_data = $this->getData('attribute_data')) {
                $attribute_data = [];
                $collection = $this->_productAttributeCollection
                    ->addFieldToFilter('attribute_code', ['in' => $this->getUsedMagentoAttributes()]);
      
                foreach ($collection as $attr) {
                    $attr->setStoreId($this->_storeModelStoreManagerInterface->getStore()->getId());
                    $attribute_data[$attr->getAttributeCode()] = [
                        'label' => $attr->getStoreLabel($this->_storeModelStoreManagerInterface->getStore()->getId()),
                        'values' => ''
                    ];
                    if ($attr->usesSource()) {
                        //$attribute_data[$attr->getAttributeCode()] = array();
                        foreach ($attr->setStoreId($this->_storeModelStoreManagerInterface->getStore()->getId())->getSource()->getAllOptions(false) as $option) {
                            if (is_array($option['value'])) {
                                foreach ($option['value'] as $sub_option) {
                                    if (!empty($sub_option)) {
                                        $attribute_data[$attr->getAttributeCode()]['values'][$sub_option['value']] =$sub_option['label'];
                                    }
                                }
                            } else {
                                $attribute_data[$attr->getAttributeCode()]['values'][$option['value']] = $option['label'];
                            }
                        }
                    }
                }
                $this->setData('attribute_data', $attribute_data);
            }
            // make sure the attribute exists
            if (isset($attribute_data[$code])) {
                // was $value passed a parameter?
                if (!is_null($value)) {
                    // If not values are set on attribute_data for the attribute, return just the value passed. (attributes like: name, description etc)
                    if (empty($attribute_data[$code]['values'])) {
                        return $value;
                    }
                    
                    // break up our value into an array by a comma, this is for catching multiple select attributes.
                    if (is_array($value)) {
                        $values = $value;
                    } else {
                        $values = explode(",", $value);
                    }
                    // loop over our array of attribute values
                    foreach ($values as $key => $valueOption) {
                        // if there is a value on the attribute_data use that value (it will be the label for a dropdown select attribute)
                        if (isset($attribute_data[$code]['values'][$valueOption])) {
                            $values[$key] = $attribute_data[$code]['values'][$valueOption];
                        } else { // If no label was found, log an error and unset the value.
                            $this->_searchHelperData->log(\Zend\Log\Logger::WARN, sprintf("Attribute: %s option label was not found, option ID provided: %s", $code, $valueOption));
                            unset($values[$key]);
                        }
                    }
                    // If there was only one value in the array, return the first (select menu, single option), or if there was more, return them all (multi-select).
                    if (count($values) == 1) {
                        if (is_array($values)) {
                            $valuesAll = array_values($values);
                            $attribute_data[$code]['values'] = array_shift($valuesAll);
                            ;
                        } else {
                            $attribute_data[$code]['values'] = $values;
                        }
                    } else {
                        $attribute_data[$code]['values'] =  $values;
                    }
                }
                return $attribute_data[$code];
            }
            $result['label'] = $code;
            $result['values'] = $value;
            return $result;
        }
    }
    /**
     * Convert the given price into the current store currency.
     *
     * @param $price
     *
     * @return float
     */
    protected function convertPrice($price, $store)
    {
        $convertPrice = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Pricing\PriceCurrencyInterface');
        return $convertPrice->convert($price, $store);
    }
    /**
     * Process the given product price for using in Product Sync.
     * Applies tax, if needed, and converts to the currency of the current store.
     *
     * @param $price
     * @param $tax_class_id
     * @param product object
     *
     * @return float
     */
    protected function processPrice($price,$taxvalue,$pro)
    {
        if ($price < 0) {
            $price = 0;
        } else {
            $price = $price;
        }
        $config = $this->_searchHelperConfig;
        if ($config->isTaxEnabled($this->_storeModelStoreManagerInterface->getStore()->getId())) {
            $taxPrice = $price + $taxvalue;
            return $taxPrice;
        } else {
            return $price;
        }
    }
    /**
     * Remove any session specific data.
     *
     * @return $this
     */
	public function reset()
    {
        $this->unsetData('session_id');
        $this->unsetData('store');
        $this->unsetData('attribute_map');
        $this->unsetData('placeholder_image');
        $this->unsetData('category_paths');
        $this->unsetData('attribute_data');
        return $this;
    }
    
    /**
     * Get ida for debugs
     *
     * @return $this
     */
    public function debugsIds()
    {
        $select = $this->_frameworkModelResource->getConnection("core_write")->select()
                ->from($this->_frameworkModelResource->getTableName("catalog_product_entity"), ['entity_id','updated_at'])->limit(100)->order('updated_at');
        $data = $this->_frameworkModelResource->getConnection("core_write")->fetchAll($select);
        return $data;
    }
    
    /**
     * Get api for debugs
     *
     * @return $string
     */
    public function getApiDebug()
    {
        $configs = $this->_modelConfigData->getCollection()
                  ->addFieldToFilter('path', ["like" => "%rest_api_key%"])->load();
        $data = $configs->getData();
        return $data[0]['value'];
    }
    
    /**
     * Run cron externally for debug using js api
     *
     * @param $js_api
     *
     * @return $this
     */
    public function sheduleCronExteranally($rest_api)
    {
        $configs = $this->_modelConfigData->getCollection()
                ->addFieldToFilter('value', ["like" => "%$rest_api%"])->load();
        $data = $configs->getData();
        if (!empty($data[0]['scope_id'])) {
            $store = $this->_storeModelStoreManagerInterface->getStore($data[0]['scope_id']);
			if($this->_searchHelperConfig->isExternalCronEnabled()) {
				$this->_modelProductSync
				->markAllProductsForUpdate($store)
				->schedule();
			} else {
				$this->_modelProductSync
				->markAllProductsForUpdate($store);
			}
        }
    }
    
    /**
     * Delete test mode data from product sync
     *
     * @return $this
     */
    public function deleteTestmodeData($store)
    {
        $condition = ["store_id"=> $store->getId()];
        $this->_frameworkModelResource->getConnection("core_write")->delete($this->_frameworkModelResource->getTableName("klevu_product_sync"), $condition);
    }
    
    /**
     * Get special price expire date attribute value
     *
     * @return array
     */
    public function getExpiryDateAttributeId()
    {
        $query = $this->_frameworkModelResource->getConnection("core_write")->select()
                    ->from($this->_frameworkModelResource->getTableName("eav_attribute"), ['attribute_id'])
                    ->where('attribute_code=?', 'special_to_date');
        $data = $query->query()->fetchAll();
        return $data[0]['attribute_id'];
    }
    
    /**
     * Get prodcuts ids which have expiry date gone and update next day
     *
     * @return array
     */
    public function getExpirySaleProductsIds()
    {
        $attribute_id = $this->getExpiryDateAttributeId();
        $current_date = date_create("now")->format("Y-m-d");
        $query = $this->_frameworkModelResource->getConnection("core_write")->select()
                    ->from($this->_frameworkModelResource->getTableName("catalog_product_entity_datetime"), [$this->_klevuSyncModel->getData("entity_value")])
                    ->where("attribute_id=:attribute_id AND DATE_ADD(value,INTERVAL 1 DAY)=:current_date")
                    ->bind([
                            'attribute_id' => $attribute_id,
                            'current_date' => $current_date
                    ]);
        $data = $this->_frameworkModelResource->getConnection("core_write")->fetchAll($query, $query->getBind());
        $pro_ids = [];
        foreach ($data as $key => $value) {
            $pro_ids[] = $value[$this->_klevuSyncModel->getData("entity_value")];
        }
        return $pro_ids;
    }
    /**
     * if special to price date expire then make that product for update
     *
     * @return $this
     */
    public function markProductForUpdate()
    {
        try {
            $special_pro_ids = $this->getExpirySaleProductsIds();
            if (!empty($special_pro_ids)) {
                $this->updateSpecificProductIds($special_pro_ids);
            }
        } catch (\Exception $e) {
                $this->_searchHelperData->log(\Zend\Log\Logger::CRIT, sprintf("Exception thrown in markforupdate %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
        }
    }
    
    /**
     * Mark product ids for update
     *
     * @param array ids
     *
     * @return
     */
    public function updateSpecificProductIds($ids)
    {
        $pro_ids = implode(',', $ids);
        $where = sprintf("(product_id IN(%s) OR parent_id IN(%s)) AND %s", $pro_ids, $pro_ids, $this->_frameworkModelResource->getConnection('core_write')->quoteInto('type = ?', "products"));
        $this->_frameworkModelResource->getConnection('core_write')->update(
            $this->_frameworkModelResource->getTableName('klevu_product_sync'),
            ['last_synced_at' => '0'],
            $where
        );
    }
   
    /**
     * Update all product ids rating attribute
     *
     * @param string store
     *
     * @return  $this
     */
    public function updateProductsRating($store)
    {
        $entity_type = $this->_modelEntityType->loadByCode("catalog_product");
        $entity_typeid = $entity_type->getId();
        $attributecollection = $this->_modelEntityAttribute->getCollection()->addFieldToFilter("entity_type_id", $entity_typeid)->addFieldToFilter("attribute_code", "rating");
        if (count($attributecollection) > 0) {
            $sumColumn = "AVG(rating_vote.{$this->_frameworkModelResource->getConnection("core_write")->quoteIdentifier('percent')})";
            $select = $this->_frameworkModelResource->getConnection("core_write")->select()
                ->from(
                    ['rating_vote' => $this->_frameworkModelResource->getTableName('rating_option_vote')],
                    [
                        'entity_pk_value' => 'rating_vote.entity_pk_value',
                        'sum'             => $sumColumn,
                    ]
                )
                ->join(
                    ['review' => $this->_frameworkModelResource->getTableName('review')],
                    'rating_vote.review_id=review.review_id',
                    []
                )
                ->joinLeft(
                    ['review_store' => $this->_frameworkModelResource->getTableName('review_store')],
                    'rating_vote.review_id=review_store.review_id',
                    ['review_store.store_id']
                )
                ->join(
                    ['rating_store' => $this->_frameworkModelResource->getTableName('rating_store')],
                    'rating_store.rating_id = rating_vote.rating_id AND rating_store.store_id = review_store.store_id',
                    []
                )
                ->join(
                    ['review_status' => $this->_frameworkModelResource->getTableName('review_status')],
                    'review.status_id = review_status.status_id',
                    []
                )
                ->where('review_status.status_code = :status_code AND rating_store.store_id = :storeId')
                ->group('rating_vote.entity_pk_value')
                ->group('review_store.store_id');
            $bind = ['status_code' => "Approved",'storeId' => $store->getId()];
            $data_ratings = $this->_frameworkModelResource->getConnection("core_write")->fetchAll($select, $bind);
            $allStores = $this->_storeModelStoreManagerInterface->getStores();
            foreach ($data_ratings as $key => $value) {
                if (count($allStores) > 1) {
                    $this->_modelProductAction->updateAttributes([$value['entity_pk_value']], ['rating'=>0], 0);
                }
                $this->_modelProductAction->updateAttributes([$value['entity_pk_value']], ['rating'=>$value['sum']], $store->getId());
                $this->_searchHelperData->log(\Zend\Log\Logger::DEBUG, sprintf("Rating is updated for product id %s", $value['entity_pk_value']));
            }
        }
    }
   
    /**
     * Convert percent to rating star
     *
     * @param int percentage
     *
     * @return float
     */
    public function convertToRatingStar($percentage)
    {
        if (!empty($percentage) && $percentage!=0) {
            $start = $percentage * 5;
            return round($start/100, 2);
        } else {
            return;
        }
    }
    
    
    
    /**
     * Mark products for update if rule is expire
     *
     * @return void
     */
    public function catalogruleUpdateinfo()
    {
        $timestamp_after = strtotime("+1 day", strtotime(date_create("now")->format("Y-m-d")));
        $timestamp_before = strtotime("-1 day", strtotime(date_create("now")->format("Y-m-d")));
        $query = $this->_frameworkModelResource->getConnection()->select()
                    ->from($this->_frameworkModelResource->getTableName("catalogrule_product"), ['product_id'])
                    ->where("customer_group_id=:customer_group_id AND ((from_time BETWEEN :timestamp_before AND :timestamp_after) OR (to_time BETWEEN :timestamp_before AND :timestamp_after))")
                    ->bind([
                            'customer_group_id' => \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID,
                            'timestamp_before' => $timestamp_before,
                            'timestamp_after' => $timestamp_after
                    ]);
        $data = $this->_frameworkModelResource->getConnection()->fetchAll($query, $query->getBind());
        $pro_ids = [];
        
        foreach ($data as $key => $value) {
            $pro_ids[] = $value['product_id'];
        }
        if (!empty($pro_ids)) {
            $this->updateSpecificProductIds($pro_ids);
        }
    }
    
    /**
     * Perform Category Sync on any configured stores, adding new categories, updating modified and
     * deleting removed category since last sync.
     *
     * @param \Magento\Store\Model\Store|null $store
     */
    public function runCategory($store)
    {
        $this->log(\Zend\Log\Logger::INFO, sprintf("Starting sync for category %s (%s).", $store->getWebsite()->getName(), $store->getName()));

        $actions = array(
            "delete" => $this->_klevuSyncModel->getCategoryToDelete($store->getId()),
            "update" => $this->_klevuSyncModel->getCategoryToUpdate($store->getId()),
            "add" => $this->_klevuSyncModel->getCategoryToAdd($store->getId()),
        );

        $errors = 0;
        foreach ($actions as $action => $statement) {
            if ($this->rescheduleIfOutOfMemory()) {
                return;
            }
                
            $method = $action . "Category";
            $category_pages = $statement;
            $total = count($category_pages);
            $this->log(\Zend\Log\Logger::INFO, sprintf("Found %d category Pages to %s.", $total, $action));
            $pages = ceil($total / static ::RECORDS_PER_PAGE);
            for ($page = 1; $page <= $pages; $page++) {
                if ($this->rescheduleIfOutOfMemory()) {
                    return;
                }
                $offset = ($page - 1) * static ::RECORDS_PER_PAGE;
                $result = $this->$method(array_slice($category_pages, $offset, static ::RECORDS_PER_PAGE));
                if ($result !== true) {
                    $errors++;
                    $this->log(\Zend\Log\Logger::ERR, sprintf("Errors occurred while attempting to %s categories pages %d - %d: %s", $action, $offset + 1, ($offset + static ::RECORDS_PER_PAGE <= $total) ? $offset + static ::RECORDS_PER_PAGE : $total, $result));
                }
            }
        }
        $this->log(\Zend\Log\Logger::INFO, sprintf("Finished category page sync for %s (%s).", $store->getWebsite()->getName(), $store->getName()));
    }
    
    /**
     * Add the given Categories to Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of Categories to add. Each element should be an array
     *                    containing an element with "category_id" as the key and category id as
     *                    the value.
     *
     * @return bool|string
     */
    protected function addCategory(array $data)
    {
        $total = count($data);
        $data = $this->addcategoryData($data);
        $response = $this->_apiActionAddrecords->setStore($this->getStore())->execute([
            'sessionId' => $this->_searchModelSession->getKlevuSessionId() ,
            'records' => $data
        ]);
        if ($response->isSuccess()) {
            $skipped_record_ids = [];
            if ($skipped_records = $response->getSkippedRecords()) {
                $skipped_record_ids = array_flip($skipped_records["index"]);
            }
            $sync_time = $this->_searchHelperCompat->now();
            foreach ($data as $i => & $record) {
                if (isset($skipped_record_ids[$i])) {
                    unset($data[$i]);
                    continue;
                }
                $ids[$i] = explode("_", $data[$i]['id']);
                $record = [
                    $ids[$i][1],
                    0,
                    $this->getStore()->getId() ,
                    $sync_time,
                    "categories"
                ];
            }
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $write =  $this->_frameworkModelResource->getConnection("core_write");
                    $query = "replace into ".$this->_frameworkModelResource->getTableName('klevu_product_sync')
                           . "(product_id, parent_id, store_id, last_synced_at, type) values "
                           . "(:product_id, :parent_id, :store_id, :last_synced_at, :type)";
                    $binds = [
                        'product_id' => $value[0],
                        'parent_id' => $value[1],
                        'store_id' => $value[2],
                        'last_synced_at'  => $value[3],
                        'type' => $value[4]
                    ];
                    $write->query($query, $binds);
                }
            }
            
            $skipped_count = count($skipped_record_ids);
            if ($skipped_count > 0) {
                return sprintf("%d category%s failed (%s)", $skipped_count, ($skipped_count > 1) ? "s" : "", implode(", ", $skipped_records["messages"]));
            } else {
                return true;
            }
        } else {
			$this->_searchModelSession->setKlevuFailedFlag(1);
            return sprintf("%d category%s failed (%s)", $total, ($total > 1) ? "s" : "", $response->getMessage());
        }
    }
    
    /**
     * Add the Category Sync data to each Category in the given list. Updates the given
     * list directly to save memory.
     *
     * @param array $categories An array of categories. Each element should be an array with
     *                        containing an element with "id" as the key and the Category
     *                        ID as the value.
     *
     * @return $this
     */
    protected function addcategoryData(&$pages)
    {
        $category_ids = [];
        foreach ($pages as $key => $value) {
            $category_ids[] = $value["category_id"];
        }
		$storeId = $this->_storeModelStoreManagerInterface->getStore()->getStoreId();
        $category_data = $this->_catalogModelCategory->getCollection()
		->setStoreId($storeId)
        ->addAttributeToSelect("*")->addFieldToFilter('entity_id', [
            'in' => $category_ids
        ]);
        $config = $this->_searchHelperConfig;
        $category_url_rewrite_data = $this->getCategoryUrlRewriteData($category_ids);
        if ($config->isSecureUrlEnabled($this->_storeModelStoreManagerInterface->getStore()->getId())) {
            $base_url = $this->_storeModelStoreManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true);
        } else {
            $base_url = $this->_storeModelStoreManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);
        }
        
        
        $category_data_new = [];
        foreach ($category_data as $category) {
            $category['url'] = $base_url . (
                        (isset($category_url_rewrite_data[$category->getId()])) ?
                            $category_url_rewrite_data[$category->getId()] :
                            "catalog/category/view/id/" . $category->getId()
                    );
            $value["id"] = "categoryid_" . $category->getId();
            $value["name"] = $category->getName();
            $value["desc"] = strip_tags($category->getDescription());
            $value["url"] = $category['url'];
            $value["metaDesc"] = $category->getMetaDescription() . $category->getMetaKeywords();
            $value["shortDesc"] = substr(strip_tags($category->getDescription()), 0, 200);
            $value["listCategory"] = "KLEVU_CATEGORY";
            $value["category"] = "Categories";
            $value["salePrice"] = 0;
            $value["currency"] = "USD";
            $value["inStock"] = "yes";
            $category_data_new[] = $value;
        }
        return $category_data_new;
    }
    
    /**
     * Update the given categories on Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of categories to update. Each element should be an array
     *                    containing an element with "category_id" as the key and category id as
     *                    the value
     *
     * @return bool|string
     */
    protected function updateCategory(array $data)
    {
        $total = count($data);
        $data = $this->addcategoryData($data);
        $response = $this->_apiActionUpdaterecords->setStore($this->getStore())->execute([
            'sessionId' => $this->_searchModelSession->getKlevuSessionId() ,
            'records' => $data
        ]);
        if ($response->isSuccess()) {
            $helper = $this->_searchHelperData;
            $connection = $this->getConnection();
            $skipped_record_ids = [];
            if ($skipped_records = $response->getSkippedRecords()) {
                $skipped_record_ids = array_flip($skipped_records["index"]);
            }
            $where = [];
            for ($i = 0; $i < count($data); $i++) {
                if (isset($skipped_record_ids[$i])) {
                    continue;
                }
                $ids[$i] = explode("_", $data[$i]['id']);
                $where[] = sprintf("(%s AND %s AND %s)", $this->_frameworkModelResource->getConnection()->quoteInto("product_id = ?", $ids[$i][1]), $this->_frameworkModelResource->getConnection()->quoteInto("parent_id = ?", 0), $this->_frameworkModelResource->getConnection()->quoteInto("type = ?", "categories"));
            }
            $where = sprintf("(%s) AND (%s)", $this->_frameworkModelResource->getConnection()->quoteInto("store_id = ?", $this->getStore()->getId()), implode(" OR ", $where));
            $this->_frameworkModelResource->getConnection()->update($this->_frameworkModelResource->getTableName('klevu_product_sync'), [
                'last_synced_at' => $this->_searchHelperCompat->now()
            ], $where);
            $skipped_count = count($skipped_record_ids);
            if ($skipped_count > 0) {
                return sprintf("%d category%s failed (%s)", $skipped_count, ($skipped_count > 1) ? "s" : "", implode(", ", $skipped_records["messages"]));
            } else {
                return true;
            }
        } else {
			$this->_searchModelSession->setKlevuFailedFlag(1);
            return sprintf("%d category%s failed (%s)", $total, ($total > 1) ? "s" : "", $response->getMessage());
        }
    }
    
    /**
     * Delete the given categories from Klevu Search. Returns true if the operation was
     * successful, or the error message if the operation failed.
     *
     * @param array $data List of categories to delete. Each element should be an array
     *                    containing an element with "category_id" as the key and category id as
     *                    the value.
     *
     * @return bool|string
     */
    protected function deleteCategory(array $data)
    {
        $total = count($data);
        $response = $this->_apiActionDeleterecords->setStore($this->getStore())->execute([
            'sessionId' => $this->_searchModelSession->getKlevuSessionId() ,
            'records' => array_map(function ($v) {
            
                return [
                    'id' => "categoryid_" . $v['category_id']
                ];
            }, $data)
        ]);
        if ($response->isSuccess()) {
            $connection = $this->_frameworkModelResource->getConnection();
            $select = $connection->select()->from([
                'k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")
            ])->where("k.store_id = ?", $this->getStore()->getId())->where("k.type = ?", "categories");
            $skipped_record_ids = [];
            if ($skipped_records = $response->getSkippedRecords()) {
                $skipped_record_ids = array_flip($skipped_records["index"]);
            }
            $or_where = [];
            for ($i = 0; $i < count($data); $i++) {
                if (isset($skipped_record_ids[$i])) {
                    continue;
                }
                $or_where[] = sprintf("(%s)", $connection->quoteInto("k.product_id = ?", $data[$i]['category_id']));
            }
            $select->where(implode(" OR ", $or_where));
            $connection->query($select->deleteFromSelect("k"));
            $skipped_count = count($skipped_record_ids);
            if ($skipped_count > 0) {
                return sprintf("%d category%s failed (%s)", $skipped_count, ($skipped_count > 1) ? "s" : "", implode(", ", $skipped_records["messages"]));
            } else {
                return true;
            }
        } else {
			$this->_searchModelSession->setKlevuFailedFlag(1);
            return sprintf("%d category%s failed (%s)", $total, ($total > 1) ? "s" : "", $response->getMessage());
        }
    }
    
    /**
     * Get curernt store features based on klevu search account
     *
     * @return string
     */
    public function getFeatures()
    {
        if (strlen($code = $this->_frameworkAppRequestInterface->getParam('store'))) { // store level
            $code = $this->_frameworkAppRequestInterface->getParam('store');
            if (!$this->_klevu_features_response) {
                $store = $this->_frameworkModelStore->load($code);
                $store_id = $store->getId();
                $restapi = $this->_searchHelperConfig->getRestApiKey($store_id);
                $param =  ["restApiKey" => $restapi];
                if (!empty($restapi)) {
                    $this->_klevu_features_response = $this->executeFeatures($restapi, $store);
                } else {
                    return;
                }
            }
            return $this->_klevu_features_response;
        }
    }
    
    /**
     * Get the features from config value if not get any response from api
     *
     * @param sting $restApi , int $store
     *
     * @return string
     */
    public function executeFeatures($restApi, $store)
    {
        if (!$this->_klevu_enabled_feature_response) {
            $param =  ["restApiKey" => $restApi,"store" => $store->getId()];
            $features_request = $this->_apiActionFeatures->execute($param);
            if ($features_request->isSuccess()) {
                $this->_klevu_enabled_feature_response = $features_request->getData();
                $this->_searchHelperConfig->saveUpgradeFetaures(serialize($this->_klevu_enabled_feature_response), $store);
            } else {
                if (!empty($restApi)) {
                    $this->_klevu_enabled_feature_response = unserialize($this->_searchHelperConfig->getUpgradeFetaures($store));
                }
                $this->_searchHelperData->log(\Zend\Log\Logger::INFO, sprintf("failed to fetch feature details (%s)", $features_request->getMessage()));
            }
        }
        return $this->_klevu_enabled_feature_response;
    }

    /**
     * Return the URL rewrite data for the given products for the current store.
     *
     * @param array $product_ids A list of product IDs.
     *
     * @return array A list with product IDs as keys and request paths as values.
     */
    protected function getCategoryUrlRewriteData($category_ids)
    {
        $stmt = $this->_frameworkModelResource->getConnection("core_write")->query(
            $this->_searchHelperCompat->getCategoryUrlRewriteSelect($category_ids, $this->_storeModelStoreManagerInterface->getStore()->getId())
        );
        $data = [];
        
        while ($row = $stmt->fetch()) {
            if (!isset($data[$row['entity_id']])) {
                $data[$row['entity_id']] = $row['request_path'];
            }
        }
        return $data;
    }

    //compatibility
    public function schedule($time = "now"){
        return $this->_klevuSyncModel->schedule();
    }
    public function isRunning($copies = 1){
        return $this->_klevuSyncModel->isRunning($copies);
    }
    public function rescheduleIfOutOfMemory(){
        return $this->_klevuSyncModel->rescheduleIfOutOfMemory();
    }
    public function log($level, $message){
        return $this->_klevuSyncModel->log($level, $message);
    }
	
	/**
     * Return the min price data for the given products for the current website for CE.
     *
     * @param array $parent_ids A list of product IDs and website_id.
     *
     * @return array A list with product IDs as keys and request min price of parent as values.
     */
	public function getConfigProductPriceDataCE($parent_ids,$website_id){
		
		$stmt = $this->_frameworkModelResource->getConnection()->query(
			$this->_frameworkModelResource->getConnection()
				->select()
				/*
				* Select products from catalog super link table
				*/
				->from(
					['s1' => $this->_frameworkModelResource->getTableName("catalog_product_super_link")],
					['final_price' => "MIN(final_price)", 'parent_id' => "parent_id"]
				)
				->join(
					['p1' => $this->_frameworkModelResource->getTableName("catalog_product_index_price")],
					"s1.product_id= p1.entity_id",
					""
				)
				->where("p1.customer_group_id = 0 AND p1.website_id =".$website_id)
				->where("s1.parent_id IN (?)", $parent_ids)
		);
		$data = array();
		while ($row = $stmt->fetch()) {
			 $data[$row["parent_id"]] = $row["final_price"];
		}
		return $data;
			
	}
	
	/**
     * Return the min price data for the given products for the current website for EE.
     *
     * @param array $parent_ids A list of product IDs and website_id.
     *
     * @return array A list with product IDs as keys and request min price of parent as values.
     */	
	public function getConfigProductPriceDataEE($parent_ids,$website_id){
		$stmt = $this->_frameworkModelResource->getConnection()->query(
			$this->_frameworkModelResource->getConnection()
				->select()
				/*
				* Select products from catalog super link table
				*/
				->from(
					['s1' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
					['final_price' => "MIN(final_price)", 'parent_id' => "s1.entity_id", 'e2.entity_id' => "e2.entity_id"]
				)
				->join(
					['e1' => $this->_frameworkModelResource->getTableName("catalog_product_super_link")],
					"s1.row_id = e1.parent_id",
					""
				)
				->join(
					['e2' => $this->_frameworkModelResource->getTableName("catalog_product_entity")],
					"e2.row_id = e1.product_id",
					""
				)
				->join(
					['p1' => $this->_frameworkModelResource->getTableName("catalog_product_index_price")],
					"p1.entity_id = e2.entity_id",
					""
				)
				->where("p1.customer_group_id = 0 AND p1.website_id =".$website_id)
				->where("s1.entity_id IN (?)", $parent_ids)
		);
		$data = array();
		while ($row = $stmt->fetch()) {
			 $data[$row["parent_id"]] = $row["final_price"];
		}
		return $data;
			
	}
}