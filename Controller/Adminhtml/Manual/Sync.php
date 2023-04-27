<?php

namespace Klevu\Search\Controller\Adminhtml\Manual;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context as ActionContext;
use Klevu\Search\Model\Product\Sync as Klevu_ProductSync;
use Klevu\Search\Model\Session as Klevu_Session;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

class Sync extends Action
{
    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;
    /**
     * @var Klevu_ProductSync
     */
    protected $_klevuProductSync;
    /**
     * @var Klevu_Session
     */
    protected $_klevuSession;

    /**
     * Construct
     *
     * @param ActionContext $context
     * @param Klevu_ProductSync $klevuProductSync
     * @param Klevu_Session $klevuSession
     */
    public function __construct(
        ActionContext $context,
        Klevu_ProductSync $klevuProductSync,
        Klevu_Session $klevuSession
    ) {
        $this->_eventManager = $context->getEventManager();
        $this->_klevuProductSync = $klevuProductSync;
        $this->_klevuSession = $klevuSession;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $this->_klevuProductSync->runManually();
        /* Use event For other content sync */
        $this->_eventManager->dispatch('content_data_to_sync', []);
        $this->_klevuSession->unsFirstSync();
        return $this->_redirect($this->_redirect->getRefererUrl());
    }
    
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
