<?php
/**
 * Category wrapper interface for use in synchronisation
 */
namespace Klevu\Search\Model\Klevu\Category;


interface CategoryInterface
{
    /** do Delete Category action
     * @param null $storeId
     * @return mixed
     */
    public function categoryDelete($storeId = null);

    /** do Update Category action
     * @param null $storeId
     * @return bool|mixed
     */
    public function categoryUpdate($storeId = null);

    /** do Add Category action
     * @param null $storeId
     * @return bool|mixed
     */
    public function categoryAdd($storeId = null);
}