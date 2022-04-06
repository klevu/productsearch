<?php
/**
 * Klevu main object model
 */

namespace Klevu\Search\Model\Klevu;

use Klevu\Search\Model\Klevu\ResourceModel\Klevu as KlevuResourceModel;
use Magento\Framework\Model\AbstractModel;

class Klevu extends AbstractModel
{
    const FIELD_ENTITY_ID = "row_id";
    const FIELD_PRODUCT_ID = "product_id";
    const FIELD_CATEGORY_ID = "product_id";
    const FIELD_PARENT_ID = "parent_id";
    const FIELD_STORE_ID = "store_id";
    const FIELD_LAST_SYNCED_AT = "last_synced_at";
    const FIELD_TYPE = "type";

    const OBJECT_TYPE_PRODUCT = "products";
    const OBJECT_TYPE_CATEGORY = "categories";
    const OBJECT_TYPE_PAGE = "pages";

    /**
     * @var array
     */
    protected $_klevuFields = [
        "row_id" => self::FIELD_ENTITY_ID,
        "product_id" => self::FIELD_PRODUCT_ID,
        "category_id" => self::FIELD_CATEGORY_ID,
        "parent_id" => self::FIELD_PARENT_ID,
        "store_id" => self::FIELD_STORE_ID,
        "last_synced_at" => self::FIELD_LAST_SYNCED_AT,
        "type" => self::FIELD_TYPE
    ];
    /**
     * @var array
     */
    protected $_klevuObjectTypes = [
        "product" => self::OBJECT_TYPE_PRODUCT,
        "category" => self::OBJECT_TYPE_CATEGORY,
        "page" => self::OBJECT_TYPE_PAGE
    ];

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init(KlevuResourceModel::class);
    }

    /** Get Klevu table field
     *
     * @param $field
     *
     * @return string
     */
    public function getKlevuField($field)
    {
        return $this->_klevuFields[$field];
    }

    /** Get Klevu type
     *
     * @param $type
     *
     * @return string
     */
    public function getKlevuType($type)
    {
        return $this->_klevuObjectTypes[$type];
    }

    /**
     * @return array|string[]
     */
    public function getTypes()
    {
        return $this->_klevuObjectTypes;
    }
}
