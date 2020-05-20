<?php

namespace Klevu\Search\Controller\Adminhtml\Trigger;

use Klevu\Search\Helper\Data;


class All extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeModelStoreManagerInterface;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $_backendModelSession;

    /**
     * @var \Klevu\Search\Helper\Config
     */
    protected $_searchHelperConfig;

    /**
     * @var \Klevu\Search\Helper\Data
     */
    protected $_searchHelperData;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_frameworkEventManagerInterface;

    /**
     * Trigger All constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface
     * @param \Klevu\Search\Helper\Config $searchHelperConfig
     * @param Data $searchHelperData
     * @param \Klevu\Search\Model\Trigger $klevuModelTrigger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeModelStoreManagerInterface,
        \Klevu\Search\Helper\Config $searchHelperConfig,
        \Klevu\Search\Helper\Data $searchHelperData,
        \Klevu\Search\Model\Trigger $klevuModelTrigger
    )
    {
        $this->_storeModelStoreManagerInterface = $storeModelStoreManagerInterface;
        $this->_backendModelSession = $context->getSession();
        $this->_searchHelperConfig = $searchHelperConfig;
        $this->_frameworkEventManagerInterface = $context->getEventManager();
        $this->_klevuModelTrigger = $klevuModelTrigger;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            if ($this->_searchHelperConfig->getTriggerOptionsFlag() == "1") {
                /** @var \Klevu\Search\Model\Trigger */
                $this->_klevuModelTrigger->activateTrigger();
                $this->messageManager->addSuccessMessage(__("Trigger is activated."));
            } else {
                $this->_klevuModelTrigger->dropTriggerIfFoundExist();
                $this->messageManager->addSuccessMessage(__("Trigger is deactivated."));
            }
        } catch (Exception $e) {
            $this->messageManager->addSuccessMessage('Trigger Operation failed.' . $e->getMessage());
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }

    protected function _isAllowed()
    {
        return true;
    }


}
