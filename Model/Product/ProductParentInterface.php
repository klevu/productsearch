<?php
/**
 * ProductParentInterface
 */

namespace Klevu\Search\Model\Product;

interface ProductParentInterface
{
    /**
     * Function that is used to interface the loading of parent ids from a child  id
     * @param $id
     * @return \string[]
     */
    public function getParentIdsByChild($id);

    /**
     * Function that is used to interface the loading of child ids from a parent  id
     * @param $id
     * @return array
     */
    public function getChildrenIds($id);

    /**
     * Function that is used to interface the main class for parent manipulation
     * @return Klevu_Parent_Product_Type
     */
    public function getParentChildMapClass();

    /**
     * Function that is used to define mysql stings for parent type
     * @return array
     */
    public function getProductParentTypeArray();


}