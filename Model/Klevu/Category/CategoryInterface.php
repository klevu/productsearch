<?php
/**
 * Category wrapper interface for use in synchronization
 */
namespace Klevu\Search\Model\Klevu\Category;

interface CategoryInterface
{
    /**
     * @param mixed $storeId
     *
     * @return mixed
     */
    public function categoryDelete($storeId = null);

    /**
     * @param mixed $storeId
     *
     * @return mixed
     */
    public function categoryUpdate($storeId = null);

    /**
     * @param mixed $storeId
     *
     * @return mixed
     */
    public function categoryAdd($storeId = null);
}
