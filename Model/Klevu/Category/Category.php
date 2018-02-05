<?php
/**
 * Category wrapper model for use in synchronisation
 */
namespace Klevu\Search\Model\Klevu\Category;

use Klevu\Search\Model\Klevu\HelperManager as KlevuHelperManager;
use Klevu\Search\Model\Klevu\KlevuFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context as Magento_Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry as Magento_Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Indexer\Category\Flat\State as Magento_Category_Flat_State;
use Exception;
use Zend\Log\Logger;

class Category extends AbstractModel implements CategoryInterface
{

    protected $_klevuHelperManager;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Klevu\Search\Model\Klevu\KlevuFactory
     */
    protected $_klevuFactory;

    /**
     * @var \Magento\Catalog\Model\Indexer\Category\Flat\State
     */
    protected $flatState;

    protected $_storeCategories = array();
    protected $_storeSourceCategories = array();
    protected $_storeNodeCategories = array();

    /**
     * Category constructor.
     * @param Magento_Context $context
     * @param Magento_Registry $registry
     * @param CategoryFactory $categoryFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param KlevuHelperManager $klevuHelperManager
     * @param KlevuFactory $klevuFactory
     * @param Magento_Category_Flat_State $flatState
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Magento_Context $context,
        Magento_Registry $registry,
        CategoryFactory $categoryFactory,
        StoreManagerInterface $storeManagerInterface,
        KlevuHelperManager $klevuHelperManager,
        KlevuFactory $klevuFactory,
        Magento_Category_Flat_State $flatState,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_klevuHelperManager = $klevuHelperManager;
        $this->_categoryFactory = $categoryFactory;
        $this->_storeManager = $storeManagerInterface;
        $this->_klevuFactory = $klevuFactory;
        $this->flatState = $flatState;
    }

    /** do Delete Category action
     * @param null $storeId
     * @return mixed
     */
    public function categoryDelete($storeId = null)
    {
        $collectionCategory = $this->getCategoryIds('delete', $storeId);
        return $collectionCategory;
    }

    /** do Update Category action
     * @param null $storeId
     * @return bool|mixed
     */
    public function categoryUpdate($storeId = null)
    {
        $collectionCategory = $this->getCategoryIds('update', $storeId);
        return $collectionCategory;
    }

    /** do Add Category action
     * @param null $storeId
     * @return bool|mixed
     */
    public function categoryAdd($storeId = null)
    {
        $collectionCategory = $this->getCategoryIds('add', $storeId);
        return $collectionCategory;
    }
    /** Main processor of getting id list from cache or db for actions
     * @param null $action
     * @param null $storeId
     * @return bool|mixed
     */
    protected function getCategoryIds($action = null, $storeId = null)
    {
        if (is_null($action)) return array();
        if (is_null($storeId)) {
            $storeId = $this->_storeManager->getStore()->getId();
        } else {
            if ($this->_storeManager->getStore()->getId() != $storeId)
                $this->_storeManager->setCurrentStore($storeId);
        }

        $categoryData = array();

        $cacheKey = sprintf('store-%d-action-%s', $storeId, $action);

        if (isset($this->_storeCategories[$cacheKey])) {
            return $this->_storeCategories[$cacheKey];
        }

        try{
            switch ($action) {
                case "delete" :
                    $categoryData = $this->getCollectionForDelete();
                    break;
                case "update" :
                    $categoryData = $this->getCollectionForUpdate();
                    break;
                case "add" :
                    $categoryData = $this->getCollectionForAdd();
                    break;
            }
        } catch (Exception $e) {
            $this->_klevuHelperManager->getDataHelper()->log(Logger::ERR, sprintf("Error in loading category for action %s - %s", $action, $e->getMessage()));
            return array();
        }


        $this->_storeCategories[$cacheKey] = $categoryData;

        return $this->_storeCategories[$cacheKey];
    }

    /** Get List of ids for Remove action
     * @return array
     */
    protected function getCollectionForDelete()
    {

        $collectionCategory = $this->getSourceCategoryIds();

        $categoryToRemove = array();
        $categoryToIgnore = array();
        $klevuToRemove = array();
        //check if we have any category
        if($collectionCategory == false) return $klevuToRemove;

        if ($collectionCategory && $collectionCategory->count() > 0) {
            foreach ($collectionCategory as $category) {
                if (!$category->getIsActive()) $categoryToRemove[$category->getId()] = $category->getId();
                if ($category->getIsExcludeCat()) $categoryToRemove[$category->getId()] = $category->getId();
                if (!isset($categoryToRemove[$category->getId()])) $categoryToIgnore[$category->getId()] = $category->getId();
            }

        }
        $storeId = $this->_storeManager->getStore()->getId();

        $klevu = $this->_klevuFactory->create();
        $klevuCollection = $klevu->getCollection()
            ->addFieldToFilter($klevu->getKlevuField('type'), $klevu->getKlevuType('category'))
            ->addFieldToFilter($klevu->getKlevuField('store_id'), $storeId);
        if (count($categoryToRemove) > 0) {
            if (count($categoryToIgnore) > 0) {
                $klevuCollection->addFieldToFilter(array(
                    'category_excluded_disabled' => $klevu->getKlevuField('category_id'),
                    'category_in_magento' => $klevu->getKlevuField('category_id')
                ), array(
                    'category_excluded_disabled' => array("in" => array_keys($categoryToRemove)), // check if we need to remove some category that was disabled/exclude
                    'category_in_magento' => array("nin" => array_keys($categoryToIgnore)) // reverse check for all category ids to see any deleted category
                ));
            } else {
                $klevuCollection->addFieldToFilter($klevu->getKlevuField('category_id'), array("in" => array_keys($categoryToRemove)));
            }
        } else {
            $klevuCollection->addFieldToFilter($klevu->getKlevuField('category_id'), array("nin" => array_keys($categoryToIgnore)));
        }

        $klevuCollection->load();

        if ($klevuCollection->count() > 0) {
            foreach ($klevuCollection as $klevuItem) {
                $klevuToRemove[$klevuItem->getData($klevu->getKlevuField('category_id'))]["category_id"] = $klevuItem->getData($klevu->getKlevuField('category_id'));
            }
        }
        return $klevuToRemove;
    }

