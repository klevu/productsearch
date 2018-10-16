<?php
/**
 * Class \Klevu\Search\Model\Category\MagentoCategoryActions
 */

namespace Klevu\Search\Model\Category;

use Klevu\Search\Model\Category\KlevuCategoryActions as Klevu_Category_Actions;
use \Magento\Framework\Model\AbstractModel as AbstractModel;
use \Magento\Eav\Model\Config as Eav_Config;
use Klevu\Search\Model\Category\LoadAttribute as Klevu_LoadCategoryAttribute;
use Klevu\Search\Model\Context as Klevu_Context;

class MagentoCategoryActions extends AbstractModel implements MagentoCategoryActionsInterface
{

    /**
     * MagentoCategoryActions constructor.
     * @param Klevu_Context $context
     * @param Eav_Config $eavConfig
     * @param KlevuCategoryActions $klevuCategoryAction
     * @param LoadAttribute $loadAttribute
     */
    public function __construct(
        Klevu_Context $context,
        Eav_Config $eavConfig,
        Klevu_Category_Actions $klevuCategoryAction,     
		Klevu_LoadCategoryAttribute $loadAttribute
    )
    {

        $this->_ProductMetadataInterface = $context->getKlevuProductMeta();
        $this->_storeModelStoreManagerInterface = $context->getStoreManagerInterface();
        $this->_searchModelSession = $context->getBackendSession();
        $this->_apiActionDeleterecords = $context->getKlevuProductDelete();
        $this->_apiActionUpdaterecords = $context->getKlevuProductUpdate();
        $this->_apiActionAddrecords = $context->getKlevuProductAdd();
        $this->_klevuCategoryAction = $klevuCategoryAction;
        $this->_klevuSyncModel = $context->getSync();
        $this->_frameworkModelResource = $context->getResourceConnection();
        $this->_eavModelConfig = $eavConfig;
        $this->_loadAttribute = $loadAttribute;
        $this->_searchHelperConfig = $context->getHelperManager()->getConfigHelper();
        $this->_searchHelperCompat = $context->getHelperManager()->getCompatHelper();
    }


    public function getCategorySyncDataActions($store)
    {
        $actions = array(
            "delete" => $this->_klevuSyncModel->getCategoryToDelete($store->getId()),
            "update" => $this->_klevuSyncModel->getCategoryToUpdate($store->getId()),
            "add" => $this->_klevuSyncModel->getCategoryToAdd($store->getId()),
        );
        return $actions;
    }

    /**
     * Update the given categories on Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of categories to update. Each element should be an array
     *                    containing an element with "category_id" as the key and category id as
     *                    the value
     *
     * @return bool|string
     */
    public function updateCategory(array $data)
    {
        $total = count($data);
        $data = $this->_loadAttribute->addcategoryData($data);
        $response = $this->_apiActionUpdaterecords->setStore($this->getStore())->execute([
            'sessionId' => $this->_searchModelSession->getKlevuSessionId(),
            'records' => $data
        ]);
        if ($response->isSuccess()) {
            $this->_klevuCategoryAction->executeUpdateCategorySuccess($data, $response);
        } else {
            $this->_searchModelSession->setKlevuFailedFlag(1);
            return sprintf("%d category%s failed (%s)", $total, ($total > 1) ? "s" : "", $response->getMessage());
        }
    }

    /**
     * Delete the given categories from Klevu Search. Returns true if the operation was
     * successful, or the error message if the operation failed.
     *
     * @param array $data List of categories to delete. Each element should be an array
     *                    containing an element with "category_id" as the key and category id as
     *                    the value.
     *
     * @return bool|string
     */
    public function deleteCategory(array $data)
    {
        $total = count($data);
        $response = $this->_apiActionDeleterecords->setStore($this->getStore())->execute([
            'sessionId' => $this->_searchModelSession->getKlevuSessionId(),
            'records' => array_map(function ($v) {

                return [
                    'id' => "categoryid_" . $v['category_id']
                ];
            }, $data)
        ]);
        if ($response->isSuccess()) {
            $this->_klevuCategoryAction->executeDeleteCategorySuccess($data, $response);
        } else {
            $this->_searchModelSession->setKlevuFailedFlag(1);
            return sprintf("%d category%s failed (%s)", $total, ($total > 1) ? "s" : "", $response->getMessage());
        }
    }


    /**
     * Add the given Categories to Klevu Search. Returns true if the operation was successful,
     * or the error message if it failed.
     *
     * @param array $data List of Categories to add. Each element should be an array
     *                    containing an element with "category_id" as the key and category id as
     *                    the value.
     *
     * @return bool|string
     */
    public function addCategory(array $data)
    {
        $total = count($data);
        $data =$this->_loadAttribute->addcategoryData($data);
        $response = $this->_apiActionAddrecords->setStore($this->getStore())->execute([
            'sessionId' => $this->_searchModelSession->getKlevuSessionId(),
            'records' => $data
        ]);
        if ($response->isSuccess()) {
            $this->_klevuCategoryAction->executeAddCategorySuccess($data, $response);
        } else {
            $this->_searchModelSession->setKlevuFailedFlag(1);
            return sprintf("%d category%s failed (%s)", $total, ($total > 1) ? "s" : "", $response->getMessage());
        }
    }





    /**
     * Return the URL rewrite data for the given products for the current store.
     *
     * @param array $product_ids A list of product IDs.
     *
     * @return array A list with product IDs as keys and request paths as values.
     */
    public function getCategoryUrlRewriteData($category_ids)
    {
        $stmt = $this->_frameworkModelResource->getConnection("core_write")->query(
            $this->_searchHelperCompat->getCategoryUrlRewriteSelect($category_ids, $this->_storeModelStoreManagerInterface->getStore()->getId())
        );
        $data = [];

        while ($row = $stmt->fetch()) {
            if (!isset($data[$row['entity_id']])) {
                $data[$row['entity_id']] = $row['request_path'];
            }
        }
        return $data;
    }

}