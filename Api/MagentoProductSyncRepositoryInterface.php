<?php

namespace Klevu\Search\Api;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Api\Data\StoreInterface;

interface MagentoProductSyncRepositoryInterface
{
    const NOT_VISIBLE_EXCLUDED = 0;
    const NOT_VISIBLE_INCLUDED = 1;
    const NOT_VISIBLE_ONLY = 2;

    /**
     * @param StoreInterface $store
     * @param int|null $visibility
     *
     * @return ProductCollection
     */
    public function getProductIdsCollection(StoreInterface $store, $visibility = self::NOT_VISIBLE_EXCLUDED);

    /**
     * @param StoreInterface $store
     *
     * @return ProductCollection
     */
    public function getChildProductIdsCollection(StoreInterface $store);

    /**
     * @param StoreInterface $store
     *
     * @return array
     */
    public function getParentProductIds(StoreInterface $store);

    /**
     * @param StoreInterface $store
     *
     * @return mixed
     */
    public function getMaxProductId(StoreInterface $store);

    /**
     * @param $productIds
     *
     * @return mixed
     */
    public function getParentProductRelations($productIds);

    /**
     * @param ProductCollection $productCollection
     * @param StoreInterface $store
     * @param array|null $productIds
     * @param int|null $lastEntityId
     *
     * @return mixed
     */
    public function getBatchDataForCollection(ProductCollection $productCollection, StoreInterface $store, $productIds = [], $lastEntityId = null);

}
