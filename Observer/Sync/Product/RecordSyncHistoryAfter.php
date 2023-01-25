<?php

namespace Klevu\Search\Observer\Sync\Product;

use Klevu\Search\Api\Service\Sync\Product\DeleteHistoryInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class RecordSyncHistoryAfter implements ObserverInterface
{
    /**
     * @var DeleteHistoryInterface
     */
    private $deleteHistory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DeleteHistoryInterface $deleteHistory
     * @param LoggerInterface $logger
     */
    public function __construct(
        DeleteHistoryInterface $deleteHistory,
        LoggerInterface $logger
    ) {
        $this->deleteHistory = $deleteHistory;
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
        /** @var array $historyItems */
        $historyItems = $event->getData('historyItems');

        try {
            $this->deleteHistory->execute($historyItems);
        } catch (\Exception $exception) {
            $this->logger->error(__($exception->getMessage()));
        }
    }
}
