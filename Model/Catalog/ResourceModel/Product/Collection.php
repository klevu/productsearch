<?php

namespace Klevu\Search\Model\Catalog\ResourceModel\Product;

use Klevu\Search\Model\Catalog\Product as KlevuProductModel;
use Klevu\Search\Model\Klevu\Klevu;
use Magento\Catalog\Model\ResourceModel\Product as MagentoProductResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection as MagentoProductCollection;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;

class Collection extends MagentoProductCollection
{
    /**
     * Initialize resources
     *
     * @return void
     *
     * Only to be used in sync grid to avoid issue with multiple rows with the same entity_id
     */
    protected function _construct()
    {
        $this->_init(
            KlevuProductModel::class,
            MagentoProductResourceModel::class
        );
        $this->_initTables();
    }

    /**
     * Retrieve item id
     *
     * @param DataObject $item
     *
     * @return string|int
     */
    protected function _getItemId(DataObject $item)
    {
        return $item->getId();
    }

    /**
     * Initialize entity object property value
     *
     * Parameter $valueInfo is _getLoadAttributesSelect fetch result row
     *
     * @param array $valueInfo
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _setItemAttributeValue($valueInfo)
    {
        $entityIdField = $this->getEntity()->getEntityIdField();
        $entityId = $valueInfo[$entityIdField];
        $items = $this->getItemsByEntityId($entityId);
        if (!count($items)) {
            throw new LocalizedException(
                __('A header row is missing for an attribute. Verify the header row and try again.')
            );
        }
        $attributeCode = array_search($valueInfo['attribute_id'], $this->_selectAttributes);
        if (!$attributeCode) {
            $attribute = $this->_eavConfig->getAttribute(
                $this->getEntity()->getType(),
                $valueInfo['attribute_id']
            );
            $attributeCode = $attribute->getAttributeCode();
        }

        foreach ($items as $object) {
            if (is_array($object)) {
                $key = array_keys($object);
                $object = $object[$key[0]];
            }
            $object->setData($attributeCode, $valueInfo['value']);
        }

        return $this;
    }

    /**
     * @param string $entityId
     *
     * @return array
     */
    private function getItemsByEntityId($entityId)
    {
        return array_filter($this->_itemsById, static function ($key) use ($entityId) {
            return strpos($key, $entityId . '-') === 0;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @return Select
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();

        $countSelect->reset(Select::COLUMNS);
        $countSelect->columns(
            new \Zend_Db_Expr(sprintf(
                'COUNT(DISTINCT CONCAT(IFNULL(e.%s, "0"), "-", IFNULL(e.%s, "0"), "-", IFNULL(e.%s, "0")))',
                Klevu::FIELD_PRODUCT_PARENT_ID,
                $this->getIdFieldName(),
                Klevu::FIELD_ALIAS_ENTITY_ID
            ))
        );

        return $countSelect;
    }

    /**
     * @param string $attribute
     * @param string $dir
     * @return $this
     */
    public function addAttributeToSort($attribute, $dir = MagentoProductCollection::SORT_ORDER_ASC)
    {
        switch ($attribute) {
            case 'name':
            case 'last_synced_at':
                $select = $this->getSelect();
                $select->order('e.' . $attribute . ' ' . $dir);
                $return = $this;
                break;

            default:
                $return = parent::addAttributeToSort($attribute, $dir);
                break;
        }

        return $return;
    }

    /**
     * @param AbstractAttribute|string|array $attribute
     * @param array $condition
     * @param string $joinType
     * @return $this
     */
    public function addAttributeToFilter($attribute, $condition = null, $joinType = 'inner')
    {
        switch ($attribute) {
            case 'name':
            case 'visibility':
            case 'status':
                $select = $this->getSelect();
                $select->where(
                    $this->_getConditionSql($this->getConnection()->quoteIdentifier('e.' . $attribute), $condition)
                );
                $return = $this;
                break;

            default:
                $return = parent::addAttributeToFilter($attribute, $condition, $joinType);
                break;
        }

        return $return;
    }
}
