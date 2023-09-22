<?php

namespace Klevu\Search\Model\Product\ResourceModel;

use Klevu\Search\Api\Service\Catalog\Product\JoinParentStatusToSelectInterface;
use Klevu\Search\Api\Service\Catalog\Product\JoinParentStockToSelectInterface;
use Klevu\Search\Api\Service\Catalog\Product\JoinParentVisibilityToSelectInterface;
use Klevu\Search\Repository\MagentoProductSyncRepository;
use Klevu\Search\Service\Sync\GetBatchSize;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
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
    /**
     * @deprecated duplicate
     * @see MagentoProductSyncRepository::CATALOG_PRODUCT_ENTITY_ALIAS
     */
    const CATALOG_PRODUCT_ENTITY_ALIAS = MagentoProductSyncRepository::CATALOG_PRODUCT_ENTITY_ALIAS;
    /**
     * @deprecated duplicate
     * @see MagentoProductSyncRepository::CATALOG_PRODUCT_SUPER_LINK_ALIAS
     */
    const CATALOG_PRODUCT_SUPER_LINK_ALIAS = MagentoProductSyncRepository::CATALOG_PRODUCT_SUPER_LINK_ALIAS;
    /**
     * @deprecated duplicate
     * @see MagentoProductSyncRepository::PARENT_STOCK_STATUS_ALIAS
     */
    const PARENT_STOCK_STATUS_ALIAS = MagentoProductSyncRepository::PARENT_STOCK_STATUS_ALIAS;

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
     * @var JoinParentStockToSelectInterface
     */
    private $joinParentStockToSelect;
    /**
     * @var JoinParentStatusToSelectInterface
     */
    private $joinParentStatusToSelect;

    /**
     * @param ProductResourceModel $productResourceModel
     * @param OptionProvider $optionProvider
     * @param GetBatchSize $getBatchSize
     * @param ResourceConnection|null $resourceConnection
     * @param JoinParentVisibilityToSelectInterface|null $joinParentVisibilityToSelectService
     * @param LoggerInterface|null $logger
     * @param JoinParentStockToSelectInterface|null $joinParentStockToSelect
     * @param JoinParentStatusToSelectInterface|null $joinParentStatusToSelect
     */
    public function __construct(
        ProductResourceModel $productResourceModel,
        OptionProvider $optionProvider,
        GetBatchSize $getBatchSize,
        ResourceConnection $resourceConnection = null,
        JoinParentVisibilityToSelectInterface $joinParentVisibilityToSelectService = null,
        LoggerInterface $logger = null,
        JoinParentStockToSelectInterface $joinParentStockToSelect = null,
        JoinParentStatusToSelectInterface $joinParentStatusToSelect = null
    ) {
        $this->productResourceModel = $productResourceModel;
        $this->optionProvider = $optionProvider;
        $this->getBatchSize = $getBatchSize;
        $objectManager = ObjectManager::getInstance();
        $this->resourceConnection = $resourceConnection ?: $objectManager->get(ResourceConnection::class);
        $this->joinParentVisibilityToSelectService = $joinParentVisibilityToSelectService
            ?: $objectManager->get(JoinParentVisibilityToSelectInterface::class);
        $this->logger = $logger
            ?: $objectManager->get(LoggerInterface::class);
        $this->joinParentStockToSelect = $joinParentStockToSelect
            ?: $objectManager->get(JoinParentStockToSelectInterface::class);
        $this->joinParentStatusToSelect = $joinParentStatusToSelect
            ?: $objectManager->get(JoinParentStatusToSelectInterface::class);
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
        $productLinkTable = $this->resourceConnection->getTableName('catalog_product_super_link');
        $productEntityTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $select->from(
            [MagentoProductSyncRepository::CATALOG_PRODUCT_SUPER_LINK_ALIAS => $productLinkTable],
            []
        );
        $select->join(
            [MagentoProductSyncRepository::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS => $productEntityTable],
            sprintf(
                '%s.%s = %s.parent_id',
                MagentoProductSyncRepository::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS,
                $connection->quoteIdentifier($this->optionProvider->getProductEntityLinkField()),
                MagentoProductSyncRepository::CATALOG_PRODUCT_SUPER_LINK_ALIAS
            ),
            [MagentoProductSyncRepository::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS . '.' . Entity::DEFAULT_ENTITY_ID_FIELD]
        );
        $select->where(
            MagentoProductSyncRepository::CATALOG_PRODUCT_SUPER_LINK_ALIAS . '.product_id IN (?)',
            $productIds
        );

        $select = $this->joinParentStatusToSelect->execute($select, $storeId);

        $this->joinParentVisibilityToSelectService->setTableAlias(
            'catalog_product_super_link',
            MagentoProductSyncRepository::CATALOG_PRODUCT_SUPER_LINK_ALIAS
        );
        $select = $this->joinParentVisibilityToSelectService->execute($select, $storeId);

        $this->joinParentStockToSelect->execute(
            $select,
            (int)$storeId,
            $includeOosParents,
            false,
            false
        );

        $select->reset('columns');
        $select->columns([
            MagentoProductSyncRepository::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS . '.' . Entity::DEFAULT_ENTITY_ID_FIELD,
            MagentoProductSyncRepository::CATALOG_PRODUCT_SUPER_LINK_ALIAS . '.product_id',
            MagentoProductSyncRepository::CATALOG_PRODUCT_SUPER_LINK_ALIAS . '.parent_id',
        ]);

        $relations = $connection->fetchAll($select);
        $this->logger->debug(
            sprintf('Product Parent Relation Select: %s', $select->__toString())
        );
        unset($connection, $select);

        return $relations;
    }
}
