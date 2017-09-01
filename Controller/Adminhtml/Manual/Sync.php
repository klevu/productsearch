<?php

namespace Klevu\Search\Controller\Adminhtml\Manual;

class Sync extends \Magento\Backend\App\Action
{
	
	/**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Model\Product\Sync')->runManually();
        /* Use event For other content sync */
         \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\Event\ManagerInterface')->dispatch('content_data_to_sync', []);
         \Magento\Framework\App\ObjectManager::getInstance()->get('Klevu\Search\Model\Session')->unsFirstSync();
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
