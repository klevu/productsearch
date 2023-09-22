<?php

namespace Klevu\Search\Plugin\Mview;

use Closure;
use Exception;
use Klevu\Search\Model\Indexer\Sync\ProductSyncIndexer;
use Magento\Framework\Mview\View as MviewView;
use Magento\Framework\Mview\View\StateInterface;
use Psr\Log\LoggerInterface;

class View
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string[]
     */
    private $indexers;

    /**
     * @param LoggerInterface $logger
     * @param string[] $indexers
     */
    public function __construct(
        LoggerInterface $logger,
        array $indexers = []
    ) {
        $this->logger = $logger;
        $this->indexers = $indexers;
    }

    /**
     * @param MviewView $subject
     * @param Closure $proceed
     *
     * @return MviewView
     */
    public function aroundSuspend(MviewView $subject, Closure $proceed)
    {
        $originalState = $subject->getState();
        $originalVersion = $originalState->getVersionId();
        $result = $proceed();
        $state = $subject->getState();
        if ($state->getMode() === StateInterface::MODE_ENABLED &&
            in_array($state->getViewId(), $this->indexers, true)
        ) {
            try {
                $state->setVersionId($originalVersion);
                $state->save();
            } catch (Exception $exception) {
                $this->logger->error(
                    sprintf(
                        'Exception thrown in %s: %s',
                        __METHOD__,
                        $exception->getMessage()
                    )
                );
            }
        }

        return $result;
    }
}
