<?php

namespace Klevu\Search\Controller\Adminhtml\Manual;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context as ActionContext;
use Klevu\Search\Model\Product\Sync as Klevu_ProductSync;
use Klevu\Search\Model\Session as Klevu_Session;

class Sync extends Action
{

    protected $_eventManager;

    /**
     * Construct
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Klevu\Search\Model\Product\Sync $klevuProductSync
     * @param \Klevu\Search\Model\Session $klevuSession
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

    public function execute()
    {
        //\Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Model\Product\Sync')->runManually();
        $this->_klevuProductSync->runManually();
        /* Use event For other content sync */
         //\Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Event\ManagerInterface')->dispatch('content_data_to_sync', []);
        $this->_eventManager->dispatch('content_data_to_sync', []);
        // \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Model\Session')->unsFirstSync();
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
