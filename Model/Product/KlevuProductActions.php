<?php
/**
 * Class \Klevu\Search\Model\Product\Sync
 * @method \Magento\Framework\Db\Adapter\Interface getConnection()
 * @method \Magento\Store\Model\Store getStore()
 * @method string getKlevuSessionId()
 */

namespace Klevu\Search\Model\Product;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Model\Context as Klevu_Context;
use Magento\Framework\DataObject;

class KlevuProductActions  extends DataObject implements KlevuProductActionsInterface
{

    protected $_searchHelperConfig;
    protected $_searchHelperData;
    protected $_apiActionStartsession;
    protected $_searchModelSession;
    protected $_klevuSyncModel;
    protected $_frameworkModelResource;
    protected $_searchHelperCompat;
    protected $_storeModelStoreManagerInterface;

    public function __construct(
        Klevu_Context $context
    )
    {
        $this->_searchHelperConfig = $context->getHelperManager()->getConfigHelper();
        $this->_searchHelperData = $context->getHelperManager()->getDataHelper();
        $this->_apiActionStartsession = $context->getStartSession();
        $this->_searchModelSession = $context->getBackendSession();
        $this->_klevuSyncModel = $context->getSync();
        $this->_frameworkModelResource = $context->getResourceConnection();
        $this->_searchHelperCompat = $context->getHelperManager()->getCompatHelper();
        $this->_storeModelStoreManagerInterface = $context->getStoreManagerInterface();
        parent::__construct([]);
    }

