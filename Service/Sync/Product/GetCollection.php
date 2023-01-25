<?php

namespace Klevu\Search\Service\Sync\Product;

use Klevu\Search\Api\Ui\DataProvider\Listing\Sync\GetCollectionInterface;
use Klevu\Search\Model\Klevu\Klevu;
use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Model\Product\Sync\ResourceModel\History\Collection as SyncHistoryCollection;
use Klevu\Search\Model\Product\Sync\ResourceModel\History\CollectionFactory as SyncHistoryCollectionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Indexer\Category\Flat\State as FlatCategoryState;
use Klevu\Search\Model\Catalog\ResourceModel\Product\Collection as ProductCollection;
use Klevu\Search\Model\Catalog\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Klevu\Search\Model\Product\Sync\ResourceModel\History as HistoryResource;
use Magento\Catalog\Model\Product\CatalogPrice;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Eav\Model\Entity;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Zend_Db_Select_Exception;

class GetCollection implements GetCollectionInterface
{
    const FIELD_LAST_SYNCED_AT = 'last_synced_at';
    const FIELD_PRODUCT_PARENT_ID = 'product_parent_id';
    const TABLE_CAT_PROD_SUPER_LINK = 'catalog_product_super_link';
    const TABLE_CAT_PROD_SUPER_LINK_ALIAS = 'l';
    const TABLE_CAT_PROD_ENTITY_ALIAS = 'e';
    const TABLE_CAT_PROD_ENTITY_PARENT_ALIAS = 'pe';
    const TABLE_KLEVU_SYNC = 'klevu_product_sync';
    const TABLE_SYNC_ALIAS = 'sync';
    const TABLE_PRODUCTS_TMP = 'products';

    /**
     * @var ProductCollectionFactory
     */
    private $collectionFactory;
    /**
     * @var FlatCategoryState
     */
    private $flatCategoryState;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var SyncHistoryCollectionFactory
     */
    private $syncHistoryCollectionFactory;
    /**
     * @var string
     */
    private $syncHistorySelect;

    /**
     * @param ProductCollectionFactory $collectionFactory
     * @param FlatCategoryState $flatCategoryState
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param SyncHistoryCollectionFactory $syncHistoryCollectionFactory
     */
    public function __construct(
        ProductCollectionFactory $collectionFactory,
        FlatCategoryState $flatCategoryState,
        RequestInterface $request,
        LoggerInterface $logger,
        SyncHistoryCollectionFactory $syncHistoryCollectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->flatCategoryState = $flatCategoryState;
        $this->request = $request;
        $this->logger = $logger;
        $this->syncHistoryCollectionFactory = $syncHistoryCollectionFactory;
    }

    /**
     * @return ProductCollection|null
     * @throws Zend_Db_Select_Exception
     */
    public function execute()
    {
        $collection = $this->collectionFactory->create();
        try {
            $linkField = $this->getLinkField($collection);
        } catch (LocalizedException $exception) {
            $this->logger->error(
                'Error joining sync data to product collection: ' . $exception->getMessage(),
                [
                    'method' => __METHOD__,
                ]
            );

            return $collection;
        }
        $store = $this->request->getParam('store');
        if ($store) {
            $this->outerJoinSyncData($collection, $linkField, $store);
            $collection->setStore($store);
        }

        return $collection;
    }

    /**
     * @param ProductCollection $collection
     *
     * @return string
     * @throws LocalizedException
     */
    private function getLinkField(ProductCollection $collection)
    {
        $productEntity = $collection->getEntity();

        return $productEntity->getLinkField();
    }

    /**
     * @param ProductCollection $collection
     * @param string $linkField
     * @param string $store
     *
     * @return void
     * @throws Zend_Db_Select_Exception
     */
    private function outerJoinSyncData(ProductCollection $collection, $linkField, $store)
    {
        $simpleProductCollection = $this->getProductCollection($collection, $linkField);
        $productCollection = $this->joinParentDataToProductCollection($simpleProductCollection, $linkField);

        $newCollection = clone $collection;
        $newSelect = $this->joinKlevuSyncData($productCollection, $newCollection, 'entity_id', $store);

        $select = $collection->getSelect();
        $select->reset();
        $select->from([
            static::TABLE_CAT_PROD_ENTITY_ALIAS => new \Zend_Db_Expr('(' . $newSelect->__toString() . ')')
        ]);
    }

    /**
     * @param ProductCollection $collection
     * @param string $linkField
     *
     * @return ProductCollection
     */
    private function getProductCollection($collection, $linkField)
    {
        $store = $this->request->getParam('store');
        $isJoinRequired = $this->isJoinRequired();

        $collection->addStoreFilter($store);
        $select = $collection->getSelect();
        $select->reset(Select::COLUMNS);
        $select->columns([
            $linkField,
            ProductInterface::CREATED_AT,
            ProductInterface::UPDATED_AT,
            ProductInterface::SKU,
            ProductInterface::TYPE_ID,
            ProductInterface::ATTRIBUTE_SET_ID
        ]);
        if ($linkField !== Entity::DEFAULT_ENTITY_ID_FIELD) {
            $select->columns([Entity::DEFAULT_ENTITY_ID_FIELD]);
        }
        $collection->setFlag('has_stock_status_filter', true);
        $collection->addAttributeToSelect(ProductInterface::NAME, $isJoinRequired);
        $collection->addAttributeToSelect(ProductInterface::STATUS, $isJoinRequired);
        $collection->addAttributeToSelect(ProductInterface::VISIBILITY, $isJoinRequired);

        return $collection;
    }

