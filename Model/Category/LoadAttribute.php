<?php

namespace Klevu\Search\Model\Category;

use Klevu\Search\Model\Context as Klevu_Context;
use Magento\Catalog\Model\Category as Category;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\UrlInterface;

class LoadAttribute extends MagentoCategoryActions implements LoadAttributeInterface
{
    /**
     * @var ResourceConnection
     */
    protected $_klevuSync;
    /**
     * @var ResourceConnection
     */
    protected $_stockHelper;
    /**
     * @var Category
     */
    protected Category $_catalogModelCategory;

    /**
     * @param Klevu_Context $context
     * @param Category $catalogModelCategory
     */
    public function __construct(
        Klevu_Context $context,
        Category $catalogModelCategory
    ) {
        $this->_storeModelStoreManagerInterface = $context->getStoreManagerInterface();
        $this->_frameworkModelResource = $context->getResourceConnection();
        $this->_searchHelperConfig = $context->getHelperManager()->getConfigHelper();
        $this->_searchHelperCompat = $context->getHelperManager()->getCompatHelper();
        $this->_searchHelperData = $context->getHelperManager()->getDataHelper();
        $this->_klevuSync = $context->getSync();
        $this->_stockHelper = $context->getHelperManager()->getStockHelper();
        $this->_catalogModelCategory = $catalogModelCategory;
    }

    /**
     * Add the Category Sync data to each Category in the given list. Updates the given
     * list directly to save memory.
     *
     * @param  $categories
     * An array of categories. Each element should be an array with
     * containing an element with "id" as the key and the Category ID as the value.
     *
     * @return array
     */
    public function addcategoryData(&$categories)
    {
        $categoryIds = [];
        foreach ($categories as $key => $categoryPage) {
            $categoryIds[] = $categoryPage["category_id"];
        }
        $storeId = $this->_storeModelStoreManagerInterface->getStore()->getStoreId();
        $categoryData = $this->loadCategoryCollection($storeId, $categoryIds);
        $categoryURLRewriteData = $this->getCategoryUrlRewriteData($categoryIds);
        if ($this->_searchHelperConfig->isSecureUrlEnabled(
            $this->_storeModelStoreManagerInterface->getStore()->getId()
        )) {
            $baseURL = $this->_storeModelStoreManagerInterface->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_LINK, true);
        } else {
            $baseURL = $this->_storeModelStoreManagerInterface->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_LINK);
        }
        $klevuCategoryData = [];
        foreach ($categoryData as $category) {
            $category['url'] = $baseURL . (
                (isset($categoryURLRewriteData[$category->getId()]))
                    ? $categoryURLRewriteData[$category->getId()]
                    : "catalog/category/view/id/" . $category->getId()
            );
            $value["id"] = "categoryid_" . $category->getId();
            $value["name"] = $category->getName();
            $value["desc"] = strip_tags((string)$category->getDescription());
            $value["url"] = $category['url'];
            $value["metaDesc"] = $category->getMetaDescription() . $category->getMetaKeywords();
            $value["shortDesc"] = substr(strip_tags((string)$category->getDescription()), 0, 200);
            $value["listCategory"] = "KLEVU_CATEGORY";
            $value["category"] = "Categories";
            $value["salePrice"] = 0;
            $value["currency"] = "USD";
            $value["inStock"] = "yes";
            $value["visibility"] = "search";
            $klevuCategoryData[] = $value;
        }

        return $klevuCategoryData;
    }

    /**
     * @param $storeId
     * @param $categoryIds
     *
     * @return mixed
     */
    public function loadCategoryCollection($storeId, $categoryIds)
    {
        return $this->_catalogModelCategory->getCollection()
            ->setStoreId($storeId)
            ->addAttributeToSelect("*")->addFieldToFilter(
                'entity_id',
                [
                    'in' => $categoryIds,
                ]
            );
    }
}