    /** Get List of ids for Update action
     * @return array
     */
    protected function getCollectionForUpdate()
    {
        $collectionCategory = $this->getSourceCategoryIds();
        $klevuToUpdate = array();
        //check if we have any category
        if($collectionCategory == false) return $klevuToUpdate;

        $storeId = $this->_storeManager->getStore()->getId();

        $klevu = $this->_klevuFactory->create();

        $klevuCollection = $klevu->getCollection()
            ->addFieldToFilter($klevu->getKlevuField('type'), $klevu->getKlevuType('category'))
            ->addFieldToFilter($klevu->getKlevuField('store_id'), $storeId)
            ->join(
                ['category' => $collectionCategory->getResource()->getTable('catalog_category_entity')],
                "main_table." . $klevu->getKlevuField('category_id') . " = category.entity_id AND category.updated_at > main_table.last_synced_at",
                ""
            )
            ->load();

        if ($klevuCollection->count() > 0) {
            foreach ($klevuCollection as $klevuItem) {
                $klevuToUpdate[$klevuItem->getData($klevu->getKlevuField('category_id'))]["category_id"] = $klevuItem->getData($klevu->getKlevuField('category_id'));
            }
        }
        return $klevuToUpdate;
    }

    /** Get List of ids for Add action
     * @return array
     */
    protected function getCollectionForAdd()
    {
        $collectionCategory = $this->getSourceCategoryIds();
        $klevuToAdd = array();
        //check if we have any category
        if($collectionCategory == false) return $klevuToAdd;

        if ($collectionCategory && $collectionCategory->count() > 0) {
            foreach ($collectionCategory as $category) {
                if ($category->getIsActive() && !$category->getIsExcludeCat()) {
                    $klevuToAdd[$category->getId()]["category_id"] = $category->getId();
                }
            }
            $storeId = $this->_storeManager->getStore()->getId();

            $klevu = $this->_klevuFactory->create();

            $klevuCollection = $klevu->getCollection()
                ->addFieldToFilter($klevu->getKlevuField('type'), $klevu->getKlevuType('category'))
                ->addFieldToFilter($klevu->getKlevuField('store_id'), $storeId)
                ->load();

            if ($klevuCollection->count() > 0) {
                foreach ($klevuCollection as $klevuItem) {
                    if (isset($klevuToAdd[$klevuItem->getData($klevu->getKlevuField('category_id'))]))
                        unset($klevuToAdd[$klevuItem->getData($klevu->getKlevuField('category_id'))]);
                }

            }
        }
        return $klevuToAdd;
    }

    /** Get category collection for store from db or cache
     * @return array
     */
    protected function getSourceCategoryIds()
    {

        $storeId = $this->_storeManager->getStore()->getId();
        $parent = $this->_storeManager->getStore()->getRootCategoryId();


        $cacheKey = sprintf('category-%d-store-%d', $parent, $storeId);
        if (isset($this->_storeSourceCategories[$cacheKey])) {
            return $this->_storeSourceCategories[$cacheKey];
        }
        /* @var $tree \Magento\Catalog\Model\ResourceModel\Category\Tree */
        $nodes = $this->getRootNode($parent)->loadChildren(100)->getAllChildNodes();

        $nodeIds = array();
        if (count($nodes) > 0) {
            foreach ($nodes as $node) {
                $nodeIds[$node->getEntityId()] = $node->getId();
            }
            $collectionCategory = $this->_categoryFactory->create()->getCollection()
                ->addAttributeToSelect('is_active')
                ->addAttributeToSelect('is_exclude_cat')
                ->addIdFilter($nodeIds)
                ->setStoreId($storeId);
            if(!$this->flatState->isFlatEnabled()) {
                $collectionCategory->setLoadProductCount(false);
            }

            $collectionCategory->load();
        } else {
            return false;
        }

        $this->_storeSourceCategories[$cacheKey] = $collectionCategory;

        return $this->_storeSourceCategories[$cacheKey];
    }

    /** Get Root element for store from db or cache
     * @param null $parent
     * @return mixed
     */
    protected function getRootNode($parent = null)
    {

        if (isset($this->_storeNodeCategories[$parent])) {
            return $this->_storeNodeCategories[$parent];
        }
        $node = $this->_categoryFactory->create()->getTreeModel()->loadNode($parent);
        $this->_storeNodeCategories[$parent] = $node;

        return $this->_storeNodeCategories[$parent];
    }




}