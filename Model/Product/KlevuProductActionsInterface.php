<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 9/18/2018
 * Time: 7:06 PM
 */

namespace Klevu\Search\Model\Product;

interface KlevuProductActionsInterface
{
    /**
     * Setup an API session for the given store. Sets the store and session ID on self. Returns
     * true on success or false if Product Sync is disabled, store is not configured or the
     * session API call fails.
     *
     * @param \Magento\Store\Model\Store $store
     *
     * @return bool
     */
    public function setupSession($store);

    /**
     * Delete success processing , separated for easier override
     */
    public function executeDeleteProductsSuccess(array $data, $response);

    /**
     * Build the delete SQL , separated for easier override
     */
    public function getDeleteProductsSuccessSql(array $data, $skipped_record_ids);

    /**
     * Update success processing , separated for easier override
     */
    public function executeUpdateProductsSuccess(array $data, $response);

    /**
     * Add success processing , separated for easier override
     */
    public function executeAddProductsSuccess(array $data, $response);
}