<?php

namespace Klevu\Search\Controller\Adminhtml\Savetriggeroption;

use Klevu\Search\Helper\Config as Klevu_HelperConfig;
use Magento\Backend\App\Action\Context as ActionContext;

class option extends \Magento\Backend\App\Action
{
    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;

    /**
     * Construct
     *
     * @param ActionContext $context
     * @param Klevu_HelperConfig $searchHelperConfig
     */
    public function __construct(
        ActionContext $context,
        Klevu_HelperConfig $searchHelperConfig)
    {
        $this->_searchHelperConfig = $searchHelperConfig;
        parent::__construct($context);
    }

    public function execute() {
        $trigger_options = $this->getRequest()->getParam("trigger_options");
        $this->_searchHelperConfig->saveTriggerOptions($trigger_options);
    }
}
