<?php

namespace Klevu\Search\Model\Product;
use Magento\Framework\DataObject;
use Klevu\Search\Model\Context as Klevu_Context;
use \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as Klevu_Parent_Product_Type;


class ProductParent extends DataObject implements ProductParentInterface
{
    protected $_klevuParentProductType;
    protected $_context;

    public function __construct(
        Klevu_Context $context,
        Klevu_Parent_Product_Type $klevuParentProductType,
        array $data = []
    ){
        parent::__construct($data);
        $this->_klevuParentProductType = $klevuParentProductType;
        $this->_context = $context;
    }

    /**
     * Function that is used to interface the loading of parent ids from a child  id
     * @param $id
     * @return \string[]
     */
    public function getParentIdsByChild($id){
        return $this->getParentChildMapClass()->getParentIdsByChild($id);
    }

    /**
     * Function that is used to interface the loading of child ids from a parent  id
     * @param $id
     * @return array
     */
    public function getChildrenIds($id){
        return $this->getParentChildMapClass()->getChildrenIds($id);
    }

    /**
     * Function that is used to interface the main class for parent manipulation
     * @return Klevu_Parent_Product_Type
     */
    public function getParentChildMapClass()
    {
        return $this->_klevuParentProductType;
    }

    /**
     * Function that is used to define mysql stings for parent type
     * @return array
     */
    public function getProductParentTypeArray(){
        return array('configurable');
    }

}