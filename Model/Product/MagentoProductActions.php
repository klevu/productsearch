<?php
/**
 * Class \Klevu\Search\Model\Product\MagentoProductActionsInterface
 */
namespace Klevu\Search\Model\Product;

use Klevu\Search\Model\Product\KlevuProductActionsInterface as Klevu_Product_Actions;
use \Magento\Framework\Model\AbstractModel as AbstractModel;
use \Magento\Eav\Model\Config as Eav_Config;
use Klevu\Search\Model\Product\LoadAttributeInterface as Klevu_LoadAttribute;
use Klevu\Search\Model\Klevu\KlevuFactory as Klevu_Factory;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as Magento_CollectionFactory;
use \Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;
use Klevu\Search\Model\Product\ProductParentInterface as  Klevu_Product_Parent;
use Klevu\Search\Model\Context as Klevu_Context;
use \Magento\Eav\Model\Entity\Type as Klevu_Entity_Type;
use \Magento\Eav\Model\Entity\Attribute as Klevu_Entity_Attribute;
use \Magento\Catalog\Model\Product\Action as Klevu_Catalog_Product_Action;


class MagentoProductActions extends AbstractModel implements MagentoProductActionsInterface
{

    protected $_klevuHelperManager;
    protected $_ProductMetadataInterface;
    protected $_searchModelSession;
    protected $_apiActionDeleterecords;
    protected $_apiActionUpdaterecords;
    protected $_apiActionAddrecords;
    protected $_klevuProductAction;
    protected $_klevuSyncModel;
    protected $_frameworkModelResource;
    protected $_eavModelConfig;
    protected $_loadAttribute;
    protected $_klevuFactory;
    protected $_magentoCollectionFactory;
    protected $_searchHelperData;
	protected $_klevuEntityType;
	protected $_klevuEntityAttribute;
	protected $_klevuProductParentInterface;
	protected $_klevuProductIndividualInterface;

    public function __construct(
		 \Magento\Framework\Model\Context $mcontext,
         Klevu_Context $context,
         Eav_Config $eavConfig,
         Klevu_Product_Parent $klevuProductParent,
         Klevu_Product_Actions $klevuProductAction,
         Klevu_LoadAttribute $loadAttribute,
         Klevu_Factory $klevuFactory,
         Magento_CollectionFactory $magentoCollectionFactory,
         Klevu_HelperManager  $klevuHelperManager,
		 Klevu_Entity_Type $klevuEntityType,
		 Klevu_Entity_Attribute $klevuEntityAttribute,
		 Klevu_Catalog_Product_Action $klevuCatalogProductAction,
		 // abstract parent
		\Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ){
        parent::__construct($mcontext, $registry, $resource, $resourceCollection, $data);
        $this->_klevuHelperManager = $klevuHelperManager;
        $this->_ProductMetadataInterface = $context->getKlevuProductMeta();
        $this->_storeModelStoreManagerInterface = $context->getStoreManagerInterface();
        $this->_searchModelSession = $context->getBackendSession();
        $this->_apiActionDeleterecords = $context->getKlevuProductDelete();
        $this->_apiActionUpdaterecords = $context->getKlevuProductUpdate();
        $this->_apiActionAddrecords = $context->getKlevuProductAdd();
        $this->_klevuProductAction = $klevuProductAction;
        $this->_klevuSyncModel = $context->getSync();
        $this->_frameworkModelResource =  $context->getResourceConnection();
        $this->_eavModelConfig = $eavConfig;
        $this->_loadAttribute = $loadAttribute;
        $this->_klevuFactory = $klevuFactory;
        $this->_magentoCollectionFactory = $magentoCollectionFactory;
        $this->_klevuProductParentInterface = $klevuProductParent;
        $this->_searchHelperData = $this->_klevuHelperManager->getDataHelper();
		$this->_klevuEntityAttribute = $klevuEntityAttribute;
		$this->_klevuEntityType = $klevuEntityType;
		$this->_klevuCatalogProductAction = $klevuCatalogProductAction;
		$this->_klevuProductIndividualInterface = $context->getKlevuProductIndividual();
    }

