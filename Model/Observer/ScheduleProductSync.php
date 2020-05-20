<?php

/**
 * Class \Klevu\Search\Model\Observer
 *
 */

namespace Klevu\Search\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

class ScheduleProductSync implements ObserverInterface
{

    /**
     * @var \Klevu\Search\Model\Product\Sync
     */
    protected $_modelProductSync;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_magentoFrameworkFilesystem;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $_modelProductAction;

    public function __construct(
        \Klevu\Search\Model\Product\Sync $modelProductSync,
        \Magento\Framework\Filesystem $magentoFrameworkFilesystem,
        \Klevu\Search\Helper\Data $searchHelperData,
        \Klevu\Search\Helper\Config $searchHelperConfig
    )
    {

        $this->_modelProductSync = $modelProductSync;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_searchHelperData = $searchHelperData;
        $this->_searchHelperConfig = $searchHelperConfig;
    }

    /**
     * Schedule a Product Sync to run immediately.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_searchHelperConfig->isExternalCronEnabled()) {
            $this->_modelProductSync->schedule();
        }
    }
}
