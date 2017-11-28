<?php
namespace Klevu\Search\Model\Search;

use Magento\Framework\DB\Select;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Request\FilterInterface;
use Magento\Framework\DB\Select as MysqlSelect;
use Magento\Framework\App\ObjectManager as ObjectManager;

class TableMapper extends \Magento\CatalogSearch\Model\Search\TableMapper
{
    private $klevuConfig;
    private $resource;

    /**
     * @param Select $select
     * @param RequestInterface $request
     * @return Select
     */
    public function addTables(Select $select, RequestInterface $request)
    {
        $select = parent::addTables($select,$request);
        // use ObjectManager to preserver backward compatibility of constructor function
        $objectManager = ObjectManager::getInstance();
        $this->klevuConfig = $objectManager->get('Klevu\Search\Helper\Config');
        if ($this->klevuConfig &&
            $this->klevuConfig->isLandingEnabled()==1 &&
            $this->klevuConfig->isExtensionConfigured() &&
            $request->getName() == 'quick_search_container') {
            $from = $select->getPart(MysqlSelect::FROM);
            $this->resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            if(strpos($from['search_index']['tableName'],$request->getIndex()) !== false)
                    $from['search_index']['tableName'] = $this->resource->getTableName('catalog_product_index_eav');

            $select->setPart(MysqlSelect::FROM,$from);
            //add the entity_id filter table, can be joined even if not used
            $from = $select->getPart(MysqlSelect::FROM);
            list($alias, $table, $mapOn, $mappedFields) = $this->getExtraFieldToTableMap('entity_id');
            if(!isset($from[$alias]))
                $select->joinLeft(
                    [$alias => $table],
                    $mapOn,
                    $mappedFields
                );

        }

        return $select;
    }

    /**
     * @param FilterInterface $filter
     * @return string
     */
    public function getMappingAlias(FilterInterface $filter)
    {
        //add special condition so that entity_id filter works, only used in <2.2
        if($filter->getField() !== 'entity_id') {
            $alias = parent::getMappingAlias($filter);
        } else {
            list($alias) = $this->getExtraFieldToTableMap($filter->getField());
        }
        return $alias;
    }
    /**
     * @param string $field
     * @return array|null
     */
    private function getExtraFieldToTableMap($field)
    {
        $objectManager = ObjectManager::getInstance();
        $this->resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $fieldToTableMap = [
            'entity_id' => [
                'entity_id_filter',
                $this->resource->getTableName('catalog_product_entity'),
                'search_index.entity_id = entity_id_filter.entity_id',
                []
            ]
        ];
        return array_key_exists($field, $fieldToTableMap) ? $fieldToTableMap[$field] : null;
    }
}