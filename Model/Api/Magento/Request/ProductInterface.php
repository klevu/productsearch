<?php
/**
 * Klevu Product API interface for preserve layout
 */

namespace Klevu\Search\Model\Api\Magento\Request;

interface ProductInterface
{
    /**
     * This method executes the Klevu API request if it has not already been called, and takes the result
     * with the result we get all the item IDs, pass into our helper which returns the child and parent id's.
     * We then add all these values to our class variable $_klevu_product_ids.
     *
     * @param mixed $query
     *
     * @return array
     */
    public function _getKlevuProductIds($query);

    /**
     * This method resets the saved $_klevu_product_ids.
     * @return bool
     */
    public function reset();

    /**
     * This method will return the parent child ids
     * @return array
     */
    public function getKlevuVariantParentChildIds();
}