	public function updateProductCollection($store = null){
        $limit = $this->_klevuSyncModel->getSessionVariable("limit");
        $klevuToUpdate = array();
		$klevu = $this->_klevuFactory->create();
		$klevuCollection = $klevu->getCollection()
            ->addFieldToFilter($klevu->getKlevuField('type'), $klevu->getKlevuType('product'))
            ->addFieldToFilter($klevu->getKlevuField('store_id'), $store->getId())
            ->join(
                ['product' => $this->_frameworkModelResource->getTableName('catalog_product_entity')],
                "main_table." . $klevu->getKlevuField('product_id') . " = product.entity_id AND product.updated_at > main_table.last_synced_at",
                ""
            );
        if(!empty($limit)){
            $klevuCollection->setPageSize($limit);
        }
		
		$klevuCollection->load();
		
        if ($klevuCollection->count() > 0) {
            foreach ($klevuCollection as $klevuItem) {
                $klevuToUpdate[$klevuItem->getData($klevu->getKlevuField('product_id'))]["product_id"] = $klevuItem->getData($klevu->getKlevuField('product_id'));
                $klevuToUpdate[$klevuItem->getData($klevu->getKlevuField('product_id'))]["parent_id"] = $klevuItem->getData($klevu->getKlevuField('parent_id'));

            }
        }
		
		
        return $klevuToUpdate;
	}

