<?php

namespace Klevu\Search\Model\Product;

use Klevu\Logger\Constants as LoggerConstants;
use Klevu\Search\Helper\Compat as CompatHelper;
use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Api\Action\Startsession as KlevuStartsession;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Model\Api\Response\Rempty as EmptyResponse;
use Klevu\Search\Model\Context as Klevu_Context;
use Klevu\Search\Model\Sync as KlevuSync;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class KlevuProductActions extends DataObject implements KlevuProductActionsInterface
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
     * @var KlevuStartsession
     */
    protected $_apiActionStartsession;
    /**
     * @var BackendSession
     */
    protected $_searchModelSession;
    /**
     * @var KlevuSync
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

    const CHUNK_SIZE_DEFAULT    = 50;
    const CHUNK_SIZE_MAX = 500;

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
        parent::__construct([]);
    }

    /**
     * Setup an API session for the given store. Sets the store and session ID on self. Returns
     * true on success or false if Product Sync is disabled, store is not configured or the
     * session API call fails.
     *
     * @param StoreInterface $store
     *
     * @return bool|null
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function setupSession($store)
    {
        $config = $this->_searchHelperConfig;
        $apiKey = $config->getRestApiKey($store->getId());
        if (!$apiKey) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_INFO,
                sprintf("No API key found for %s (%s).", $store->getWebsite()->getName(), $store->getName())
            );

            return null;
        }

        /** @var Response|EmptyResponse $response */
        $response = $this->_apiActionStartsession->execute([
            'api_key' => $apiKey,
            'store' => $store,
        ]);

        if ($response->isSuccess()) {
            $this->addData([
                'store' => $store,
                'session_id' => $response->getSessionId(),
            ]);
            $this->_searchModelSession->setKlevuSessionId($response->getSessionId());

            return true;
        }
        $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR,
            sprintf(
                "Failed to start a session for %s (%s): %s",
                $store->getWebsite()->getName(),
                $store->getName(),
                $response->getMessage()
            ));
        if ($response instanceof EmptyResponse) {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR,
                sprintf(
                    "Product Sync failed for %s (%s): Could not contact Klevu.",
                    $store->getWebsite()->getName(),
                    $store->getName()
                ));
        } else {
            $this->_searchHelperData->log(LoggerConstants::ZEND_LOG_ERR,
                sprintf(
                    "Product Sync failed for %s (%s): %s",
                    $store->getWebsite()->getName(),
                    $store->getName(),
                    $response->getMessage()
                ));
        }

        return false;
    }

    /**
     * Delete success processing , separated for easier override
     *
     * @param array $data
     * @param Response $response
     *
     * @return bool|string
     * @throws NoSuchEntityException
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
                ($skipped_count > 1)
                    ? "s"
                    : "",
                implode(", ", $skipped_records["messages"])
            );
        }

        return true;
    }

    /**
     * Build the delete SQL , separated for easier override
     *
     * @param array $data
     * @param array $skipped_record_ids
     *
     * @return Select
     * @throws NoSuchEntityException
     */
    public function getDeleteProductsSuccessSql(array $data, array $skipped_record_ids)
    {
        $connection = $this->_frameworkModelResource->getConnection("core_write");
        $select = $connection->select();
        $select->from(['k' => $this->_frameworkModelResource->getTableName("klevu_product_sync")]);
        $select->where("k.store_id = ?", $this->_storeModelStoreManagerInterface->getStore()->getId());
        $select->where("k.type = ?", "products");

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
     * Update success processing, separated for easier override
     *
     * @param array $data
     * @param EmptyResponse $response
     * @param bool|string $batchStartTime
     *
     * @return bool|string True on success; error summary string if any records skipped
     * @throws \Throwable Rethrows DB exceptions after rollback
     */
    public function executeUpdateProductsSuccess(array $data, $response, $batchStartTime = null)
    {
        $connection = $this->_frameworkModelResource->getConnection('core_write');
        $skippedIndexMap = $this->getSkippedIndexMapFromResponse($response);
        $batchStartTime = $this->resolveBatchStartTime($batchStartTime);

        $storeId = (int)$this->_storeModelStoreManagerInterface->getStore()->getId();
        $pairs   = [];

        $iMaxCount = count($data);
        for ($i = 0; $i < $iMaxCount; $i++) {
            if (isset($skippedIndexMap[$i])) {
                continue;
            }
            if (!isset($data[$i]['id'])) {
                continue;
            }

            $ids = $this->_searchHelperData->getMagentoProductId($data[$i]['id']);
            if (!empty($ids)) {
                $pairs[] = [
                    (int)(isset($ids['product_id']) ? $ids['product_id'] : 0),
                    (int)(isset($ids['parent_id']) ? $ids['parent_id'] : 0),
                ];
            }
        }

        $registry = $this->_klevuSyncModel->getRegistry();
        $registry->unregister('numberOfRecord_update');
        $registry->register('numberOfRecord_update', count($pairs));

        if (!empty($pairs)) {
            $table = $this->_frameworkModelResource->getTableName('klevu_product_sync');
            $type  = 'products';
            $chunkSize = $this->_searchHelperConfig->getChunkSize(true, $storeId);
            $connection->beginTransaction();
            try {
                foreach (array_chunk($pairs, $chunkSize) as $chunk) {
                    $orParts = [];
                    $binds   = [];
                    $binds[] = $storeId;
                    $binds[] = $type;

                    foreach ($chunk as $pp) {
                        $orParts[] = '(product_id = ? AND parent_id = ?)';
                        $binds[]   = $pp[0]; // product_id
                        $binds[]   = $pp[1]; // parent_id
                    }

                    $whereSql = 'store_id = ? AND type = ? AND (' . implode(' OR ', $orParts) . ')';
                    $sql = "UPDATE $table SET last_synced_at = ? WHERE $whereSql";
                    array_unshift($binds, $batchStartTime);

                    $connection->query($sql, $binds);
                }
                $connection->commit();
            } catch (\Throwable $e) {
                $connection->rollBack();
                $this->_searchHelperData->log(
                    LoggerConstants::ZEND_LOG_ERR,
                    sprintf(
                        'Product updates sent to Klevu but Magento table operation failed in %s: %s',
                        __METHOD__,
                        $e->getMessage()
                    )
                );
                throw $e;
            }
        }

        $skippedCount = count($skippedIndexMap);
        if ($skippedCount > 0) {
            $messages = $this->getSkippedMessagesFromResponse($response);
            return sprintf(
                '%d product%s failed (%s)',
                $skippedCount,
                ($skippedCount > 1) ? 's' : '',
                $messages
            );
        }

        return true;
    }

    /**
     *
     * @param array $data
     * @param Response $response
     * @param string|null $batchStartTime
     *
     * @return bool|string True on success; error summary string if any records skipped
     * @throws \Throwable Rethrows DB exceptions after rollback
     */
    public function executeAddProductsSuccess(
        array $data,
        $response,
        $batchStartTime = null
    ) {
        $skippedIndexMap = $this->getSkippedIndexMapFromResponse($response);
        $batchStartTime = $this->resolveBatchStartTime($batchStartTime);

        $storeId = (int)$this->_storeModelStoreManagerInterface->getStore()->getId();
        $rows = [];
        foreach ($data as $i => $item) {
            if (isset($skippedIndexMap[$i])) {
                continue;
            }
            $ids = $this->_searchHelperData->getMagentoProductId($item['id']);
            $rows[] = [
                (int)($ids['product_id'] ?? 0),
                (int)($ids['parent_id'] ?? 0),
                $storeId,
                $batchStartTime,
                'products',
            ];
        }

        $registry = $this->_klevuSyncModel->getRegistry();
        $registry->unregister('numberOfRecord_add');
        $registry->register('numberOfRecord_add', count($rows));

        if (!empty($rows)) {
            $connection = $this->_frameworkModelResource->getConnection('core_write');
            $table = $this->_frameworkModelResource->getTableName('klevu_product_sync');

            $chunkSize = $this->_searchHelperConfig->getChunkSize(false, $storeId);
            $connection->beginTransaction();
            try {
                foreach (array_chunk($rows, $chunkSize) as $chunkRow) {
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
                        'Product addition sent to Klevu but Magento table operation failed in %s: %s',
                        __METHOD__,
                        $e->getMessage()
                    )
                );
                throw $e;
            }
        }

        $skippedCount = count($skippedIndexMap);
        if ($skippedCount > 0) {
            $messages = $this->getSkippedMessagesFromResponse($response);
            return sprintf(
                '%d product%s failed (%s)',
                $skippedCount,
                ($skippedCount > 1) ? 's' : '',
                $messages
            );
        }
        return true;
    }

    /**
     * Extract skipped index map from API response.
     *
     * @param Response $response
     *
     * @return array
     */
    private function getSkippedIndexMapFromResponse(Response $response)
    {
        $skippedIndexMap = [];
        $skippedRecords  = method_exists($response, 'getSkippedRecords')
            ? (array)$response->getSkippedRecords()
            : [];

        if (!empty($skippedRecords['index']) && is_array($skippedRecords['index'])) {
            $skippedIndexMap = array_flip($skippedRecords['index']);
        }

        return $skippedIndexMap;
    }

    /**
     * Normalize and validate batch start time.
     *
     * @param ?string $batchStartTime
     *
     * @return string
     */
    private function resolveBatchStartTime(?string $batchStartTime = null)
    {
        $nowStr   = $this->_searchHelperCompat->now();
        $candidate = ($batchStartTime !== null) ? $batchStartTime : $nowStr;

        if (false === strtotime($candidate)) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf(
                    "Invalid batch start time (%s) passed to %s",
                    (string)$candidate,
                    __METHOD__
                )
            );
            return $nowStr;
        }

        try {
            $dt = new \DateTimeImmutable($candidate);
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            $this->_searchHelperData->log(
                LoggerConstants::ZEND_LOG_ERR,
                sprintf(
                    "Failed to normalise batch start time (%s) in %s: %s",
                    (string)$candidate,
                    __METHOD__,
                    $e->getMessage()
                )
            );
            return $nowStr;
        }
    }

    /**
     * @param Response $response
     *
     * @return string
     */
    private function getSkippedMessagesFromResponse(Response $response)
    {
        $skippedRecords  = method_exists($response, 'getSkippedRecords')
            ? (array)$response->getSkippedRecords()
            : [];

        if (!empty($skippedRecords['messages']) && is_array($skippedRecords['messages'])) {
            return implode(', ', $skippedRecords['messages']);
        }

        return 'No messages';
    }
}
