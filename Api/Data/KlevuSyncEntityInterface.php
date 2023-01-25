<?php

namespace Klevu\Search\Api\Data;

interface KlevuSyncEntityInterface
{
    /** Get Klevu table field
     *
     * @param string $field
     *
     * @return string
     */
    public function getKlevuField($field);

    /** Get Klevu type
     *
     * @param string $type
     *
     * @return string
     */
    public function getKlevuType($type);

    /**
     * @return array|string[]
     */
    public function getTypes();

    /**
     * @return int
     */
    public function getProductId();

    /**
     * @param int $productId
     *
     * @return void
     */
    public function setProductId($productId);

    /**
     * @return int|null
     */
    public function getParentId();

    /**
     * @param int $parentId
     *
     * @return void
     */
    public function setParentId($parentId);

    /**
     * @return int
     */
    public function getStoreId();

    /**
     * @param int $storeId
     *
     * @return void
     */
    public function setStoreId($storeId);

    /**
     * @return string|null
     */
    public function getLastSyncedAt();

    /**
     * @param int $timestamp
     *
     * @return void
     */
    public function setLastSyncedAt($timestamp);

    /**
     * @return string
     */
    public function getType();

    /**
     * @param int $type
     *
     * @return void
     */
    public function setType($type);
}
