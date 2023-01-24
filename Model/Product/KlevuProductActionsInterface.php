<?php

namespace Klevu\Search\Model\Product;

use Klevu\Search\Model\Api\Response;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;

interface KlevuProductActionsInterface
{
    /**
     * Setup an API session for the given store. Sets the store and session ID on self. Returns
     * true on success or false if Product Sync is disabled, store is not configured or the
     * session API call fails.
     *
     * @param StoreInterface $store
     *
     * @return bool
     */
    public function setupSession($store);

    /**
     * Delete success processing , separated for easier override
     *
     * @param array $data
     * @param Response $response
     *
     * @return bool|string
     * @throws NoSuchEntityException
     */
    public function executeDeleteProductsSuccess(array $data, $response);

    /**
     * Build the delete SQL , separated for easier override
     *
     * @param array $data
     * @param array $skipped_record_ids
     *
     * @return Select
     * @throws NoSuchEntityException
     */
    public function getDeleteProductsSuccessSql(array $data, array $skipped_record_ids);

    /**
     * Update success processing , separated for easier override
     *
     * @param array $data
     * @param Response $response
     */
    public function executeUpdateProductsSuccess(array $data, $response);

    /**
     * Add success processing , separated for easier override
     *
     * @param array $data
     * @param Response $response
     *
     * @return bool|string
     * @throws NoSuchEntityException
     */
    public function executeAddProductsSuccess(array $data, $response);
}
