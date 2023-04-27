<?php

namespace Klevu\Search\Controller\Adminhtml\Savesyncoption;

use Klevu\Search\Helper\Config as ConfigHelper;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class Option extends Action
{
    /**
     * @var ConfigHelper
     */
    protected $_searchHelperConfig;

    /**
     * @param Context $context
     * @param ConfigHelper $searchHelperConfig
     */
    public function __construct(Context $context, ConfigHelper $searchHelperConfig)
    {
        $this->_searchHelperConfig = $searchHelperConfig;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $sync_options = $this->getRequest()->getParam("sync_options");
        $this->_searchHelperConfig->saveSyncOptions($sync_options);
    }
}
