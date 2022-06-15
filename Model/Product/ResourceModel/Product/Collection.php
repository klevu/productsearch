<?php

namespace Klevu\Search\Model\Product\ResourceModel\Product;

use Klevu\Search\Service\Sync\GetBatchSize;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Model\Entity;
use Magento\Framework\Data\Collection as MagentoCollection;
use Magento\Store\Api\Data\StoreInterface;

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

    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        GetBatchSize $getBatchSize
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->getBatchSize = $getBatchSize;
    }

    /**
     * @param StoreInterface $store
     * @param array $productTypeArray
     * @param array $visibility
     *
     * @return ProductCollection
     */
    public function initCollectionByType(
        StoreInterface $store,
        array $productTypeArray,
        array $visibility
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
        $productCollection->addAttributeToSort(Entity::DEFAULT_ENTITY_ID_FIELD, MagentoCollection::SORT_ORDER_ASC);
        $productCollection->setPageSize($batchSize);
        $productCollection->setFlag('has_stock_status_filter', true);

        return $productCollection;
    }

    /**
     * @param StoreInterface $store
     *
     * @return int
     */
    public function getMaxProductId(StoreInterface $store)
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addStoreFilter($store->getId());
        $productCollection->addAttributeToSort(Entity::DEFAULT_ENTITY_ID_FIELD, MagentoCollection::SORT_ORDER_DESC);
        $productCollection->setPageSize(1);
        $productCollection->setFlag('has_stock_status_filter', true);

        $firstItem = $productCollection->getFirstItem();

        return $firstItem ? (int)$firstItem->getData(Entity::DEFAULT_ENTITY_ID_FIELD) : 0;
    }
}
