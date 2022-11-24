<?php

namespace Klevu\Search\Model\Product;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;

interface MagentoProductActionsInterface
{
    /**
     * @param StoreInterface $store
     *
     * @return mixed
     */
    public function updateProductCollection($store = null);

    /**
     * @param StoreInterface $store
     *
     * @return mixed
     */
    public function addProductCollection($store = null);

    /**
     * @param StoreInterface $store
     *
     * @return mixed
     */
    public function deleteProductCollection($store = null);

    /**
     * @param StoreInterface $store
     * @param array|null $productIds
     * @param int|null $lastEntityId
     *
     * @return mixed
     */
    public function getKlevuProductCollection($store = null, $productIds = [], $lastEntityId = null);

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
    public function deleteProducts(array $data);

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
    public function updateProducts(array $data);

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
    public function addProducts(array $data);

    /**
     * Mark all products to be updated the next time Product Sync runs.
     *
     * @param Store|int $store If passed, will only update products for the given store.
     *
     * @return $this
     */
    public function markAllProductsForUpdate($store = null);

    /**
     * Forget the sync status of all the products for the given Store and test mode.
     * If no store or test mode status is given, clear products for all stores and modes respectively.
     *
     * @param Store|int|null $store
     *
     * @return int
     */
    public function clearAllProducts($store = null);

    /**
     * Run cron externally for debug using js api
     *
     * @param string $restApi
     *
     * @return $this
     */
    public function sheduleCronExteranally($restApi);

    /**
     * Get special price expire date attribute value
     *
     * @return array
     */
    public function getExpiryDateAttributeId();

    /**
     * Get prodcuts ids which have expiry date gone and update next day
     *
     * @return array
     */
    public function getExpirySaleProductsIds();

    /**
     * if special to price date expire then make that product for update
     *
     * @return $this
     */
    public function markProductForUpdate();

    /**
     * Mark product ids for update
     *
     * @param array $ids
     *
     * @return void
     */
    public function updateSpecificProductIds($ids);

    /**
     * Update all product ids rating attribute
     *
     * @param string $store
     *
     * @return $this
     */
    public function updateProductsRating($store);

    /**
     * Mark products for update if rule is expire
     *
     * @return void
     */
    public function catalogruleUpdateinfo();

    /**
     * Mark records for update storewise
     *
     * @param int|array $productIds
     * @param string $recordType
     * @param array|string|int|null $stores
     *
     * @return void
     */
    public function markRecordIntoQueue($productIds, $recordType, $stores);

    /**
     * Returns parent relations data by child ids
     *
     * @param array $ids
     * @param int $storeId
     * @param bool $includeOosParents
     *
     * @return array
     */
    public function getParentRelationsByChild($ids, $storeId = Store::DEFAULT_STORE_ID, $includeOosParents = true);

    /**
     * @param array|string|int|null $stores
     *
     * @return void
     */
    public function markCategoryRecordIntoQueue($stores = null);
}