	public function addProductCollection($store = null){
		
		$klevu = $this->_klevuFactory->create();
        $limit = $this->_klevuSyncModel->getSessionVariable("limit");
        $products_ids_add = array();
        // Get 'simple','bundle','grouped','Virtual','downloadable' which dont have parent and visibility search,both
		// Use factory to create a new product collection
        $productCollection =  $this->_magentoCollectionFactory->create()
            ->addFieldToSelect('id');
        $productCollection->addStoreFilter($store->getId());
        $productCollection->addAttributeToFilter('type_id', array('in' =>  $this->_klevuProductIndividualInterface->getProductIndividualTypeArray()));
        $productCollection->addAttributeToFilter('status', array('eq' =>  \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        //set visibility filter
        $productCollection->addAttributeToFilter('visibility', array('in' => array( \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH ,\Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH)));
        //$productCollection->getSelect()->where('e.entity_id NOT IN (select '. $klevu->getKlevuField('product_id') .' from '.$this->_frameworkModelResource->getTableName("klevu_product_sync").' where store_id ='.$store->getId().' and type="'.$klevu->getKlevuType('product').'")');
		if(!empty($limit)){
            //$productCollection->setPageSize($limit);
        }

        $m_product_ids = array();
        foreach ($productCollection->getData() as $key => $value){
			$parent_ids = $this->_klevuProductParentInterface->getParentIdsByChild($value['entity_id']);
            if(!empty($parent_ids)) {
                foreach ($parent_ids as $pkey => $pvalue) {
                    $m_product_ids[] = $pvalue . "-" . $value['entity_id'];
                }
            } 
			$parent_id = 0;
			$m_product_ids[] = $parent_id."-".$value['entity_id'];	
        }

		// Get Simple product which have parent and visibility not visible individual
        $productCollection = $this->_magentoCollectionFactory->create()
            ->addFieldToSelect('id');
        $productCollection->addStoreFilter($store->getId());
        $productCollection->addAttributeToFilter('type_id', array('in' =>  $this->_klevuProductIndividualInterface->getProductChildTypeArray()));
        $productCollection->addAttributeToFilter('status', array('eq' =>  \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        //set visibility filter
        $productCollection->addAttributeToFilter('visibility', array('in' => array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE )));
		//$productCollection->getSelect()->where('e.entity_id NOT IN (select '. $klevu->getKlevuField('product_id') .' from '.$this->_frameworkModelResource->getTableName("klevu_product_sync").' where store_id ='.$store->getId().' and type="'.$klevu->getKlevuType('product').'")');
		if(!empty($limit)){
			//$productCollection->setPageSize($limit);
        }
		
	
		
        foreach ($productCollection->getData() as $key => $value){
            $parent_ids = $this->_klevuProductParentInterface->getParentIdsByChild($value['entity_id']);
            if(!empty($parent_ids)) {
                foreach ($parent_ids as $pkey => $pvalue) {
                    $m_product_ids[] = $pvalue . "-" . $value['entity_id'];
                }
            }
        }
		
	
		
        $enable_parent_ids = array();
        // Get parent product,enabled or visibility catalogsearch,search
        $productCollection = $this->_magentoCollectionFactory->create()
            ->addFieldToSelect('id');
        $productCollection->addStoreFilter($store->getId());
        $productCollection->addAttributeToFilter('type_id', array('in' =>  $this->_klevuProductParentInterface->getProductParentTypeArray()));
		$productCollection->addAttributeToFilter('visibility', array('in' => array( \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH ,\Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH)));
		// disable not working for flat indexing  so checking for enabled product only
        $productCollection->addAttributeToFilter('status', array('eq' =>  \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        foreach ($productCollection->getData() as $key => $value){
            $enable_parent_ids[] =  $value['entity_id'];
        }
		
		
		$k_product_ids = array();
        $klevuCollection = $this->getKlevuProductCollection($store);
        foreach($klevuCollection as $k_key => $k_value){
            $k_product_ids[] = $k_value['parent_id']."-".$k_value['product_id'];
        }
		
		$products_to_add = array_diff($m_product_ids,$k_product_ids);
		
		foreach($products_to_add as $key => $value){
			$ids = explode('-',$value);
			if($ids[0] !== "0") {
				if(in_array($ids[0],$enable_parent_ids)) {
					$products_ids_add[$ids[1]]['parent_id'] = $ids[0];
					$products_ids_add[$ids[1]]['product_id'] = $ids[1];
				}
			} else {
				$products_ids_add[$ids[1]]['parent_id'] = $ids[0];
				$products_ids_add[$ids[1]]['product_id'] = $ids[1];
			}	
		}
		return $products_ids_add;
	}
	
	public function deleteProductCollection($store = null){

        $limit = $this->_klevuSyncModel->getSessionVariable("limit");
        $products_ids_delete = array();
        // Get 'simple','bundle','grouped','Virtual','downloadable' which have parent and visibility search,both
        $productCollection = $this->_magentoCollectionFactory->create()
            ->addFieldToSelect('id');
		
        $productCollection->addStoreFilter($store->getId());
        $productCollection->addAttributeToFilter('type_id', array('in' =>  $this->_klevuProductIndividualInterface->getProductIndividualTypeArray()));
        $productCollection->addAttributeToFilter('status', array('eq' =>  \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        //set visibility filter
        $productCollection->addAttributeToFilter('visibility', array('in' => array( \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH ,\Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH,\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE )));
        $m_product_ids = array();
        foreach ($productCollection->getData() as $key => $value){
            $parent_ids = $this->_klevuProductParentInterface->getParentIdsByChild($value['entity_id']);
            if(!empty($parent_ids)) {
                foreach ($parent_ids as $pkey => $pvalue) {
					
					$m_product_ids[] = $pvalue . "-" . $value['entity_id'];
					
                }
            } 
			$parent_id = 0;
			// 1 = not visible individual
			if($value['visibility'] !== "1") {
				$m_product_ids[] = $parent_id."-".$value['entity_id'];
			}
        }
        $enable_parent_ids = array();
        // Get parent product,disabled or visibility catalog
        $productCollection = $this->_magentoCollectionFactory->create()
            ->addFieldToSelect('id');
        $productCollection->addStoreFilter($store->getId());
        $productCollection->addAttributeToFilter('type_id', array('in' => $this->_klevuProductParentInterface->getProductParentTypeArray()));
		$productCollection->addAttributeToFilter('visibility', array('in' => array( \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH ,\Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH)));
		$productCollection->addAttributeToFilter('status', array('eq' =>  \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
		
        foreach ($productCollection->getData() as $key => $value){
            $enable_parent_ids[] =  $value['entity_id'];
        }

		$k_product_ids = array();
        $klevuCollection = $this->getKlevuProductCollection($store);
        foreach($klevuCollection as $k_key => $k_value){
            $k_product_ids[] = $k_value['parent_id']."-".$k_value['product_id'];
			if($k_value['parent_id'] !== "0"){
				if(!in_array($k_value['parent_id'],$enable_parent_ids)){
					$products_ids_delete[$k_value['product_id']]['parent_id'] = $k_value['parent_id'];
					$products_ids_delete[$k_value['product_id']]['product_id'] = $k_value['product_id'];

				} 
			} 
			
        }
		
		
		$products_to_delete = array_diff($k_product_ids,$m_product_ids);
		
		foreach($products_to_delete as $key => $value){
			$ids = explode('-',$value);
			$products_ids_delete[$ids[1]]['parent_id'] = $ids[0];
			$products_ids_delete[$ids[1]]['product_id'] = $ids[1];
		}
		
		return $products_ids_delete;
	}

	public function getKlevuProductCollection($store = null){
		$limit = $this->_klevuSyncModel->getSessionVariable("limit");
        $klevu = $this->_klevuFactory->create();
        $klevuCollection = $klevu->getCollection()
            ->addFieldToSelect($klevu->getKlevuField('product_id'))
            ->addFieldToSelect($klevu->getKlevuField('parent_id'))
            ->addFieldToSelect($klevu->getKlevuField('store_id'))
            ->addFieldToFilter($klevu->getKlevuField('type'), $klevu->getKlevuType('product'))
            ->addFieldToFilter($klevu->getKlevuField('store_id'),$store->getId());
        return $klevuCollection->getData();
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
    public function deleteProducts(array $data)
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
            return $this->_klevuProductAction->executeDeleteProductsSuccess($data,$response);
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
     * Update the given products on Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of products to update. Each element should be an array
     *                    containing an element with "product_id" as the key and product id as
     *                    the value and an optional "parent_id" element with the parent id.
     *
     * @return bool|string
     */
    public function updateProducts(array $data)
    {

        $total = count($data);

        $dataToSend = $this->_loadAttribute->addProductSyncData($data);
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
            return $this->_klevuProductAction->executeUpdateProductsSuccess($data,$response);
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
     * Add the given products to Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of products to add. Each element should be an array
     *                    containing an element with "product_id" as the key and product id as
     *                    the value and an optional "parent_id" element with the parent id.
     *
     * @return bool|string
     */
    public function addProducts(array $data)
    {
        $total = count($data);
        $dataToSend =$this->_loadAttribute->addProductSyncData($data);
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
            return $this->_klevuProductAction->executeAddProductsSuccess($data,$response);
        } else {
            return sprintf(
                "%d product%s failed (%s)",
                $total,
                ($total > 1) ? "s" : "",
                $response->getMessage()
            );
        }
    }

    protected function getSyncDataSqlForCEDelete($store)
    {
        $limit = $this->_klevuSyncModel->getSessionVariable("limit");
        return $this->deleteProductCollection($store);
    }
    protected function getSyncDataSqlForCEUpdate($store)
    {
        $limit = $this->_klevuSyncModel->getSessionVariable("limit");
        return $this->updateProductCollection($store);
    }
    protected function getSyncDataSqlForCEAdd($store)
    {
        $limit = $this->_klevuSyncModel->getSessionVariable("limit");
        return $this->addProductCollection($store);
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
                $this->markAllProductsForUpdate($store);
            } else {
                $this->markAllProductsForUpdate($store);
            }
        }
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
        $entity_type = $this->_klevuEntityType->loadByCode("catalog_product");
        $entity_typeid = $entity_type->getId();
        $attributecollection = $this->_klevuEntityAttribute->getCollection()->addFieldToFilter("entity_type_id", $entity_typeid)->addFieldToFilter("attribute_code", "rating");
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
                    $this->_klevuCatalogProductAction->updateAttributes([$value['entity_pk_value']], ['rating'=>0], 0);
                }
                $this->_klevuCatalogProductAction->updateAttributes([$value['entity_pk_value']], ['rating'=>$value['sum']], $store->getId());
                $this->_searchHelperData->log(\Zend\Log\Logger::DEBUG, sprintf("Rating is updated for product id %s", $value['entity_pk_value']));
            }
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
}