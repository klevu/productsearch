<?php

namespace Klevu\Search\Model\Product\ResourceModel\Product;

use Klevu\Search\Service\Sync\GetBatchSize;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Model\ResourceModel\Stock\StatusFactory as StockStatusFactory;
use Magento\Eav\Model\Entity;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection as MagentoCollection;
use Magento\Framework\DB\Select as DbSelect;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;

class Collection
{
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var GetBatchSize
     */
    private $getBatchSize;
    /**
     * @var StockStatusFactory
     */
    private $stockStatusFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param GetBatchSize $getBatchSize
     * @param StockStatusFactory|null $stockStatusFactory
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        GetBatchSize $getBatchSize,
        StockStatusFactory $stockStatusFactory = null,
        LoggerInterface $logger = null
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->getBatchSize = $getBatchSize;
        $objectManager = ObjectManager::getInstance();
        $this->stockStatusFactory = $stockStatusFactory
            ?: $objectManager->get(StockStatusFactory::class);
        $this->logger = $logger
            ?: $objectManager->get(LoggerInterface::class);
    }

    /**
     * @param StoreInterface $store
     * @param array $productTypeArray
     * @param array $visibility
     * @param bool $includeOosProducts
     *
     * @return ProductCollection
     */
    public function initCollectionByType(
        StoreInterface $store,
        array $productTypeArray,
        array $visibility,
        $includeOosProducts = true
    ) {
        $batchSize = $this->getBatchSize->execute($store);

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToSelect(Entity::DEFAULT_ENTITY_ID_FIELD);
        $productCollection->addStoreFilter($store->getId());
        if ($productTypeArray) {
            $productCollection->addAttributeToFilter(ProductInterface::TYPE_ID, ['in' => $productTypeArray]);
        }
        $productCollection->addAttributeToFilter(ProductInterface::STATUS, ['eq' => Status::STATUS_ENABLED]);
        if ($visibility) {
            $productCollection->addAttributeToFilter(ProductInterface::VISIBILITY, ['in' => $visibility]);
        }
        $productCollection->getSelect()->reset(DbSelect::ORDER);
        $productCollection->addAttributeToSort(Entity::DEFAULT_ENTITY_ID_FIELD, MagentoCollection::SORT_ORDER_ASC);
        $productCollection->setPageSize($batchSize);

        $stockStatusResource = $this->stockStatusFactory->create();
        $stockStatusResource->addStockDataToCollection($productCollection, !$includeOosProducts);
        $productCollection->setFlag('has_stock_status_filter', true);

        $this->logger->debug(
            sprintf(
                'Product Collection Select: %s : %s',
                __METHOD__,
                $productCollection->getSelect()->__toString()
            )
        );

        return $productCollection;
    }

    /**
     * @param StoreInterface $store
     * @param bool $includeOosProducts
     *
     * @return int
     */
    public function getMaxProductId(StoreInterface $store, $includeOosProducts = true)
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addStoreFilter($store->getId());
        if ($includeOosProducts) {
            $productCollection->setFlag('has_stock_status_filter', true);
        }

        $select = $productCollection->getSelect();
        $select->reset(DbSelect::COLUMNS);
        $select->columns(Entity::DEFAULT_ENTITY_ID_FIELD);
        $select->reset(DbSelect::ORDER);
        $select->order(Entity::DEFAULT_ENTITY_ID_FIELD . ' ' . MagentoCollection::SORT_ORDER_DESC);
        $select->limit(1);

        $connection = $productCollection->getConnection();
        $maxProductId = $connection->fetchOne($select);
        $return = $maxProductId ? (int)$maxProductId : 0;
        $this->logger->debug(
            sprintf(
                'Max Product ID Select: %s : %s',
                __METHOD__,
                $productCollection->getSelect()->__toString()
            )
        );
        $this->logger->debug(
            sprintf(
                'Max Product ID: %s : %s',
                __METHOD__,
                $return
            )
        );

        return $return;
    }
}
