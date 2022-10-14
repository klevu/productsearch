<?php

namespace Klevu\Search\Provider\Catalog\Product\Review;

use Klevu\Search\Api\Provider\Catalog\Product\Review\ProductsWithRatingAttributeDataProviderInterface;
use Klevu\Search\Model\Attribute\Rating;
use Klevu\Search\Model\Attribute\ReviewCount;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

class MagentoProductsWithRatingAttributeDataProvider implements ProductsWithRatingAttributeDataProviderInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var AbstractAttribute[]
     */
    private $entityAttributes = [];

    /**
     * @param LoggerInterface $logger
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @api
     * @return int[]
     */
    public function getProductIdsForAllStores()
    {
        return $this->getProductIds(null);
    }

    /**
     * @api
     * @param int $storeId
     * @return int[]
     */
    public function getProductIdsForStore($storeId)
    {
        if (!is_int($storeId) && !ctype_digit($storeId)) {
            throw new \InvalidArgumentException(sprintf(
                'storeId parameter must be integer; %s received',
                is_object($storeId) ? get_class($storeId) : gettype($storeId) // phpcs:ignore
            ));
        }

        return $this->getProductIds((int)$storeId);
    }

    /**
     * @param int|null $storeId
     * @return int[]
     */
    private function getProductIds($storeId = null)
    {
        $productCollection = $this->productCollectionFactory->create();
        try {
            $productEntity = $productCollection->getEntity();
        } catch (LocalizedException $e) {
            $this->logger->error(
                'Error retrieving existing product ids with review attribute data: ' . $e->getMessage(),
                [
                    'method' => __METHOD__,
                    'storeId' => $storeId,
                ]
            );

            return [];
        }

        if (null !== $storeId) {
            $productCollection->addStoreFilter($storeId);
        }

        $connection = $productCollection->getConnection();
        $select = $productCollection->getSelect();

        $select->reset(Select::COLUMNS);
        $select->columns(['product_id' => $productEntity->getEntityIdField()]);

        $whereParts = [];
        $ratingJoined = $this->joinAttribute(
            $connection,
            $select,
            $productEntity,
            Rating::ATTRIBUTE_CODE,
            $storeId
        );
        if ($ratingJoined) {
            $ratingTableAlias = $this->getTableAlias($connection, Rating::ATTRIBUTE_CODE);
            $whereParts[] = sprintf(
                '(%s.`value` IS NOT NULL AND %s.`value` != "")',
                $connection->quoteIdentifier($ratingTableAlias),
                $connection->quoteIdentifier($ratingTableAlias)
            );
        }

        $reviewCountJoined = $this->joinAttribute(
            $connection,
            $select,
            $productEntity,
            ReviewCount::ATTRIBUTE_CODE,
            $storeId
        );
        if ($reviewCountJoined) {
            $reviewCountTableAlias = $this->getTableAlias($connection, ReviewCount::ATTRIBUTE_CODE);
            $whereParts[] = sprintf(
                '(%s.`value` IS NOT NULL AND %s.`value` != "")',
                $connection->quoteIdentifier($reviewCountTableAlias),
                $connection->quoteIdentifier($reviewCountTableAlias)
            );
        }

        if (!$ratingJoined && !$reviewCountJoined) {
            $this->logger->warning(
                'Cannot retrieve existing product ids with review data attributes: Could not apply any attributes',
                [
                    'attribute_codes' => [
                        Rating::ATTRIBUTE_CODE,
                        ReviewCount::ATTRIBUTE_CODE,
                    ],
                ]
            );

            return [];
        }

        $select->where(implode(' OR ', $whereParts));

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * @param string $attributeCode
     * @param AbstractEntity $productEntity
     *
     * @return AbstractAttribute|null
     * @throws LocalizedException
     */
    private function getEntityAttribute($attributeCode, AbstractEntity $productEntity)
    {
        if (!array_key_exists($attributeCode, $this->entityAttributes)) {
            $this->entityAttributes[$attributeCode] = $productEntity->getAttribute($attributeCode) ?: null;
        }

        return $this->entityAttributes[$attributeCode];
    }

    /**
     * @param AdapterInterface $connection
     * @param string $attributeCode
     * @return string
     */
    private function getTableAlias(AdapterInterface $connection, $attributeCode)
    {
        return $connection->getTableName(ProductCollection::ATTRIBUTE_TABLE_ALIAS_PREFIX . $attributeCode);
    }

    /**
     * @param AdapterInterface $connection
     * @param Select $select
     * @param AbstractEntity $productEntity
     * @param string $attributeCode
     * @param int|null $storeId
     * @return bool
     */
    private function joinAttribute(
        AdapterInterface $connection,
        Select $select,
        AbstractEntity $productEntity,
        $attributeCode,
        $storeId
    ) {
        try {
            $attribute = $this->getEntityAttribute($attributeCode, $productEntity);
        } catch (LocalizedException $e) {
            $this->logger->error(
                'Error retrieving product attribute: ' . $e->getMessage(),
                [
                    'method' => __METHOD__,
                    'attributeCode' => $attributeCode,
                    'storeId' => $storeId,
                ]
            );

            return false;
        }
        if (!$attribute) {
            $this->logger->warning(sprintf(
                'Klevu attribute [%s] not found in installation',
                Rating::ATTRIBUTE_CODE
            ));

            return false;
        }

        $tableAlias = $this->getTableAlias($connection, $attributeCode);

        $linkField = $productEntity->getLinkField();
        $select->joinLeft(
            [$tableAlias => $attribute->getBackendTable()],
            $this->getJoinClause(
                $connection,
                $tableAlias,
                $attribute,
                $linkField,
                $storeId
            ),
            [$attributeCode => $tableAlias . '.value']
        );

        return true;
    }

    /**
     * @param AdapterInterface $connection
     * @param string $tableAlias
     * @param AbstractAttribute $attribute
     * @param string $linkField
     * @param int|null $storeId
     * @return string
     */
    private function getJoinClause(
        AdapterInterface $connection,
        $tableAlias,
        AbstractAttribute $attribute,
        $linkField,
        $storeId
    ) {
        $joinClause = sprintf(
            '%s.attribute_id = %s AND %s.%s = e.%s',
            $tableAlias,
            $connection->quote((int)$attribute->getId()),
            $tableAlias,
            $linkField,
            $linkField,
            $tableAlias
        );
        if (null !== $storeId) {
            $joinClause .= sprintf(
                ' AND %s.store_id = %s',
                $tableAlias,
                $connection->quote($storeId)
            );
        } else {
            $joinClause .= sprintf(
                ' AND %s.store_id != %s',
                $tableAlias,
                $connection->quote(Store::DEFAULT_STORE_ID)
            );
        }

        return $joinClause;
    }
}
