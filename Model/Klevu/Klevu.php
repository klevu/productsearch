<?php
/**
 * Klevu main object model
 */
namespace Klevu\Search\Model\Klevu;

use Magento\Framework\Model\AbstractModel;

class Klevu extends AbstractModel
{
    protected $_klevuFields = array();
    protected $_klevuObjectTypes = array();

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_klevuFields = array(
            "product_id" => "product_id",
            "category_id" => "product_id",
            "parent_id" => "parent_id",
            "store_id" => "store_id",
            "last_synced_at" => "last_synced_at",
            "type" => "type"
        );
        $this->_klevuObjectTypes = array(
            "product" => "products",
            "category" => "categories",
            "page" => "pages"
        );
        $this->_init('Klevu\Search\Model\Klevu\ResourceModel\Klevu');
    }

    /** Get Klevu table field
     * @param $field
     * @return mixed
     */
    public function getKlevuField($field)
    {
        return $this->_klevuFields[$field];
    }

    /** Get Klevu type
     * @param $type
     * @return mixed
     */
    public function getKlevuType($type)
    {
        return $this->_klevuObjectTypes[$type];
    }


}