    /**
     * @return bool
     */
    private function isJoinRequired()
    {
        return !$this->flatCategoryState->isAvailable();
    }

    /**
     * @param ProductCollection $productCollection
     * @param string $linkField
     *
     * @return ProductCollection
     * @throws Zend_Db_Select_Exception
     */
    private function joinParentDataToProductCollection(
        ProductCollection $productCollection,
        $linkField
    ) {
        $productSelect = $productCollection->getSelect();

        $parentCollection = clone $productCollection;
        $resource = $parentCollection->getResource();
        $parentSelect = $parentCollection->getSelect();

        $linkTable = [
            static::TABLE_CAT_PROD_SUPER_LINK_ALIAS => $resource->getTable(static::TABLE_CAT_PROD_SUPER_LINK)
        ];
        $condition = implode(
            ' AND ',
            [
                sprintf(
                    '(`%s`.`product_id` = `%s`.`%s`)',
                    static::TABLE_CAT_PROD_SUPER_LINK_ALIAS,
                    static::TABLE_CAT_PROD_ENTITY_ALIAS,
                    'entity_id'
                )
            ]
        );
        if ($linkField === 'entity_id') {
            $fields = [
                self::FIELD_PRODUCT_PARENT_ID => 'parent_id',
            ];
            $parentSelect->join($linkTable, $condition, $fields);
        } else {
            // Enterprise edition joins super link on row_id, but we still need the parent entity_id in our result
            $parentSelect->join($linkTable, $condition, []);
            $parentSelect->join(
                [
                    self::TABLE_CAT_PROD_ENTITY_PARENT_ALIAS => $resource->getTable('catalog_product_entity'),
                ],
                sprintf(
                    '%s.parent_id = %s.%s',
                    static::TABLE_CAT_PROD_SUPER_LINK_ALIAS,
                    self::TABLE_CAT_PROD_ENTITY_PARENT_ALIAS,
                    $linkField
                ),
                [
                    self::FIELD_PRODUCT_PARENT_ID => sprintf(
                        '%s.entity_id',
                        self::TABLE_CAT_PROD_ENTITY_PARENT_ALIAS
                    ),
                ]
            );
        }

        // add same column with null value to original select so that they can be merged in union
        $productSelect->columns([
            self::FIELD_PRODUCT_PARENT_ID => new \Zend_Db_Expr('0')
        ]);

        $joinedCollection = clone $productCollection;
        $joinedSelect = $joinedCollection->getSelect();
        $joinedSelect->reset(Select::FROM);
        $joinedSelect->reset(Select::COLUMNS);
        $joinedSelect->union([$parentSelect, $productSelect]);

        $newCollection = clone $productCollection;
        $newSelect = $newCollection->getSelect();
        $newSelect->reset(Select::FROM);
        $newSelect->reset(Select::COLUMNS);
        $newSelect->from([
            self::TABLE_PRODUCTS_TMP => new \Zend_Db_Expr('(' . $joinedSelect->__toString() . ')')
        ]);

        return $newCollection;
    }

