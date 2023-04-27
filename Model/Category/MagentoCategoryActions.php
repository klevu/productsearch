<?php
/**
 * Class \Klevu\Search\Model\Category\MagentoCategoryActions
 */

namespace Klevu\Search\Model\Category;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Compat as CompatHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Api\Action\Addrecords as ApiActionAddRecords;
use Klevu\Search\Model\Api\Action\Deleterecords as ApiActionDeleteRecords;
use Klevu\Search\Model\Api\Action\Updaterecords as ApiActionUpdateRecords;
use Klevu\Search\Model\Category\KlevuCategoryActions as Klevu_Category_Actions;
use Klevu\Search\Model\Klevu\HelperManager;
use Klevu\Search\Model\Sync;
use Magento\Backend\Model\Session;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Framework\Model\AbstractModel;
use \Magento\Eav\Model\Config as Eav_Config;
use Klevu\Search\Model\Category\LoadAttribute as Klevu_LoadCategoryAttribute;
use Klevu\Search\Model\Context as Klevu_Context;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class MagentoCategoryActions extends AbstractModel implements MagentoCategoryActionsInterface
{
    /**
     * @var ProductMetadataInterface
     */
    protected $_ProductMetadataInterface;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;
    /**
     * @var Session
     */
    protected $_searchModelSession;
    /**
     * @var ApiActionDeleteRecords
     */
    protected $_apiActionDeleterecords;
    /**
     * @var ApiActionUpdateRecords
     */
    protected $_apiActionUpdaterecords;
    /**
     * @var ApiActionAddRecords
     */
    protected $_apiActionAddrecords;
    /**
     * @var KlevuCategoryActions
     */
    protected $_klevuCategoryAction;
    /**
     * @var Sync
     */
    protected $_klevuSyncModel;
    /**
     * @var ResourceConnection
     */
    protected $_frameworkModelResource;
    /**
     * @var Eav_Config
     */
    protected $_eavModelConfig;
    /**
     * @var LoadAttribute
     */
    protected $_loadAttribute;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;
    /**
     * @var CompatHelper
     */
    protected $_searchHelperCompat;
    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;

    /**
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
    ) {
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
        /** @var HelperManager $helperManager */
        $helperManager = $context->getHelperManager();
        $this->_searchHelperConfig = $helperManager->getConfigHelper();
        $this->_searchHelperCompat = $helperManager->getCompatHelper();
        $this->_searchHelperData = $helperManager->getDataHelper();
    }

    /**
     * Returns category pages array based on store and action or error message will be shown if it failed.
     *
     * @param StoreInterface $store
     * @param string $action
     *
     * @return array
     */
    public function getCategorySyncDataActions(StoreInterface $store, $action)
    {
        $catPages = [];
        $storeId = $store->getId();
        try {
            switch ($action) {
                case "delete":
                    $catPages = $this->_klevuSyncModel->getCategoryToDelete($storeId);
                    break;
                case "update":
                    $catPages = $this->_klevuSyncModel->getCategoryToUpdate($storeId);
                    break;
                case "add":
                    $catPages = $this->_klevuSyncModel->getCategoryToAdd($storeId);
                    break;
            }

            return $catPages;
        } catch (\Exception $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf("Error in collecting category pages for action %s - %s", $action, $e->getMessage())
            );

            return [];
        }
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
     * @throws NoSuchEntityException
     */
    public function updateCategory(array $data)
    {
        $total = count($data);
        $data = $this->_loadAttribute->addcategoryData($data);
        $response = $this->_apiActionUpdaterecords->setStore($this->_storeModelStoreManagerInterface->getStore())
            ->execute(
                [
                    'sessionId' => $this->_searchModelSession->getKlevuSessionId(),
                    'records' => $data,
                ]
            );
        if ($response->isSuccess()) {
            return $this->_klevuCategoryAction->executeUpdateCategorySuccess($data, $response);
        }
        $this->_searchModelSession->setKlevuFailedFlag(1);

        return sprintf(
            "%d category%s failed (%s)",
            $total,
            ($total > 1)
                ? "s"
                : "",
            $response->getMessage()
        );
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
     * @throws NoSuchEntityException
     */
    public function deleteCategory(array $data)
    {
        $total = count($data);
        $response = $this->_apiActionDeleterecords->setStore($this->_storeModelStoreManagerInterface->getStore())
            ->execute(
                [
                    'sessionId' => $this->_searchModelSession->getKlevuSessionId(),
                    'records' => array_map(function ($v) {
                        return [
                            'id' => "categoryid_" . $v['category_id'],
                        ];
                    }, $data),
                ]
            );
        if ($response->isSuccess()) {
            return $this->_klevuCategoryAction->executeDeleteCategorySuccess($data, $response);
        }
        $this->_searchModelSession->setKlevuFailedFlag(1);

        return sprintf(
            "%d category%s failed (%s)",
            $total,
            ($total > 1) ? "s" : "",
            $response->getMessage()
        );
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
     * @throws NoSuchEntityException
     */
    public function addCategory(array $data)
    {
        $total = count($data);
        $data = $this->_loadAttribute->addcategoryData($data);
        $response = $this->_apiActionAddrecords->setStore($this->_storeModelStoreManagerInterface->getStore())
            ->execute(
                [
                    'sessionId' => $this->_searchModelSession->getKlevuSessionId(),
                    'records' => $data,
                ]
            );
        if ($response->isSuccess()) {
            return $this->_klevuCategoryAction->executeAddCategorySuccess($data, $response);
        }
        $this->_searchModelSession->setKlevuFailedFlag(1);

        return sprintf(
            "%d category%s failed (%s)",
            $total,
            ($total > 1) ? "s" : "",
            $response->getMessage()
        );
    }

    /**
     * Return the URL rewrite data for the given products for the current store.
     *
     * @param array $categoryIds A list of product IDs.
     *
     * @return array A list with product IDs as keys and request paths as values.
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    public function getCategoryUrlRewriteData($categoryIds)
    {
        $store = $this->_storeModelStoreManagerInterface->getStore();
        $connection =$this->_frameworkModelResource->getConnection("core_write");
        $stmt = $connection->query(
            $this->_searchHelperCompat->getCategoryUrlRewriteSelect($categoryIds, $store->getId())
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
