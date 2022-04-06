<?php
/**
 * Class \Klevu\Search\Model\Product\MagentoProductActionsInterface
 */

namespace Klevu\Search\Model\Product;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Api\KlevuSyncRepositoryInterface;
use Klevu\Search\Api\MagentoProductSyncRepositoryInterface;
use Klevu\Search\Helper\Config as Klevu_Config;
use Klevu\Search\Model\Context as Klevu_Context;
use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;
use Klevu\Search\Model\Klevu\Klevu;
use Klevu\Search\Model\Klevu\KlevuFactory as Klevu_Factory;
use Klevu\Search\Model\Product\KlevuProductActionsInterface as Klevu_Product_Actions;
use Klevu\Search\Model\Product\LoadAttributeInterface as Klevu_LoadAttribute;
use Klevu\Search\Model\Product\ProductParentInterface as Klevu_Product_Parent;
use Magento\Catalog\Model\Product\Action as Klevu_Catalog_Product_Action;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as Magento_CollectionFactory;
use Magento\Eav\Model\Config as Eav_Config;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\Entity\Attribute as Klevu_Entity_Attribute;
use Magento\Eav\Model\Entity\Type as Klevu_Entity_Type;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class MagentoProductActions extends AbstractModel implements MagentoProductActionsInterface
{
    const PARENT_ID_WHEN_NOT_VISIBLE = '0';
    const MAX_ITERATIONS = 100000;

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
    protected $_klevuConfig;
    protected $_magentoOptionProvider;
    /**
     * @var KlevuSyncRepositoryInterface|null
     */
    private $klevuSyncRepository;
    /**
     * @var MagentoProductSyncRepositoryInterface|mixed
     */
    private $magentoProductRepository;
    /**
     * @var array
     */
    private $parentProductIds;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    public function __construct(
        \Magento\Framework\Model\Context $mcontext,
        Klevu_Context $context,
        Eav_Config $eavConfig,
        Klevu_Product_Parent $klevuProductParent,
        Klevu_Product_Actions $klevuProductAction,
        Klevu_LoadAttribute $loadAttribute,
        Klevu_Factory $klevuFactory,
        Magento_CollectionFactory $magentoCollectionFactory,
        Klevu_HelperManager $klevuHelperManager,
        Klevu_Entity_Type $klevuEntityType,
        Klevu_Entity_Attribute $klevuEntityAttribute,
        Klevu_Catalog_Product_Action $klevuCatalogProductAction,
        Klevu_Config $klevuConfig,
        OptionProvider $magentoOptionProvider,
        // abstract parent
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        KlevuSyncRepositoryInterface $klevuSyncRepository = null,
        MagentoProductSyncRepositoryInterface $magentoProductRepository = null
    )
    {
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
        $this->_frameworkModelResource = $context->getResourceConnection();
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
        $this->_klevuConfig = $klevuConfig;
        $this->_magentoOptionProvider = $magentoOptionProvider;
        $this->klevuSyncRepository = $klevuSyncRepository ?: ObjectManager::getInstance()->get(KlevuSyncRepositoryInterface::class);
        $this->magentoProductRepository = $magentoProductRepository ?: ObjectManager::getInstance()->get(MagentoProductSyncRepositoryInterface::class);
    }

    /**
     * @param StoreInterface $store
     *
     * @return array
     */
    public function updateProductCollection($store = null)
    {
        if (!$store) {
            try {
                $store = $this->_storeModelStoreManagerInterface->getStore();
            } catch (NoSuchEntityException $exception) {
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_CRIT,
                    $exception->getMessage()
                );
                return[];
            }
        }
        $klevuToUpdate = [];
        $maxEntityId = $this->klevuSyncRepository->getMaxSyncId($store);
        if (!$maxEntityId) {
            return $klevuToUpdate;
        }
        $this->klevuSyncRepository->clearKlevuCollection();
        $lastEntityId = 0;
        $i = 0;
        while ($lastEntityId < $maxEntityId) {
            $productsToSync = $this->klevuSyncRepository->getProductIdsForUpdate($store, [], $lastEntityId);
            if (!$productsToSync || ++$i >= static::MAX_ITERATIONS) {
                break;
            }
            $lastEntityId = (int)max(array_column($productsToSync, Klevu::FIELD_ENTITY_ID));
            foreach ($productsToSync as $klevuItem) {
                $parentFieldId = $klevuItem[Klevu::FIELD_PARENT_ID];
                $productFieldId = $klevuItem[Klevu::FIELD_PRODUCT_ID];
                $uniqueGroupKey = $parentFieldId . "-" . $productFieldId;
                $klevuToUpdate[$uniqueGroupKey]["product_id"] = $productFieldId;
                $klevuToUpdate[$uniqueGroupKey]["parent_id"] = $parentFieldId;
            }
        }

        return $klevuToUpdate;
    }

    /**
     * @param StoreInterface $store
     *
     * @return array
     */
    public function addProductCollection($store = null)
    {
        if (!$store) {
            try {
                $store = $this->_storeModelStoreManagerInterface->getStore();
            } catch (NoSuchEntityException $exception) {
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_CRIT,
                    $exception->getMessage()
                );
                return[];
            }
        }
        $maxEntityId = $this->magentoProductRepository->getMaxProductId($store);
        if (!$maxEntityId) {
            return [];
        }
        $this->klevuSyncRepository->clearKlevuCollection();
        $batchedProductIds = [];
        $productCollection = $this->magentoProductRepository->getProductIdsCollection($store);
        $childProductCollection = $this->magentoProductRepository->getChildProductIdsCollection($store);
        $lastProductEntityId = 0;
        $lastChildEntityId = 0;
        $i = 0;
        while ($lastProductEntityId < $maxEntityId || $lastChildEntityId < $maxEntityId) {
            $productIds = ($lastProductEntityId < $maxEntityId) ?
                $this->getMagentoProductIds($productCollection, $store, [], $lastProductEntityId) :
                [];
            $childProductIds = ($lastChildEntityId < $maxEntityId) ?
                $this->getMagentoProductIds($childProductCollection, $store, [], $lastChildEntityId) :
                [];

            if ((!$productIds && !$childProductIds) || ++$i >= static::MAX_ITERATIONS) {
                break;
            }
            $lastProductEntityId = $productIds ? (int)max($productIds) : static::MAX_ITERATIONS + 1;
            $lastChildEntityId = $childProductIds ? (int)max($childProductIds) : static::MAX_ITERATIONS + 1;

            $magentoProductIdsToFilter = array_merge($productIds, $childProductIds);
            // getParentRelationsByChild method has to remain in this class for backwards compatibility
            // it is called from within formatMagentoProductIds,
            $magentoProductIds = array_merge(
                $this->formatMagentoProductIds($productIds),
                $this->formatMagentoProductIds($childProductIds, true)
            );
            unset($productIds, $childProductIds);

            // check klevu products for this batch of magento products ($magentoProductIdsToFilter)
            // getKlevuProductCollection method has to remain in this class for backwards compatibility
            $klevuProductIds = $this->getKlevuProductCollection($store, $magentoProductIdsToFilter);
            array_walk($klevuProductIds, function (&$v) {
                unset($v['row_id']);
            });
            unset($magentoProductIdsToFilter);

            if ($diff = $this->diffMultiDimensionalArrays($magentoProductIds, $klevuProductIds)) {
                $batchedProductIds[] = $this->formatProductIdsToAdd($diff, $store);
            }
            unset($klevuProductIds, $magentoProductIds);
        }

        return array_merge([], ...array_filter($batchedProductIds));
    }

    /**
     * @param StoreInterface|null $store
     *
     * @return array
     */
    public function deleteProductCollection($store = null)
    {
        if (!$store) {
            try {
                $store = $this->_storeModelStoreManagerInterface->getStore();
            } catch (NoSuchEntityException $exception) {
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_CRIT,
                    $exception->getMessage()
                );
                return[];
            }
        }
        $maxEntityId = $this->klevuSyncRepository->getMaxSyncId($store);
        if (!$maxEntityId) {
            return [];
        }
        $batchedProductIds = [];
        $this->klevuSyncRepository->clearKlevuCollection();
        $lastEntityId = 0;
        $i = 0;
        while ($lastEntityId <  $maxEntityId) {
            // getKlevuProductCollection method has to remain in this class for backwards compatibility
            $klevuProductIds = $this->getKlevuProductCollection($store, [], $lastEntityId);
            if (!$klevuProductIds || ++$i >= static::MAX_ITERATIONS) {
                break;
            }
            $lastEntityId = (int)max(array_column($klevuProductIds, Klevu::FIELD_ENTITY_ID));
            $productIdsToFilter = array_map(static function (array $product) {
                return $product['product_id'];
            }, $klevuProductIds);
            array_walk($klevuProductIds, function (&$v) {
                unset($v['row_id']);
            });

            // check magento products for this batch of klevu products ($productIdsToFilter)
            $magentoProductIdsCollection = $this->magentoProductRepository->getProductIdsCollection(
                $store,
                MagentoProductSyncRepositoryInterface::NOT_VISIBLE_INCLUDED
            );
            $magentoProductIds = $this->getMagentoProductIds($magentoProductIdsCollection, $store, $productIdsToFilter);
            // getParentRelationsByChild method has to remain in this class for backwards compatibility
            // it is called from within formatMagentoProductIds,
            $magentoProductIds = $this->formatMagentoProductIds($magentoProductIds);
            unset($productIdsToFilter);

            if ($diff = $this->diffMultiDimensionalArrays($klevuProductIds, $magentoProductIds)) {
                $batchedProductIds[] = $this->formatProductIdsToDelete($diff, $store);
            }
            unset($klevuProductIds, $magentoProductIds);
        }

        return  array_merge([], ...array_filter($batchedProductIds));
    }

    /**
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    private function diffMultiDimensionalArrays(array $array1, array $array2)
    {
        $jsonDiff = array_diff(
            array_map('json_encode', $array1),
            array_map('json_encode', $array2)
        );

        return array_map('json_decode', $jsonDiff);
    }

    /**
     * method retained for backward compatibility
     *
     * @param StoreInterface $store
     * @param array|null $productIds
     * @param int|null $lastEntityId
     *
     * @return array
     */
    public function getKlevuProductCollection($store = null, $productIds = [], $lastEntityId = null)
    {
        return $this->klevuSyncRepository->getProductIds($store, $productIds, $lastEntityId);
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
        $this->_apiActionDeleterecords
            ->setStore($this->_storeModelStoreManagerInterface->getStore());
        $response = $this->_apiActionDeleterecords
            ->execute([
                'sessionId' => $this->_searchModelSession->getKlevuSessionId(),
                'records' => array_map(function ($v) {
                    return ['id' => $this->_searchHelperData->getKlevuProductId($v['product_id'], $v['parent_id'])];
                }, $data)
            ]);
        if ($response->isSuccess()) {
            return $this->_klevuProductAction->executeDeleteProductsSuccess($data, $response);
        }

        $this->_searchModelSession->setKlevuFailedFlag(1);
        return sprintf(
            "%d product%s failed (%s)",
            $total,
            ($total > 1) ? "s" : "",
            $response->getMessage()
        );
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
        $this->_apiActionUpdaterecords
            ->setStore($this->_storeModelStoreManagerInterface->getStore());
        $response = $this->_apiActionUpdaterecords
            ->execute([
                'sessionId' => $this->_searchModelSession->getKlevuSessionId(),
                'records' => $data
            ]);
        if ($response->isSuccess()) {
            return $this->_klevuProductAction->executeUpdateProductsSuccess($data, $response);
        }

        return sprintf(
            "%d product%s failed (%s)",
            $total,
            ($total > 1) ? "s" : "",
            $response->getMessage()
        );
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
        $dataToSend = $this->_loadAttribute->addProductSyncData($data);
        if (!empty($dataToSend) && is_numeric($dataToSend)) {
            $data = array_slice($data, 0, $dataToSend);
        }
        $this->_apiActionAddrecords
            ->setStore($this->_storeModelStoreManagerInterface->getStore());
        $response = $this->_apiActionAddrecords
            ->execute([
                'sessionId' => $this->_searchModelSession->getKlevuSessionId(),
                'records' => $data
            ]);
        if ($response->isSuccess()) {
            return $this->_klevuProductAction->executeAddProductsSuccess($data, $response);
        }

        return sprintf(
            "%d product%s failed (%s)",
            $total,
            ($total > 1) ? "s" : "",
            $response->getMessage()
        );
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
            if ($this->_searchHelperConfig->isExternalCronEnabled()) {
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
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_CRIT, sprintf("Exception thrown in markforupdate %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage()));
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
                        'sum' => $sumColumn,
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
            $bind = ['status_code' => "Approved", 'storeId' => $store->getId()];
            $data_ratings = $this->_frameworkModelResource->getConnection("core_write")->fetchAll($select, $bind);
            $allStores = $this->_storeModelStoreManagerInterface->getStores();
            foreach ($data_ratings as $key => $value) {
                if (count($allStores) > 1) {
                    $this->_klevuCatalogProductAction->updateAttributes([$value['entity_pk_value']], ['rating' => 0], 0);
                }
                $this->_klevuCatalogProductAction->updateAttributes([$value['entity_pk_value']], ['rating' => $value['sum']], $store->getId());
                $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_DEBUG, sprintf("Rating is updated for product id %s", $value['entity_pk_value']));
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

    /**
     * Mark records for update storewise
     *
     * @param int|array $productIds
     * @param string $recordType
     * @param null $stores
     *
     * @return mixed|void
     */
    public function markRecordIntoQueue($productIds, $recordType = 'products', $stores = null)
    {
        if (is_array($productIds)) {
            $ids = implode(',', $productIds);
        } else {
            $ids = (int)$productIds;
        }
        $whereType = $this->_frameworkModelResource->getConnection('core_write')->quoteInto('type = ?', $recordType);
        $where = sprintf("(product_id IN(%s) OR parent_id IN(%s)) AND %s", $ids, $ids, $whereType);
        if (!empty($stores)) {
            if (is_array($stores)) {
                $storeIds = implode(',', $stores);
            } else {
                $storeIds = (int)$stores;
            }
            $where .= sprintf(" AND `store_id` IN(%s)", $storeIds);
        }
        $this->_frameworkModelResource->getConnection('core_write')->update(
            $this->_frameworkModelResource->getTableName('klevu_product_sync'),
            ['last_synced_at' => '0'],
            $where
        );
    }

    /**
     * Returns parent relations data by child ids
     *
     * @param array $ids
     *
     * @return array
     */
    public function getParentRelationsByChild($ids)
    {
        $list = $this->magentoProductRepository->getParentProductRelations($ids);
        $result = [];
        foreach ($list as $row) {
            if (!isset($result[$row['product_id']])) {
                $result[$row['product_id']] = [];
            }
            $result[$row['product_id']][] = $row;
        }
        unset($list);

        return $result;
    }

    /**
     * @param null $stores
     * @return mixed|void
     */
    public function markCategoryRecordIntoQueue($stores = null)
    {
        $whereType = $this->_frameworkModelResource->getConnection('core_write')->quoteInto('type = ?', 'categories');
        $where = sprintf(" %s", $whereType);
        if (!empty($stores)) {
            if (is_array($stores)) {
                $storeIds = implode(',', $stores);
            } else {
                $storeIds = (int)$stores;
            }
            $where .= sprintf(" AND `store_id` IN(%s)", $storeIds);
        }
        $this->_frameworkModelResource->getConnection('core_write')->update(
            $this->_frameworkModelResource->getTableName('klevu_product_sync'),
            ['last_synced_at' => '0'],
            $where
        );
    }

    /**
     * @param ProductCollection $productCollection
     * @param StoreInterface $store
     * @param array|null $productIds
     * @param int|null $lastEntityId
     *
     * @return array
     */
    private function getMagentoProductIds(
        ProductCollection $productCollection,
        StoreInterface $store,
        $productIds = [],
        $lastEntityId = null
    ) {
        return $this->magentoProductRepository->getBatchDataForCollection(
            $productCollection,
            $store,
            $productIds,
            $lastEntityId
        );
    }

    /**
     * @param array $productIds
     * @param bool|null $skipChildProducts
     *
     * @return array
     */
    private function formatMagentoProductIds(
        $productIds = [],
        $skipChildProducts = false
    ) {
        $magentoProductIds = [];
        if (!$productIds) {
            return $magentoProductIds;
        }
        // getParentRelationsByChild method has to remain in this class for backwards compatibility
        $parentIds = $this->getParentRelationsByChild($productIds);

        foreach ($productIds as $entityId) {
            $entityParentIds = isset($parentIds[$entityId]) ? $parentIds[$entityId] : [];
            foreach ($entityParentIds as $entityParentId) {
                $magentoProductIds[] = [
                    'product_id' => $entityId,
                    'parent_id' => $entityParentId[Entity::DEFAULT_ENTITY_ID_FIELD]
                ];
            }
            if ($skipChildProducts) {
                continue;
            }
            $magentoProductIds[] = [
                'product_id' => $entityId,
                'parent_id' => static::PARENT_ID_WHEN_NOT_VISIBLE
            ];
        }
        unset($parentIds);

        return $magentoProductIds;
    }

    /**
     * @param StoreInterface $store
     *
     * @return array
     */
    private function getParentProductIds(StoreInterface $store)
    {
        if (!$this->parentProductIds) {
            $this->parentProductIds = $this->magentoProductRepository->getParentProductIds($store);
        }

        return $this->parentProductIds;
    }

    /**
     * @param array $productIdsToDelete
     * @param StoreInterface $store
     *
     * @return array
     */
    private function formatProductIdsToDelete(array $productIdsToDelete, StoreInterface $store)
    {
        $return = [];
        foreach ($productIdsToDelete as $productIds) {
            $productIds = (array)$productIds;
            $parentId = $productIds[Klevu::FIELD_PARENT_ID];
            $productId = $productIds[Klevu::FIELD_PRODUCT_ID];
            $return[$parentId . '-' . $productId] = [
                'parent_id' => $parentId,
                'product_id' => $productId
            ];
            if (
                !(int)$parentId ||
                ($parentId && in_array($parentId, $this->getParentProductIds($store), true))
            ) {
                continue;
            }
            $return[$productId] = [
                'parent_id' => $parentId,
                'product_id' => $productId
            ];
        }

        return $return;
    }

    /**
     * @param array|Object[] $productIdsToAdd
     * @param StoreInterface $store
     *
     * @return array
     */
    private function formatProductIdsToAdd(array $productIdsToAdd, StoreInterface $store)
    {
        $productsIds = [];
        foreach ($productIdsToAdd as $productIds) {
            $productIds = (array)$productIds;
            $parentId = $productIds[Klevu::FIELD_PARENT_ID];
            $productId = $productIds[Klevu::FIELD_PRODUCT_ID];
            if (
                (int)$parentId &&
                !in_array($parentId, $this->getParentProductIds($store), true)
            ) {
                continue;
            }
            $productsIds[$parentId . '-' . $productId] = [
                'parent_id' => $parentId,
                'product_id' => $productId
            ];
        }

        return $productsIds;
    }
}
