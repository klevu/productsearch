<?php

namespace Klevu\Search\Api;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;

interface MagentoProductSyncRepositoryInterface
{
    const NOT_VISIBLE_EXCLUDED = 0;
    const NOT_VISIBLE_INCLUDED = 1;
    const NOT_VISIBLE_ONLY = 2;

    /**
     * @param StoreInterface $store
     * @param int|null $visibility
     * @param bool $includeOosProducts
     *
     * @return ProductCollection
     */
    public function getProductIdsCollection(
        StoreInterface $store,
        $visibility = self::NOT_VISIBLE_EXCLUDED,
        $includeOosProducts = true
    );

    /**
     * @param StoreInterface $store
     * @param int|null $parentVisibility
     * @param bool $includeOosProducts
     *
     * @return ProductCollection
     */
    public function getChildProductIdsCollection(
        StoreInterface $store,
        $parentVisibility = self::NOT_VISIBLE_EXCLUDED,
        $includeOosProducts = true
    );

    /**
     * @param StoreInterface $store
     * @param bool $includeOosProducts
     *
     * @return array
     */
    public function getParentProductIds(StoreInterface $store, $includeOosProducts = true);

    /**
     * @param StoreInterface $store
     *
     * @return int
     */
    public function getMaxProductId(StoreInterface $store);

    /**
     * @param string[] $productIds
     * @param int $storeId
     * @param bool $includeOosParents
     *
     * @return array[]
     */
    public function getParentProductRelations(
        $productIds,
        $storeId = Store::DEFAULT_STORE_ID,
        $includeOosParents = true
    );

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
    );
}
