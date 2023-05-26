<?php

namespace Klevu\Search\Model\Product\ResourceModel;

use Klevu\Search\Api\Service\Catalog\Product\JoinParentVisibilityToSelectInterface;
use Klevu\Search\Service\Sync\GetBatchSize;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogInventory\Model\Stock\Status as StockStatus;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Eav\Model\Entity;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

class Product
{
    const CATALOG_PRODUCT_ENTITY_ALIAS = 'e';
    const CATALOG_PRODUCT_SUPER_LINK_ALIAS = 'l';
    const PARENT_STOCK_STATUS_ALIAS = 'parent_stock_status';

    /**
     * @var ProductResourceModel
     */
    private $productResourceModel;
    /**
     * @var OptionProvider
     */
    private $optionProvider;
    /**
     * @var GetBatchSize
     */
    private $getBatchSize;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var JoinParentVisibilityToSelectInterface
     */
    private $joinParentVisibilityToSelectService;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProductResourceModel $productResourceModel
     * @param OptionProvider $optionProvider
     * @param GetBatchSize $getBatchSize
     * @param ResourceConnection|null $resourceConnection
     * @param JoinParentVisibilityToSelectInterface|null $joinParentVisibilityToSelectService
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ProductResourceModel $productResourceModel,
        OptionProvider $optionProvider,
        GetBatchSize $getBatchSize,
        ResourceConnection $resourceConnection = null,
        JoinParentVisibilityToSelectInterface $joinParentVisibilityToSelectService = null,
        LoggerInterface $logger = null
    ) {
        $this->productResourceModel = $productResourceModel;
        $this->optionProvider = $optionProvider;
        $this->getBatchSize = $getBatchSize;
        $objectManager = ObjectManager::getInstance();
        $this->resourceConnection = $resourceConnection ?: $objectManager->get(ResourceConnection::class);
        $this->joinParentVisibilityToSelectService = $joinParentVisibilityToSelectService
            ?: $objectManager->get(JoinParentVisibilityToSelectInterface::class);
        $this->logger = $logger ?: $objectManager->get(LoggerInterface::class);
    }

    /**
     * @param ProductCollection $productCollection
     * @param StoreInterface $store
     * @param array|null $productIds
     * @param int|null $lastEntityId
     *
     * @return array
     */
    public function getBatchDataForCollection(
        ProductCollection $productCollection,
        StoreInterface $store,
        $productIds = [],
        $lastEntityId = null
    ) {
        $connection = $this->productResourceModel->getConnection();

        $select = clone $productCollection->getSelect();
        if ($productIds) {
            $select->where('e.' . Entity::DEFAULT_ENTITY_ID_FIELD . ' IN (?)', $productIds);
        }
        if (null !== $lastEntityId) {
            $select->where('e.' . Entity::DEFAULT_ENTITY_ID_FIELD . ' > ?', $lastEntityId);
            $batchSize = $this->getBatchSize->execute($store);
            $select->limit($batchSize);
        }
        $products = $connection->fetchAll($select);
        $this->logger->debug(
            sprintf('Batch Product Collection Select: %s', $select->__toString())
        );
        unset($connection, $select);

        return array_unique(
            array_column($products, Entity::DEFAULT_ENTITY_ID_FIELD)
        );
    }

    /**
     * @param array $productIds
     * @param int $storeId
     * @param bool $includeOosParents
     * @throws NoSuchEntityException
     *
     * @return array[]
     */
    public function getParentProductRelations(
        array $productIds,
        $storeId = Store::DEFAULT_STORE_ID,
        $includeOosParents = true
    ) {
        $connection = $this->productResourceModel->getConnection();
        $select = $connection->select();
        $select->from(
            [self::CATALOG_PRODUCT_SUPER_LINK_ALIAS => $this->resourceConnection->getTableName('catalog_product_super_link')], // phpcs:ignore Generic.Files.LineLength.TooLong
            []
        );
        $select->join(
            [self::CATALOG_PRODUCT_ENTITY_ALIAS => $this->resourceConnection->getTableName('catalog_product_entity')],
            sprintf(
                '%s.%s = %s.parent_id',
                self::CATALOG_PRODUCT_ENTITY_ALIAS,
                $connection->quoteIdentifier($this->optionProvider->getProductEntityLinkField()),
                self::CATALOG_PRODUCT_SUPER_LINK_ALIAS
            ),
            [self::CATALOG_PRODUCT_ENTITY_ALIAS . '.' . Entity::DEFAULT_ENTITY_ID_FIELD]
        );
        $select->where(self::CATALOG_PRODUCT_SUPER_LINK_ALIAS . '.product_id IN (?)', $productIds);

        $this->joinParentVisibilityToSelectService->setTableAlias(
            'catalog_product_super_link',
            self::CATALOG_PRODUCT_SUPER_LINK_ALIAS
        );
        $select = $this->joinParentVisibilityToSelectService->execute($select, $storeId);

        if (!$includeOosParents) {
            $select->joinInner(
                [self::PARENT_STOCK_STATUS_ALIAS => $this->resourceConnection->getTableName('cataloginventory_stock_status')], // phpcs:ignore Generic.Files.LineLength.TooLong
                sprintf(
                    '%s.product_id = %s.entity_id',
                    self::PARENT_STOCK_STATUS_ALIAS,
                    self::CATALOG_PRODUCT_ENTITY_ALIAS
                ),
                []
            );
            $select->where(
                self::PARENT_STOCK_STATUS_ALIAS . '.stock_status = ?',
                StockStatus::STATUS_IN_STOCK
            );
        }

        $select->reset('columns');
        $select->columns([
            self::CATALOG_PRODUCT_ENTITY_ALIAS . '.' . Entity::DEFAULT_ENTITY_ID_FIELD,
            self::CATALOG_PRODUCT_SUPER_LINK_ALIAS . '.product_id',
            self::CATALOG_PRODUCT_SUPER_LINK_ALIAS . '.parent_id',
        ]);

        $relations = $connection->fetchAll($select);
        $this->logger->debug(
            sprintf('Product Parent Relation Select: %s', $select->__toString())
        );
        unset($connection, $select);

        return $relations;
    }
}
