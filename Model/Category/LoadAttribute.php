<?php
/**
 * Class \Klevu\Search\Model\Product\MagentoProductActionsInterface
 */
namespace Klevu\Search\Model\Category;
use \Magento\Framework\Model\AbstractModel as AbstractModel;
use \Magento\Catalog\Model\Category as Category;
use \Klevu\Search\Model\Context as Klevu_Context;


class LoadAttribute extends  \Klevu\Search\Model\Category\MagentoCategoryActions implements LoadAttributeInterface
{

    public function __construct(
        Klevu_Context $context,
		Category $catalogModelCategory
    ){
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
     * @param array $categories An array of categories. Each element should be an array with
     *                        containing an element with "id" as the key and the Category
     *                        ID as the value.
     *
     * @return $this
     */
    public function addcategoryData(&$pages)
    {
        $category_ids = [];
        foreach ($pages as $key => $value) {
            $category_ids[] = $value["category_id"];
        }
        $storeId = $this->_storeModelStoreManagerInterface->getStore()->getStoreId();
        $category_data = $this->loadCategoryCollection($storeId,$category_ids);
        $category_url_rewrite_data = $this->getCategoryUrlRewriteData($category_ids);
        if ($this->_searchHelperConfig->isSecureUrlEnabled($this->_storeModelStoreManagerInterface->getStore()->getId())) {
            $base_url = $this->_storeModelStoreManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true);
        } else {
            $base_url = $this->_storeModelStoreManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);
        }
        $category_data_new = [];
        foreach ($category_data as $category) {
            $category['url'] = $base_url . (
                (isset($category_url_rewrite_data[$category->getId()])) ?
                    $category_url_rewrite_data[$category->getId()] :
                    "catalog/category/view/id/" . $category->getId()
                );
            $value["id"] = "categoryid_" . $category->getId();
            $value["name"] = $category->getName();
            $value["desc"] = strip_tags($category->getDescription());
            $value["url"] = $category['url'];
            $value["metaDesc"] = $category->getMetaDescription() . $category->getMetaKeywords();
            $value["shortDesc"] = substr(strip_tags($category->getDescription()), 0, 200);
            $value["listCategory"] = "KLEVU_CATEGORY";
            $value["category"] = "Categories";
            $value["salePrice"] = 0;
            $value["currency"] = "USD";
            $value["inStock"] = "yes";
			$value["visibility"] = "search";
            $category_data_new[] = $value;
        }
        return $category_data_new;
    }
	
	public function loadCategoryCollection($storeId,$category_ids)
	{
		
		$category_data = $this->_catalogModelCategory->getCollection()
            ->setStoreId($storeId)
            ->addAttributeToSelect("*")->addFieldToFilter('entity_id', [
                'in' => $category_ids
            ]);
		return $category_data;
	}



}