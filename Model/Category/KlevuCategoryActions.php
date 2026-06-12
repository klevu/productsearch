<?php

namespace Klevu\Search\Model\Category;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Compat as CompatHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Api\Action\StartSession;
use Klevu\Search\Model\Context as Klevu_Context;
use Klevu\Search\Model\Session;
use Klevu\Search\Model\Sync;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class KlevuCategoryActions extends DataObject implements KlevuCategoryActionsInterface
{

    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;
    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;
    /**
     * @var StartSession
     */
    protected $_apiActionStartsession;
    /**
     * @var Session
     */
    protected $_searchModelSession;
    /**
     * @var Sync
     */
    protected $_klevuSyncModel;
    /**
     * @var ResourceConnection
     */
    protected $_frameworkModelResource;
    /**
     * @var CompatHelper
     */
    protected $_searchHelperCompat;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    /**
     * @param Klevu_Context $context
     */
    public function __construct(
        Klevu_Context $context
    ) {
        $this->_searchHelperConfig = $context->getHelperManager()->getConfigHelper();
        $this->_searchHelperData = $context->getHelperManager()->getDataHelper();
        $this->_apiActionStartsession = $context->getStartSession();
        $this->_searchModelSession = $context->getBackendSession();
        $this->_klevuSyncModel = $context->getSync();
        $this->_frameworkModelResource = $context->getResourceConnection();
        $this->_searchHelperCompat = $context->getHelperManager()->getCompatHelper();
        $this->_storeModelStoreManagerInterface = $context->getStoreManagerInterface();
        parent::__construct();
    }

    /**
     * Delete success processing, separated for easier override
     * @param array $data
     * @param mixed $response
     *
     * @return string|true
     */
    public function executeDeleteCategorySuccess(array $data, $response)
    {

        $connection = $this->_frameworkModelResource->getConnection();
        $select = $connection->select()->from([
            'k' => $this->_frameworkModelResource->getTableName("klevu_product_sync"),
        ])->where("k.store_id = ?", $this->_storeModelStoreManagerInterface->getStore()->getId())
            ->where("k.type = ?", "categories");
        $skipped_record_ids = [];
        if ($skipped_records = $response->getSkippedRecords()) {
            $skipped_record_ids = array_flip($skipped_records["index"]);
        }
        $or_where = [];
        $iMaxCount = count($data);
        for ($i = 0; $i < $iMaxCount; $i++) {
            if (isset($skipped_record_ids[$i])) {
                continue;
            }
            $or_where[] = sprintf("(%s)", $connection->quoteInto("k.product_id = ?", $data[$i]['category_id']));
        }
        $select->where(implode(" OR ", $or_where));
        $connection->query($select->deleteFromSelect("k"));
        $skipped_count = count($skipped_record_ids);
        if ($skipped_count > 0) {
            return sprintf(
                "%d category%s failed (%s)",
                $skipped_count,
                ($skipped_count > 1) ? "s" : "",
                implode(", ", $skipped_records["messages"])
            );
        } else {
            return true;
        }
    }

    /**
     * Update success processing, separated for easier override
     * @param array $data
     * @param mixed $response
     *
     * @return string|true
     * @throws NoSuchEntityException
     */
    public function executeUpdateCategorySuccess(array $data, $response)
    {
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
            $ids[$i] = explode("_", $data[$i]['id']);
            $where[] = sprintf(
                "(%s AND %s AND %s)",
                $this->_frameworkModelResource->getConnection()->quoteInto("product_id = ?", $ids[$i][1]),
                $this->_frameworkModelResource->getConnection()->quoteInto("parent_id = ?", 0),
                $this->_frameworkModelResource->getConnection()->quoteInto("type = ?", "categories")
            );
        }
        $where = sprintf(
            "(%s) AND (%s)",
            $this->_frameworkModelResource->getConnection()
                ->quoteInto("store_id = ?", $this->_storeModelStoreManagerInterface->getStore()->getId()),
            implode(" OR ", $where)
        );
        $this->_frameworkModelResource->getConnection()->update(
            $this->_frameworkModelResource->getTableName('klevu_product_sync'),
            [
                'last_synced_at' => $this->_searchHelperCompat->now(),
            ],
            $where
        );
        $skipped_count = count($skipped_record_ids);
        if ($skipped_count > 0) {
            return sprintf(
                "%d category%s failed (%s)",
                $skipped_count,
                ($skipped_count > 1) ? "s" : "",
                implode(", ", $skipped_records["messages"])
            );
        } else {
            return true;
        }
    }

    /**
     * Add success processing, separated for easier override
     * @param array $data
     * @param mixed $response
     *
     * @return string|true
     * @throws \Throwable
     */
    public function executeAddCategorySuccess(array $data, $response)
    {
        $skippedIds = [];
        if ($skippedRecords = $response->getSkippedRecords()) {
            $skippedIds = array_flip($skippedRecords["index"]);
        }
        $sync_time = $this->_searchHelperCompat->now();
        $storeId = $this->_storeModelStoreManagerInterface->getStore()->getId();

        foreach ($data as $i => & $record) {
            if (isset($skippedIds[$i])) {
                unset($data[$i]);
                continue;
            }
            $ids[$i] = explode("_", $data[$i]['id']);
            $record = [
                $ids[$i][1],
                0,
                $storeId,
                $sync_time,
                "categories"
            ];
        }

        if (!empty($data)) {
            $connection = $this->_frameworkModelResource->getConnection("core_write");
            $table = $this->_frameworkModelResource->getTableName('klevu_product_sync');

            $chunkSize = property_exists($this, '_searchHelperConfig')
                ? $this->_searchHelperConfig->getChunkSize(false, $storeId)
                : 500;

            $connection->beginTransaction();
            try {
                foreach (array_chunk($data, $chunkSize) as $chunkRow) {
                    $placeholders = implode(
                        ',',
                        array_fill(0, count($chunkRow), '(?, ?, ?, ?, ?)')
                    );
                    $sql = "REPLACE INTO $table (product_id, parent_id, store_id, last_synced_at, type) VALUES $placeholders";

                    $binds = [];
                    foreach ($chunkRow as $column) {
                        array_push($binds, $column[0], $column[1], $column[2], $column[3], $column[4]);
                    }

                    $connection->query($sql, $binds);
                }
                $connection->commit();
            } catch (\Throwable $e) {
                $connection->rollBack();
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_ERR,
                    sprintf(
                        'Category addition sent to Klevu but Magento table operation failed in %s: %s',
                        __METHOD__,
                        $e->getMessage()
                    )
                );

                throw $e;
            }
        }

        $skippedIdCount = count($skippedIds);
        if ($skippedIdCount > 0) {
            return sprintf(
                "%d category%s failed (%s)",
                $skippedIdCount,
                ($skippedIdCount > 1) ? "s" : "",
                implode(", ", $skippedRecords["messages"])
            );
        } else {
            return true;
        }
    }
}
