<?php

namespace Klevu\Search\Model\Indexer\Sync;

use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Mview\View\StateInterface;
use Psr\Log\LoggerInterface;

class ProductStockSyncIndexer implements IndexerActionInterface, MviewActionInterface
{
    const RECORD_TYPE_PRODUCTS = 'products';
    const INDEXER_ID = 'klevu_product_sync_stock';

    /**
     * @var MagentoProductActionsInterface
     */
    private $magentoProductActions;
    /**
     * @var ChangelogInterface
     */
    private $changelog;
    /**
     * @var StateInterface
     */
    private $state;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param MagentoProductActionsInterface $magentoProductActions
     * @param ChangelogInterface $changelog
     * @param StateInterface $state
     * @param LoggerInterface $logger
     */
    public function __construct(
        MagentoProductActionsInterface $magentoProductActions,
        ChangelogInterface $changelog,
        StateInterface $state,
        LoggerInterface $logger
    ) {
        $this->magentoProductActions = $magentoProductActions;
        $this->changelog = $changelog;
        $this->state = $state;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function executeFull()
    {
        $ids = $this->getIdsToUpdate();
        $this->executeAction($ids);
    }

    /**
     * @param array $ids
     *
     * @return void
     */
    public function executeList(array $ids)
    {
        $this->executeAction($ids);
    }

    /**
     * @param int $id
     *
     * @return void
     */
    public function executeRow($id)
    {
        $this->executeAction([$id]);
    }

    /**
     * @param int[] $ids
     *
     * @return void
     */
    public function execute($ids)
    {
        $this->executeAction($ids);
    }

    /**
     * @param mixed[] $unfilteredIds
     *
     * @return void
     */
    private function executeAction(array $unfilteredIds)
    {
        $ids = array_filter($unfilteredIds, static function ($id) {
            return is_numeric($id) && (int)$id == $id; // intentionally used weak comparison
        });
        if (!$ids) {
            return;
        }

        $this->magentoProductActions->markRecordIntoQueue(
            $ids,
            static::RECORD_TYPE_PRODUCTS,
            null
        );
    }

    /**
     * @return int[]
     */
    private function getIdsToUpdate()
    {
        $state = $this->state->loadByView(static::INDEXER_ID);
        $fromVersionId = (int)$state->getVersionId();
        $this->changelog->setViewId(static::INDEXER_ID);
        $toVersionId = (int)$this->changelog->getVersion();

        try {
            $state->setVersionId($toVersionId);
            $state->save();
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf(
                    'Exception thrown in %s: %s',
                    __METHOD__,
                    $exception->getMessage()
                )
            );

            return [];
        }

        return $this->changelog->getList($fromVersionId, $toVersionId);
    }
}
