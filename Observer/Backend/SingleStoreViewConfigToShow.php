<?php

namespace Klevu\Search\Observer\Backend;

use Klevu\Logger\Constants as LoggerConstants;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Klevu\Search\Model\Klevu\HelperManager as Klevu_HelperManager;

/**
 * @deprecated 3.7.2 - no longer required. Targeted config load rather than config save.
 * @see no direct alternative.
 */
class SingleStoreViewConfigToShow implements ObserverInterface
{
    /**
     * @var Klevu_HelperManager
     */
    private $_klevuHelperManager;
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var RequestInterface
     */
    private $_request;

    /**
     * @param Klevu_HelperManager $klevuHelperManager
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     */
    public function __construct(
        Klevu_HelperManager $klevuHelperManager,
        StoreManagerInterface $storeManager,
        RequestInterface $request
    ) {
        $this->_klevuHelperManager = $klevuHelperManager;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
    }

    /**
     * @param EventObserver $observer
     *
     * @return void
     * @deprecated 3.7.2 - no longer required. Targeted config load rather than config save.
     * @see no direct alternative. Changed adminhtml/system.xml fields to all have showInDefault="1"
     *       using group showInDefault to control visibility
     */
    public function execute(EventObserver $observer) //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedFunction
    {
    }
}
