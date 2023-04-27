<?php

namespace Klevu\Search\Model\Observer;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Helper\Data as SearchHelper;
use Klevu\Search\Model\Product\Sync as ProductSync;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem;

class ScheduleProductSync implements ObserverInterface
{
    /**
     * @var ProductSync
     */
    protected $_modelProductSync;
    /**
     * @var Filesystem
     */
    protected $_magentoFrameworkFilesystem;
    /**
     * @var SearchHelper
     */
    protected $_searchHelperData;
    /**
     * @var ProductAction
     */
    protected $_modelProductAction;
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;

    /**
     * @param ProductSync $modelProductSync
     * @param Filesystem $magentoFrameworkFilesystem
     * @param SearchHelper $searchHelperData
     * @param ConfigHelper $searchHelperConfig
     */
    public function __construct(
        ProductSync $modelProductSync,
        Filesystem $magentoFrameworkFilesystem,
        SearchHelper $searchHelperData,
        ConfigHelper $searchHelperConfig
    ) {
        $this->_modelProductSync = $modelProductSync;
        $this->_magentoFrameworkFilesystem = $magentoFrameworkFilesystem;
        $this->_searchHelperData = $searchHelperData;
        $this->_searchHelperConfig = $searchHelperConfig;
    }

    /**
     * Schedule a Product Sync to run immediately.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->_searchHelperConfig->isExternalCronEnabled()) {
            $this->_modelProductSync->schedule();
        }
    }
}