    /**
     * Setup an API session for the given store. Sets the store and session ID on self. Returns
     * true on success or false if Product Sync is disabled, store is not configured or the
     * session API call fails.
     *
     * @param \Magento\Store\Model\Store $store
     *
     * @return bool
     */
    public function setupSession($store)
    {

        $config = $this->_searchHelperConfig;
	//Moved to each of the specific product sync
        /*if (!$config->isProductSyncEnabled($store->getId())) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, sprintf("Disabled for %s (%s).", $store->getWebsite()->getName(), $store->getName()));
            return null;
        }*/
        $api_key = $config->getRestApiKey($store->getId());
        if (!$api_key) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_INFO, sprintf("No API key found for %s (%s).", $store->getWebsite()->getName(), $store->getName()));
            return null;
        }

        $response = $this->_apiActionStartsession->execute([
            'api_key' => $api_key,
            'store' => $store,
        ]);

        if ($response->isSuccess()) {
            $this->addData([
                'store' => $store,
                'session_id' => $response->getSessionId()
            ]);
            $this->_searchModelSession->setKlevuSessionId($response->getSessionId());
            return true;
        } else {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, sprintf(
                "Failed to start a session for %s (%s): %s",
                $store->getWebsite()->getName(),
                $store->getName(),
                $response->getMessage()
            ));
            if ($response instanceof \Klevu\Search\Model\Api\Response\Rempty) {
                $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, sprintf(
                    "Product Sync failed for %s (%s): Could not contact Klevu.",
                    $store->getWebsite()->getName(),
                    $store->getName()
                ));
            } else {
                $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR, sprintf(
                    "Product Sync failed for %s (%s): %s",
                    $store->getWebsite()->getName(),
                    $store->getName(),
                    $response->getMessage()
                ));
            }
            return false;
        }
    }


    /**
     * Delete success processing , separated for easier override
     *
     * @param array $data
     * @param Response $response
     *
     * @return bool|string
     */
    public function executeDeleteProductsSuccess(array $data, $response)
    {
        $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_delete");
        $this->_klevuSyncModel->getRegistry()->register("numberOfRecord_delete", count($data));
		
        $skipped_record_ids = [];
        if ($skipped_records = $response->getSkippedRecords()) {
            $skipped_record_ids = array_flip($skipped_records["index"]);
        }

        $connection = $this->_frameworkModelResource->getConnection("core_write");

        $select = $this->getDeleteProductsSuccessSql($data, $skipped_record_ids);

        $connection->query($select->deleteFromSelect("k"));

        $skipped_count = count($skipped_record_ids);
        if ($skipped_count > 0) {
            return sprintf(
                "%d product%s failed (%s)",
                $skipped_count,
                ($skipped_count > 1) ? "s" : "",
                implode(", ", $skipped_records["messages"])
            );
        }

        return true;
    }


    /**
     * Build the delete SQL , separated for easier override
     */
    public function getDeleteProductsSuccessSql(array $data, $skipped_record_ids)
    {

        $connection = $this->_frameworkModelResource->getConnection("core_write");
        $select = $connection
            ->select()
            ->from(['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")])
            ->where("k.store_id = ?", $this->_storeModelStoreManagerInterface->getStore()->getId())
            ->where("k.type = ?", "products");

        $or_where = [];
        $iMaxCount = count($data);
        for ($i = 0; $i < $iMaxCount; $i++) {
            if (isset($skipped_record_ids[$i])) {
                continue;
            }
            $or_where[] = sprintf(
                "(%s AND %s)",
                $connection->quoteInto("k.product_id = ?", $data[$i]['product_id']),
                $connection->quoteInto("k.parent_id = ?", $data[$i]['parent_id']),
                $connection->quoteInto("k.type = ?", "products")
            );
        }
        $select->where(implode(" OR ", $or_where));
        return $select;
    }

    /**
     * Update success processing , separated for easier override
     */
    public function executeUpdateProductsSuccess(array $data, $response)
    {
        $connection = $this->_frameworkModelResource->getConnection("core_write");
        $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_update");
        $this->_klevuSyncModel->getRegistry()->register("numberOfRecord_update", count($data));	
        $skipped_record_ids = [];
        if ($skipped_records = $response->getSkippedRecords()) {
            $skipped_record_ids = array_flip($skipped_records["index"]);
        }

        $where = [];
        $iMaxCount = count($data);
        for ($i = 0; $i < $iMaxCount; $i++) {
            if (isset($skipped_record_ids[$i])) {
                continue;
            }

            if (isset($data[$i]['id'])) {
                $ids = $this->_searchHelperData->getMagentoProductId($data[$i]['id']);
                if (!empty($ids)) {
                    $where[] = sprintf(
                        "(%s AND %s AND %s)",
                        $connection->quoteInto("product_id = ?", $ids['product_id']),
                        $connection->quoteInto("parent_id = ?", $ids['parent_id']),
                        $connection->quoteInto("type = ?", "products")
                    );
                }
            }
        }


        if (!empty($where)) {
            $where = sprintf(
                "(%s) AND (%s)",
                $connection->quoteInto("store_id = ?", $this->_storeModelStoreManagerInterface->getStore()->getId()),
                implode(" OR ", $where)
            );

            $this->_frameworkModelResource->getConnection("core_write")->update(
                $this->_frameworkModelResource->getTableName('klevu_product_sync'),
                ['last_synced_at' => $this->_searchHelperCompat->now()],
                $where
            );
        }

        $skipped_count = count($skipped_record_ids);
        if ($skipped_count > 0) {
            return sprintf(
                "%d product%s failed (%s)",
                $skipped_count,
                ($skipped_count > 1) ? "s" : "",
                implode(", ", $skipped_records["messages"])
            );
        } else {
            return true;
        }
    }

    /**
     * Add success processing , separated for easier override
     */
    public function executeAddProductsSuccess(array $data, $response)
    {
		
        $skipped_record_ids = [];
        if ($skipped_records = $response->getSkippedRecords()) {
            $skipped_record_ids = array_flip($skipped_records["index"]);
        }

        $sync_time = $this->_searchHelperCompat->now();
        $this->_klevuSyncModel->getRegistry()->unregister("numberOfRecord_add");
        $this->_klevuSyncModel->getRegistry()->register("numberOfRecord_add", count($data));
        foreach ($data as $i => &$record) {
            if (isset($skipped_record_ids[$i])) {
                unset($data[$i]);
                continue;
            }

            $ids = $this->_searchHelperData->getMagentoProductId($data[$i]['id']);

            $record = [
                $ids["product_id"],
                $ids["parent_id"],
                $this->_storeModelStoreManagerInterface->getStore()->getId(),
                $sync_time,
                "products"
            ];
        }

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $write = $this->_frameworkModelResource->getConnection("core_write");
                $query = "replace into " . $this->_frameworkModelResource->getTableName('klevu_product_sync')
                    . "(product_id, parent_id, store_id, last_synced_at, type) values "
                    . "(:product_id, :parent_id, :store_id, :last_synced_at, :type)";

                $binds = [
                    'product_id' => $value[0],
                    'parent_id' => $value[1],
                    'store_id' => $value[2],
                    'last_synced_at' => $value[3],
                    'type' => $value[4]
                ];
                $write->query($query, $binds);
            }
        }

        $skipped_count = count($skipped_record_ids);
        if ($skipped_count > 0) {
            return sprintf(
                "%d product%s failed (%s)",
                $skipped_count,
                ($skipped_count > 1) ? "s" : "",
                implode(", ", $skipped_records["messages"])
            );
        } else {
            return true;
        }
    }
}
