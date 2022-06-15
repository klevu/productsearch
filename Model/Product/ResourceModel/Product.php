<?php

namespace Klevu\Search\Model\Product\ResourceModel;

use Klevu\Search\Service\Sync\GetBatchSize;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Eav\Model\Entity;
use Magento\Store\Api\Data\StoreInterface;

class Product
{
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

    public function __construct(
        ProductResourceModel $productResourceModel,
        OptionProvider $optionProvider,
        GetBatchSize $getBatchSize
    ) {
        $this->productResourceModel = $productResourceModel;
        $this->optionProvider = $optionProvider;
        $this->getBatchSize = $getBatchSize;
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

        unset($connection, $select);

        return array_unique(
            array_column($products, Entity::DEFAULT_ENTITY_ID_FIELD)
        );
    }

    /**
     * @param array $productIds
     *
     * @return array
     */
    public function getParentProductRelations(array $productIds)
    {
        $connection = $this->productResourceModel->getConnection();
        $select = $connection->select();
        $select->from(['l' => $connection->getTableName('catalog_product_super_link')], [])
            ->join(
                ['e' => $connection->getTableName('catalog_product_entity')],
                'e.' . $this->optionProvider->getProductEntityLinkField() . ' = l.parent_id',
                ['e.' . Entity::DEFAULT_ENTITY_ID_FIELD]
            )->where('l.product_id IN(?)', $productIds);

        $select->reset('columns');
        $select->columns(['e.' . Entity::DEFAULT_ENTITY_ID_FIELD, 'l.product_id', 'l.parent_id']);
        $relations = $connection->fetchAll($select);

        unset($connection, $select);

        return $relations;
    }
}
