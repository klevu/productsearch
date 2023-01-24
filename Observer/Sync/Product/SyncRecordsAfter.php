<?php

namespace Klevu\Search\Observer\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Sync\Product\RecordHistoryInterface;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Service\Sync\Product\RecordHistory;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class SyncRecordsAfter implements ObserverInterface
{
    const SYNC_RECORD_FIELD_PRODUCT_ID = 'id';
    const SYNC_RECORD_FIELD_PARENT_ID = 'itemGroupId';

    /**
     * @var RecordHistoryInterface
     */
    private $recordHistory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RecordHistoryInterface $recordHistory
     * @param LoggerInterface $logger
     */
    public function __construct(
        RecordHistoryInterface $recordHistory,
        LoggerInterface $logger
    ) {
        $this->recordHistory = $recordHistory;
        $this->logger = $logger;
    }

    /**
     * @param EventObserver $observer
     *
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $event = $observer->getEvent();
        try {
            $this->validateEventData($event);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        /** @var array[] $recordsToSync */
        $recordsToSync = $event->getData('recordsToSync');
        /** @var Response $response */
        $response = $event->getData('response');
        $success = $response->isSuccess();
        $message = $response->getMessage();
        $action = $event->getData('action');
        $store = $event->getData('store');

        $actionsToRecord = [];
        foreach ($recordsToSync as $recordToSync) {
            $productId = $this->getProductId($recordToSync);
            if (!$productId) {
                continue;
            }
            $parentId = $this->getParentId($recordToSync);

            $actionsToRecord[] = [
                RecordHistory::RECORD_PARAM_PRODUCT_ID => (int)$productId,
                RecordHistory::RECORD_PARAM_PARENT_ID => $parentId,
                RecordHistory::RECORD_PARAM_STORE_ID => (int)$store,
                RecordHistory::RECORD_PARAM_ACTION => $action,
                RecordHistory::RECORD_PARAM_SUCCESS => (bool)$success,
                RecordHistory::RECORD_PARAM_MESSAGE => $message
            ];
        }

        try {
            $this->recordHistory->execute($actionsToRecord);
        } catch (\Exception $exception) {
            $this->logger->error(__($exception->getMessage()));
        }
    }

    /**
     * @param array $recordToSync
     *
     * @return int|null
     */
    private function getProductId(array $recordToSync)
    {
        if (!isset($recordToSync[self::SYNC_RECORD_FIELD_PRODUCT_ID])) {
            return null;
        }
        $return = $recordToSync[self::SYNC_RECORD_FIELD_PRODUCT_ID];
        if (false !== strpos($recordToSync[self::SYNC_RECORD_FIELD_PRODUCT_ID], '-')) {
            $ids = explode('-', $recordToSync[self::SYNC_RECORD_FIELD_PRODUCT_ID]);
            $return = $ids[1];
        }

        return $return;
    }

    /**
     * @param array $recordToSync
     * @return int
     */
    private function getParentId(array $recordToSync)
    {
        if (array_key_exists(self::SYNC_RECORD_FIELD_PARENT_ID, $recordToSync)) {
            return (int)($recordToSync[self::SYNC_RECORD_FIELD_PARENT_ID] ?: 0);
        }

        $return = 0;
        if (false !== strpos($recordToSync[self::SYNC_RECORD_FIELD_PRODUCT_ID], '-')) {
            $ids = explode('-', $recordToSync[self::SYNC_RECORD_FIELD_PRODUCT_ID]);
            if ((int)$ids[0]) {
                $return = (int)$ids[0];
            }
        }

        return $return;
    }

    /**
     * @param Event $event
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateEventData(Event $event)
    {
        $recordsToSync = $event->getData('recordsToSync');
        if (!is_array($recordsToSync)) {
            throw new InvalidArgumentException(
                __(
                    '%s: Event Data recordsToSync must be an array. %s provided',
                    __METHOD__,
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    is_object($recordsToSync) ? get_class($recordsToSync) : gettype($recordsToSync)
                )
            );
        }
        $response = $event->getData('response');
        if (!($response instanceof Response)) {
            throw new InvalidArgumentException(
                __(
                    '%s: Event Data response must be an instance of Klevu\Search\Model\Api\Response. %s provided',
                    __METHOD__,
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    is_object($response) ? get_class($response) : gettype($response)
                )
            );
        }
        $action = $event->getData('action');
        if (!is_string($action)) {
            throw new InvalidArgumentException(
                __(
                    '%s: Event Data action must be a string. %s provided',
                    __METHOD__,
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    is_object($action) ? get_class($action) : gettype($action)
                )
            );
        }
        $store = $event->getData('store');
        if (!is_numeric($store)) {
            throw new InvalidArgumentException(
                __(
                    '%s: Event Data store must be numeric. %s provided',
                    __METHOD__,
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    is_object($store) ? get_class($store) : gettype($store)
                )
            );
        }
    }
}
