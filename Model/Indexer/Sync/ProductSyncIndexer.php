<?php

namespace Klevu\Search\Model\Indexer\Sync;

use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Mview\View\StateInterface;
use Psr\Log\LoggerInterface;

class ProductSyncIndexer implements IndexerActionInterface, MviewActionInterface
{
    const RECORD_TYPE_PRODUCTS = 'products';
    const INDEXER_ID = 'klevu_product_sync';

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
        $id = $this->getIdsToUpdate();
        $this->executeAction($id);
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
     * @param array $ids
     *
     * @return void
     */
    private function executeAction(array $ids)
    {
        $id = array_filter($ids, static function ($id) {
            return is_numeric($id) && (int)$id == $id; // intentionally used weak comparison
        });
        if (!$id) {
            return;
        }

        $this->magentoProductActions->markRecordIntoQueue(
            $id,
            self::RECORD_TYPE_PRODUCTS,
            null
        );
    }

    /**
     * @return int[]
     */
    private function getIdsToUpdate()
    {
        $state = $this->state->loadByView(self::INDEXER_ID);
        $fromVersionId = (int)$state->getVersionId();
        $this->changelog->setViewId(self::INDEXER_ID);
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