    /**
     * @param ProductCollection $productCollection
     * @param ProductCollection $newCollection
     * @param string $linkField
     * @param int $storeId
     *
     * @return Select
     * @throws Zend_Db_Select_Exception
     */
    private function joinKlevuSyncData(
        ProductCollection $productCollection,
        ProductCollection $newCollection,
        $linkField,
        $storeId
    ) {
        $select = $productCollection->getSelect();
        /** @var Product $resource */
        $resource = $productCollection->getResource();

        $klevuSyncTable = [self::TABLE_SYNC_ALIAS => $resource->getTable(static::TABLE_KLEVU_SYNC)];
        $condition = implode(
            ' AND ',
            [
                sprintf(
                    '(`%s`.`%s` = `%s`.`%s`)',
                    self::TABLE_SYNC_ALIAS,
                    Klevu::FIELD_PRODUCT_ID,
                    self::TABLE_PRODUCTS_TMP,
                    $linkField
                ),
                sprintf(
                    '(`%s`.`%s` = `%s`.`%s`)',
                    self::TABLE_SYNC_ALIAS,
                    Klevu::FIELD_PARENT_ID,
                    self::TABLE_PRODUCTS_TMP,
                    self::FIELD_PRODUCT_PARENT_ID
                ),
                sprintf(
                    '(`%s`.`%s` = \'%s\')',
                    self::TABLE_SYNC_ALIAS,
                    Klevu:: FIELD_TYPE,
                    Klevu::OBJECT_TYPE_PRODUCT
                ),
                sprintf(
                    '(`%s`.`%s` = %s)',
                    self::TABLE_SYNC_ALIAS,
                    Klevu::FIELD_STORE_ID,
                    $storeId
                )
            ]
        );
        $fields = [
            Klevu::FIELD_ALIAS_ENTITY_ID => Klevu::FIELD_ENTITY_ID,
            'product_id' => Klevu::FIELD_PRODUCT_ID,
            'parent_id' => Klevu::FIELD_PARENT_ID
        ];
        $where = implode(
            ' AND ',
            [
                sprintf(
                    '(`%s`.`%s` = \'%s\')',
                    self::TABLE_SYNC_ALIAS,
                    Klevu::FIELD_TYPE,
                    Klevu::OBJECT_TYPE_PRODUCT
                ),
                sprintf(
                    '(`%s`.`%s` = %s)',
                    self::TABLE_SYNC_ALIAS,
                    Klevu::FIELD_STORE_ID,
                    $storeId
                )
            ]
        );

        $leftJoin = clone $select;
        $leftJoin->joinLeft($klevuSyncTable, $condition, $fields);
        $this->joinLastSyncedDate($leftJoin, $storeId, $linkField);

        $rightJoin = clone $select;
        $rightJoin->joinRight($klevuSyncTable, $condition, $fields);
        $rightJoin->where($where);
        $this->joinLastSyncedDate($rightJoin, $storeId, $linkField);

        $newSelect = $newCollection->getSelect();
        $newSelect->reset(Select::FROM);
        $newSelect->reset(Select::FROM);
        $newSelect->union([$leftJoin, $rightJoin]);
        $newSelect->group(sprintf('%s.entity_id', self::TABLE_PRODUCTS_TMP));

        return $newSelect;
    }

    /**
     * @param Select $select
     * @param int|string $storeId
     * @param string $linkField
     *
     * @return void
     */
    private function joinLastSyncedDate(Select $select, $storeId, $linkField)
    {
        $historySelect = $this->getHistorySelect();

        $table = ['klevu_sync_history_tmp' => new \Zend_Db_Expr('(' . $historySelect . ')')];
        $condition = implode(
            ' AND ',
            [
                sprintf(
                    'IF (%s.%s IS NOT NULL, %s.%s, %s.%s) = klevu_sync_history_tmp.%s',
                    self::TABLE_PRODUCTS_TMP,
                    $linkField,
                    self::TABLE_PRODUCTS_TMP,
                    $linkField,
                    self::TABLE_SYNC_ALIAS,
                    Klevu::FIELD_PRODUCT_ID,
                    History::FIELD_PRODUCT_ID
                ),
                sprintf(
                    'IF (%s.%s IS NOT NULL, %s.%s, %s.%s) = klevu_sync_history_tmp.%s',
                    self::TABLE_PRODUCTS_TMP,
                    self::FIELD_PRODUCT_PARENT_ID,
                    self::TABLE_PRODUCTS_TMP,
                    self::FIELD_PRODUCT_PARENT_ID,
                    self::TABLE_SYNC_ALIAS,
                    Klevu::FIELD_PARENT_ID,
                    History::FIELD_PARENT_ID
                ),
                sprintf(
                    '%d = klevu_sync_history_tmp.%s',
                    (int)$storeId,
                    History::FIELD_STORE_ID
                ),
            ]
        );
        $fields = implode(
            ' AND ',
            [
                self::FIELD_LAST_SYNCED_AT => sprintf(
                    'klevu_sync_history_tmp.%s',
                    self::FIELD_LAST_SYNCED_AT
                )
            ]
        );

        $select->joinLeft(
            $table,
            $condition,
            $fields
        );
    }

    /**
     * @return Select
     */
    private function getHistorySelect()
    {
        if (null === $this->syncHistorySelect) {
            /** @var SyncHistoryCollection $collection */
            $collection = $this->syncHistoryCollectionFactory->create();
            $resource = $collection->getResource();

            $historySelect = $collection->getSelect();
            $historySelect->reset(Select::FROM);
            $historySelect->from(['klevu_sync_history' => $resource->getTable(HistoryResource::TABLE)]);
            $historySelect->reset(Select::COLUMNS);
            $historySelect->columns(
                [
                    History::FIELD_PRODUCT_ID,
                    History::FIELD_PARENT_ID,
                    History::FIELD_STORE_ID,
                    new \Zend_Db_Expr(
                        sprintf("MAX(%s) as %s", History::FIELD_SYNCED_AT, self::FIELD_LAST_SYNCED_AT)
                    )
                ]
            );
            $historySelect->group([
                History::FIELD_PRODUCT_ID,
                History::FIELD_PARENT_ID,
                History::FIELD_STORE_ID,
            ]);

            $this->syncHistorySelect = $historySelect;
        }

        return $this->syncHistorySelect;
    }
}
