<?php

namespace Klevu\Search\Service\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Sync\Product\RecordHistoryInterface;
use Klevu\Search\Api\KlevuProductSyncHistoryRepositoryInterface as ProductSyncHistoryRepository;
use Klevu\Search\Exception\Sync\Product\CouldNotSaveHistoryException;
use Klevu\Search\Model\Product\Sync\History;
use Klevu\Search\Model\Source\NextAction;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface;

class RecordHistory implements RecordHistoryInterface
{
    const RECORD_PARAM_PRODUCT_ID = 'product_id';
    const RECORD_PARAM_PARENT_ID = 'parent_id';
    const RECORD_PARAM_STORE_ID = 'store_id';
    const RECORD_PARAM_ACTION = 'action';
    const RECORD_PARAM_SUCCESS = 'success';
    const RECORD_PARAM_MESSAGE = 'message';

    /**
     * @var ProductSyncHistoryRepository
     */
    private $syncHistoryRepository;
    /**
     * @var EventManager
     */
    private $eventManager;
    /**
     * @var NextAction
     */
    private $nextAction;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string[]|null
     */
    private $nextActions;

    /**
     * @param ProductSyncHistoryRepository $syncHistoryRepository
     * @param EventManager $eventManager
     * @param NextAction $nextAction
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductSyncHistoryRepository $syncHistoryRepository,
        EventManager $eventManager,
        NextAction $nextAction,
        LoggerInterface $logger
    ) {
        $this->syncHistoryRepository = $syncHistoryRepository;
        $this->eventManager = $eventManager;
        $this->nextAction = $nextAction;
        $this->logger = $logger;
    }

    /**
     * @param array[] $records
     *
     * @return int
     * @throws CouldNotSaveHistoryException
     */
    public function execute(array $records)
    {
        $historyRecords = [];
        foreach ($records as $record) {
            $historyRecords[] = $this->processRecordToSave($record);
        }
        $recordsToSave = array_values(array_filter($historyRecords));
        $savedHistoryCount = 0;
        if (count($recordsToSave)) {
            $savedHistoryCount = $this->syncHistoryRepository->insert($recordsToSave);
        }
        if ($savedHistoryCount) {
            $this->eventManager->dispatch(
                'klevu_record_sync_history_after',
                [
                    'historyItems' => $recordsToSave
                ]
            );
        }

        return $savedHistoryCount;
    }

    /**
     * @param array $record
     *
     * @return array|null
     */
    private function processRecordToSave(array $record)
    {
        try {
            $this->validateRecord($record);
            $history = $this->formatHistoryRecord($record);
        } catch (InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage(), [$record]);

            return null;
        }

        return $history;
    }

    /**
     * @param array $record
     *
     * @return array
     */
    private function formatHistoryRecord(array $record)
    {
        $action = $this->getNextActionId($record['action']);
        $parentId = isset($record[static::RECORD_PARAM_PARENT_ID]) ? $record[static::RECORD_PARAM_PARENT_ID] : 0;
        $success = isset($record[static::RECORD_PARAM_SUCCESS]) ? $record[static::RECORD_PARAM_SUCCESS] : false;
        $message = isset($record[static::RECORD_PARAM_MESSAGE]) ? $record[static::RECORD_PARAM_MESSAGE] : '';

        $history = [];
        $history[History::FIELD_PRODUCT_ID] = $record[static::RECORD_PARAM_PRODUCT_ID];
        $history[History::FIELD_PARENT_ID] = $parentId;
        $history[History::FIELD_STORE_ID] = $record[static::RECORD_PARAM_STORE_ID];
        $history[History::FIELD_ACTION] = $action;
        $history[History::FIELD_SUCCESS] = $success;
        $history[History::FIELD_MESSAGE] = $message;

        return $history;
    }

    /**
     * @param string|int $action
     *
     * @return int|null
     */
    private function getNextActionId($action)
    {
        if (is_numeric($action) && array_key_exists($action, $this->getNextActions())) {
            return (int)$action;
        }
        $nextAction = array_keys(
            array_filter($this->getNextActions(), static function ($nextAction) use ($action) {
                return $action === $nextAction;
            })
        );

        return array_key_exists(0, $nextAction) ? (int)$nextAction[0] : null;
    }

    /**
     * @return string[]
     */
    private function getNextActions()
    {
        if (null === $this->nextActions) {
            $this->nextActions = $this->nextAction->getActions();
        }

        return $this->nextActions;
    }

    /**
     * @param array $record
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateRecord(array $record)
    {
        if (!isset($record[static::RECORD_PARAM_PRODUCT_ID])) {
            throw new InvalidArgumentException(
                __(
                    'Exception %1: Product ID is missing from the sync data to be recorded',
                    __METHOD__
                )
            );
        }
        if (!isset($record[static::RECORD_PARAM_STORE_ID])) {
            throw new InvalidArgumentException(
                __(
                    'Exception %1: Store Id is missing from the sync data to be recorded',
                    __METHOD__
                )
            );
        }
        if (!isset($record[static::RECORD_PARAM_ACTION]) || (
                !array_key_exists($record[static::RECORD_PARAM_ACTION], $this->getNextActions()) &&
                !in_array($record[static::RECORD_PARAM_ACTION], $this->getNextActions(), true)
            )
        ) {
            throw new InvalidArgumentException(
                __(
                    'Exception %1: Api Action in the sync data to be recorded is missing or invalid.',
                    __METHOD__
                )
            );
        }
        if (isset($record[static::RECORD_PARAM_MESSAGE]) && !is_string($record[static::RECORD_PARAM_MESSAGE])) {
            throw new InvalidArgumentException(
                __(
                    'Exception %1: Message in sync data to be recorded must be a string.',
                    __METHOD__
                )
            );
        }
    }
}
