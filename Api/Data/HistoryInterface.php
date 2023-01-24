<?php

namespace Klevu\Search\Api\Data;

interface HistoryInterface
{
    /**
     * @return int|null
     */
    public function getProductId();

    /**
     * @param int $productId
     *
     * @return void
     */
    public function setProductId($productId);

    /**
     * @return int
     */
    public function getParentId();

    /**
     * @param int|null $parentId
     *
     * @return void
     */
    public function setParentId($parentId);

    /**
     * @return int|null
     */
    public function getStoreId();

    /**
     * @param int $storeId
     *
     * @return void
     */
    public function setStoreId($storeId);

    /**
     * @return int|null
     */
    public function getAction();

    /**
     * @param int $action
     *
     * @return void
     */
    public function setAction($action);

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param string $message
     *
     * @return void
     */
    public function setMessage($message);

    /**
     * @return bool
     */
    public function getSuccess();

    /**
     * @param bool $success
     *
     * @return void
     */
    public function setSuccess($success);

    /**
     * @return string|null
     */
    public function getSyncedAt();
}
