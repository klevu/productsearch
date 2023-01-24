<?php

namespace Klevu\Search\Model\Product;

use InvalidArgumentException;
use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Api\KlevuSyncRepositoryInterface;
use Klevu\Search\Api\MagentoProductSyncRepositoryInterface;
use Klevu\Search\Api\Service\Catalog\Product\Review\UpdateAllRatingsInterface;
use Klevu\Search\Exception\Catalog\Product\Review\KlevuProductAttributeMissingException;
use Klevu\Search\Helper\Config as Klevu_Config;
use Klevu\Search\Helper\Data as SearchData;
use Klevu\Search\Model\Api\Action\Addrecords;
use Klevu\Search\Model\Api\Action\Deleterecords;
use Klevu\Search\Model\Api\Action\Updaterecords;
use Klevu\Search\Model\Context as Klevu_Context;
use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;
use Klevu\Search\Model\Klevu\Klevu;
use Klevu\Search\Model\Klevu\KlevuFactory as Klevu_Factory;
use Klevu\Search\Model\Product\KlevuProductActionsInterface as Klevu_Product_Actions;
use Klevu\Search\Model\Product\LoadAttributeInterface as Klevu_LoadAttribute;
use Klevu\Search\Model\Product\ProductParentInterface as Klevu_Product_Parent;
use Klevu\Search\Model\Sync as KlevuSync;
use Magento\Backend\Model\Session;
use Magento\Catalog\Model\Product as MagentoProduct;
use Magento\Catalog\Model\Product\Action as Klevu_Catalog_Product_Action;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as Magento_CollectionFactory;
use Magento\Customer\Model\Group as CustomerGroup;
use Magento\Eav\Model\Config as Eav_Config;
use Magento\Eav\Model\Entity;
use Magento\Eav\Model\Entity\Attribute as Klevu_Entity_Attribute;
use Magento\Eav\Model\Entity\Type as Klevu_Entity_Type;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class MagentoProductActions extends AbstractModel implements MagentoProductActionsInterface
{
    const PARENT_ID_WHEN_NOT_VISIBLE = '0';
    const MAX_ITERATIONS = 100000;

    /**
     * @var Klevu_HelperManager
     */
    protected $_klevuHelperManager;
    /**
     * @var ProductMetadataInterface
     */
    protected $_ProductMetadataInterface;
    /**
     * @var Session
     */
    protected $_searchModelSession;
    /**
     * @var Deleterecords
     */
    protected $_apiActionDeleterecords;
    /**
     * @var Updaterecords
     */
    protected $_apiActionUpdaterecords;
    /**
     * @var Addrecords
     */
    protected $_apiActionAddrecords;
    /**
     * @var KlevuProductActionsInterface
     */
    protected $_klevuProductAction;
    /**
     * @var KlevuSync
     */
    protected $_klevuSyncModel;
    /**
     * @var ResourceConnection
     */
    protected $_frameworkModelResource;
    /**
     * @var Eav_Config
     */
    protected $_eavModelConfig;
    /**
     * @var LoadAttributeInterface
     */
    protected $_loadAttribute;
    /**
     * @var Klevu_Factory
     */
    protected $_klevuFactory;
    /**
     * @var Magento_CollectionFactory
     */
    protected $_magentoCollectionFactory;
    /**
     * @var SearchData
     */
    protected $_searchHelperData;
    /**
     * @var Klevu_Entity_Type
     */
    protected $_klevuEntityType;
    /**
     * @var Klevu_Entity_Attribute
     */
    protected $_klevuEntityAttribute;
    /**
     * @var ProductParentInterface
     */
    protected $_klevuProductParentInterface;
    /**
     * @var ProductIndividualInterface
     */
    protected $_klevuProductIndividualInterface;
    /**
     * @var Klevu_Config
     */
    protected $_klevuConfig;
    /**
     * @var OptionProvider
     */
    protected $_magentoOptionProvider;
    /**
     * @var KlevuSyncRepositoryInterface
     */
    private $klevuSyncRepository;
    /**
     * @var MagentoProductSyncRepositoryInterface
     */
    private $magentoProductRepository;
    /**
     * @var array
     */
    private $parentProductIds = [];
    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var UpdateAllRatingsInterface
     */
    private $updateAllRatings;

    /**
     * @param \Magento\Framework\Model\Context $mcontext
     * @param Klevu_Context $context
     * @param Eav_Config $eavConfig
     * @param ProductParentInterface $klevuProductParent
     * @param KlevuProductActionsInterface $klevuProductAction
     * @param LoadAttributeInterface $loadAttribute
     * @param Klevu_Factory $klevuFactory
     * @param Magento_CollectionFactory $magentoCollectionFactory
     * @param Klevu_HelperManager $klevuHelperManager
     * @param Klevu_Entity_Type $klevuEntityType
     * @param Klevu_Entity_Attribute $klevuEntityAttribute
     * @param Klevu_Catalog_Product_Action $klevuCatalogProductAction
     * @param Klevu_Config $klevuConfig
     * @param OptionProvider $magentoOptionProvider
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param KlevuSyncRepositoryInterface|null $klevuSyncRepository
     * @param MagentoProductSyncRepositoryInterface|null $magentoProductRepository
     * @param UpdateAllRatingsInterface|null $updateAllRatings
     */
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
        MagentoProductSyncRepositoryInterface $magentoProductRepository = null,
        UpdateAllRatingsInterface $updateAllRatings = null
    ) {
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
        $objectManager = ObjectManager::getInstance();
        $this->klevuSyncRepository = $klevuSyncRepository ?:
            $objectManager->get(KlevuSyncRepositoryInterface::class);
        $this->magentoProductRepository = $magentoProductRepository ?:
            $objectManager->get(MagentoProductSyncRepositoryInterface::class);
        $this->updateAllRatings = $updateAllRatings ?:
            $objectManager->get(UpdateAllRatingsInterface::class);
    }

    /**
     * @param StoreInterface|null $store
     * @param array|null $productIdsToUpdate
     *
     * @return array
     */
    public function updateProductCollection($store = null, $productIdsToUpdate = [])
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
            $productsToSync = $this->klevuSyncRepository->getProductIdsForUpdate(
                $store,
                $productIdsToUpdate,
                $lastEntityId
            );
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
     * @param StoreInterface|null $store
     * @param array|null $productIdsToAdd
     *
     * @return array
     */
    public function addProductCollection($store = null, $productIdsToAdd = [])
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
        $storeId = (int)$store->getId();

        $maxEntityId = $this->magentoProductRepository->getMaxProductId($store);
        if (!$maxEntityId) {
            return [];
        }

        $this->klevuSyncRepository->clearKlevuCollection();
        $batchedProductIds = [];
        $includeOosProductsInSync = $this->_klevuConfig->includeOosProductsInSync($store);
        $productCollection = $this->magentoProductRepository->getProductIdsCollection(
            $store,
            MagentoProductSyncRepositoryInterface::NOT_VISIBLE_EXCLUDED,
            $includeOosProductsInSync
        );
        $childProductCollection = $this->magentoProductRepository->getChildProductIdsCollection(
            $store,
            MagentoProductSyncRepositoryInterface::NOT_VISIBLE_EXCLUDED,
            $includeOosProductsInSync
        );
        $lastProductEntityId = 0;
        $lastChildEntityId = 0;
        $i = 0;
        while ($lastProductEntityId < $maxEntityId || $lastChildEntityId < $maxEntityId) {
            $productIds = ($lastProductEntityId < $maxEntityId) ?
                $this->getMagentoProductIds($productCollection, $store, $productIdsToAdd, $lastProductEntityId) :
                [];
            $childProductIds = ($lastChildEntityId < $maxEntityId) ?
                $this->getMagentoProductIds($childProductCollection, $store, $productIdsToAdd, $lastChildEntityId) :
                [];

            if ((!$productIds && !$childProductIds) || ++$i >= static::MAX_ITERATIONS) {
                break;
            }
            $lastProductEntityId = $productIds ? (int)max($productIds) : $maxEntityId + 1;
            $lastChildEntityId = $childProductIds ? (int)max($childProductIds) : $maxEntityId + 1;

            $magentoProductIdsToFilter = array_merge($productIds, $childProductIds);
            // getParentRelationsByChild method has to remain in this class for backwards compatibility
            // it is called from within formatMagentoProductIds,
            $magentoProductIds = array_merge(
                $this->formatMagentoProductIds($storeId, $productIds, false, $includeOosProductsInSync),
                $this->formatMagentoProductIds($storeId, $childProductIds, true, $includeOosProductsInSync)
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
                $batchedProductIds[] = $this->formatProductIdsToAdd($diff, $store, $includeOosProductsInSync);
            }
            unset($klevuProductIds, $magentoProductIds);
        }

        return array_merge([], ...array_filter($batchedProductIds));
    }

    /**
     * @param StoreInterface|null $store
     * @param array|null $productIdsToDelete
     *
     * @return array
     */
    public function deleteProductCollection($store = null, $productIdsToDelete = [])
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
        $storeId = (int)$store->getId();

        $maxEntityId = $this->klevuSyncRepository->getMaxSyncId($store);
        if (!$maxEntityId) {
            return [];
        }

        $includeOosProductsInSync = $this->_klevuConfig->includeOosProductsInSync($store);

        $batchedProductIds = [];
        $this->klevuSyncRepository->clearKlevuCollection();
        $lastEntityId = 0;
        $i = 0;
        while ($lastEntityId <  $maxEntityId) {
            // getKlevuProductCollection method has to remain in this class for backwards compatibility
            $klevuProductIds = $this->getKlevuProductCollection($store, $productIdsToDelete, $lastEntityId);
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
                MagentoProductSyncRepositoryInterface::NOT_VISIBLE_EXCLUDED,
                $includeOosProductsInSync
            );
            $magentoVisibleProductIds = $this->getMagentoProductIds(
                $magentoProductIdsCollection,
                $store,
                $productIdsToFilter
            );
            // getParentRelationsByChild method has to remain in this class for backwards compatibility
            // it is called from within formatMagentoProductIds,
            $magentoVisibleProductIds = $this->formatMagentoProductIds($storeId, $magentoVisibleProductIds);

            $magentoChildProductIdsCollection = $this->magentoProductRepository->getChildProductIdsCollection(
                $store,
                MagentoProductSyncRepositoryInterface::NOT_VISIBLE_EXCLUDED,
                $includeOosProductsInSync
            );
            $magentoChildProductIds = $this->getMagentoProductIds(
                $magentoChildProductIdsCollection,
                $store,
                $productIdsToFilter
            );
            $magentoChildProductIds = $this->formatMagentoProductIds(
                $storeId,
                $magentoChildProductIds,
                true,
                $includeOosProductsInSync
            );

            // merge invisible child products and visible products, i.e. remove invisible orphaned simple products
            $magentoProductIds = array_merge($magentoVisibleProductIds, $magentoChildProductIds);

            unset($productIdsToFilter);

            if ($diff = $this->diffMultiDimensionalArrays($klevuProductIds, $magentoProductIds)) {
                $batchedProductIds[] = $this->formatProductIdsToDelete($diff, $store, $includeOosProductsInSync);
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

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return array
     */
    protected function getSyncDataSqlForCEDelete($store)
    {
        $limit = $this->_klevuSyncModel->getSessionVariable("limit");
        return $this->deleteProductCollection($store);
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return array
     */
    protected function getSyncDataSqlForCEUpdate($store)
    {
        $limit = $this->_klevuSyncModel->getSessionVariable("limit");
        return $this->updateProductCollection($store);
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return array
     */
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
            $this->setData(
                "status_attribute",
                $this->_eavModelConfig->getAttribute(MagentoProduct::ENTITY, 'status')
            );
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
            $this->setData(
                "visibility_attribute",
                $this->_eavModelConfig->getAttribute(MagentoProduct::ENTITY, 'visibility')
            );
        }
        return $this->getData("visibility_attribute");
    }

    /**
     * Mark all products to be updated the next time Product Sync runs.
     *
     * @param StoreInterface|string|int|null $store If passed, will only update products for the given store.
     *
     * @return $this
     */
    public function markAllProductsForUpdate($store = null)
    {
        $where = "";
        $connection = $this->_frameworkModelResource->getConnection("core_write");
        if ($store !== null) {
            try {
                $store = $this->_storeModelStoreManagerInterface->getStore($store);
                $where = $connection->quoteInto("store_id =  ?", $store->getId());
            } catch (NoSuchEntityException $e) {
                $this->_logger->error($e->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);
            }
        }
        $connection->update(
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
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function clearAllProducts($store = null)
    {
        $connection = $this->_frameworkModelResource->getConnection("core_write");
        $select = $connection->select()
            ->from(
                ["k" => $this->_frameworkModelResource->getTableName("klevu_product_sync")]
            );
        if ($store) {
            try {
                $store = $this->_storeModelStoreManagerInterface->getStore($store);
                $select->where("k.store_id = ?", $store->getId());
            } catch (NoSuchEntityException $e) {
                $this->_logger->error($e->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);
            }
        }
        $result = $connection->query($select->deleteFromSelect("k"));

        return $result->rowCount();
    }

    /**
     * Run cron externally for debug using js api
     *
     * @param string $restApi
     *
     * @return void
     */
    public function sheduleCronExteranally($restApi)
    {
        $configs = $this->_modelConfigData->getCollection();
        $configs->addFieldToFilter('value', ["like" => "%$restApi%"]);
        $configs->load();
        $data = $configs->getData();
        if (empty($data[0]['scope_id'])) {
            return;
        }
        try {
            $store = $this->_storeModelStoreManagerInterface->getStore($data[0]['scope_id']);
            $this->markAllProductsForUpdate($store);
        } catch (NoSuchEntityException $e) {
            $this->_logger->error($e->getMessage(), ['class' => __CLASS__, 'method' => __METHOD__]);
        }
    }

    /**
     * Get special price expire date attribute value
     *
     * @return array
     */
    public function getExpiryDateAttributeId()
    {
        $connection = $this->_frameworkModelResource->getConnection("core_write");
        $query = $connection->select();
        $query->from($this->_frameworkModelResource->getTableName("eav_attribute"), ['attribute_id']);
        $query->where('attribute_code=?', 'special_to_date');
        $data = $connection->fetchAll($query);

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
        $connection = $this->_frameworkModelResource->getConnection("core_write");

        $query = $connection->select();
        $query->from(
            $this->_frameworkModelResource->getTableName("catalog_product_entity_datetime"),
            [$this->_klevuSyncModel->getData("entity_value")]
        );
        $query->where("attribute_id=:attribute_id AND DATE_ADD(value,INTERVAL 1 DAY)=:current_date");
        $bind = [
            'attribute_id' => $attribute_id,
            'current_date' => $current_date
        ];
        $data = $connection->fetchAll($query, $bind);
        $pro_ids = [];
        foreach ($data as $value) {
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
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_CRIT,
                sprintf(
                    "Exception thrown in markforupdate %s::%s - %s",
                    __CLASS__,
                    __METHOD__,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Mark product ids for update
     *
     * @param array $ids
     *
     * @return void
     */
    public function updateSpecificProductIds($ids)
    {
        $connection = $this->_frameworkModelResource->getConnection('core_write');
        $pro_ids = implode(',', $ids);
        $where = sprintf(
            "(product_id IN(%s) OR parent_id IN(%s)) AND %s",
            $pro_ids,
            $pro_ids,
            $connection->quoteInto('type = ?', "products")
        );
        $connection->update(
            $this->_frameworkModelResource->getTableName('klevu_product_sync'),
            ['last_synced_at' => '0'],
            $where
        );
    }

    /**
     * Update all product ids rating attribute
     *
     * @param StoreInterface|string|int $store
     *
     * @return void
     */
    public function updateProductsRating($store)
    {
        try {
            $storeObject = $this->_storeModelStoreManagerInterface->getStore($store);
            $this->updateAllRatings->execute($storeObject->getId());
        } catch (NoSuchEntityException $exception) {
            $this->_logger->error($exception->getMessage());
        } catch (KlevuProductAttributeMissingException $exception) {
            $this->_logger->error($exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            $this->_logger->error($exception->getMessage());
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
            ->where("customer_group_id=:customer_group_id AND (
                (from_time BETWEEN :timestamp_before AND :timestamp_after) OR
                (to_time BETWEEN :timestamp_before AND :timestamp_after)
              )")
            ->bind([
                'customer_group_id' => CustomerGroup::NOT_LOGGED_IN_ID,
                'timestamp_before' => $timestamp_before,
                'timestamp_after' => $timestamp_after
            ]);
        $data = $this->_frameworkModelResource->getConnection()->fetchAll($query, $query->getBind());
        $pro_ids = [];

        foreach ($data as $value) {
            $pro_ids[] = $value['product_id'];
        }
        if (!empty($pro_ids)) {
            $this->updateSpecificProductIds($pro_ids);
        }
    }

    /**
     * Mark records for update storewise
     *
     * @param string|int|array $productIds
     * @param string $recordType
     * @param string|int|array|null $stores
     *
     * @return void
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
     * @param int $storeId
     * @param bool $includeOosParents
     *
     * @return array
     */
    public function getParentRelationsByChild($ids, $storeId = Store::DEFAULT_STORE_ID, $includeOosParents = true)
    {
        $list = $this->magentoProductRepository->getParentProductRelations($ids, $storeId, $includeOosParents);
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
     * @param string|int|array|null $stores
     *
     * @return void
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
     * @param int $storeId
     * @param array $productIds
     * @param bool $skipChildProducts
     * @param bool $includeOosParents
     *
     * @return array
     */
    private function formatMagentoProductIds(
        $storeId,
        $productIds = [],
        $skipChildProducts = false,
        $includeOosParents = true
    ) {
        $magentoProductIds = [];
        if (!$productIds) {
            return $magentoProductIds;
        }
        // getParentRelationsByChild method has to remain in this class for backwards compatibility
        $parentIds = $this->getParentRelationsByChild($productIds, $storeId, $includeOosParents);

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
     * @param bool $includeOosProducts
     *
     * @return array
     */
    private function getParentProductIds(StoreInterface $store, $includeOosProducts = true)
    {
        if (empty($this->parentProductIds[(int)$includeOosProducts])) {
            $this->parentProductIds[(int)$includeOosProducts] = $this->magentoProductRepository->getParentProductIds(
                $store,
                $includeOosProducts
            );
        }

        return $this->parentProductIds[(int)$includeOosProducts];
    }

    /**
     * @param array $productIdsToDelete
     * @param StoreInterface $store
     * @param bool $includeOosProducts
     *
     * @return array
     */
    private function formatProductIdsToDelete(
        array $productIdsToDelete,
        StoreInterface $store,
        $includeOosProducts = true
    ) {
        $return = [];
        $parentProductIds = $this->getParentProductIds($store, $includeOosProducts);
        foreach ($productIdsToDelete as $productIds) {
            $productIds = (array)$productIds;
            $parentId = $productIds[Klevu::FIELD_PARENT_ID];
            $productId = $productIds[Klevu::FIELD_PRODUCT_ID];
            $return[$parentId . '-' . $productId] = [
                'parent_id' => $parentId,
                'product_id' => $productId
            ];
            if (!(int)$parentId ||
                ($parentId && in_array($parentId, $parentProductIds, true))
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
     * @param bool $includeOosProducts
     *
     * @return array
     */
    private function formatProductIdsToAdd(
        array $productIdsToAdd,
        StoreInterface $store,
        $includeOosProducts = true
    ) {
        $productsIds = [];
        $parentProductIds = $this->getParentProductIds($store, $includeOosProducts);
        foreach ($productIdsToAdd as $productIds) {
            $productIds = (array)$productIds;
            $parentId = $productIds[Klevu::FIELD_PARENT_ID];
            $productId = $productIds[Klevu::FIELD_PRODUCT_ID];
            if ((int)$parentId &&
                !in_array($parentId, $parentProductIds, true)
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
