<?php

namespace Klevu\Search\Controller\Adminhtml\Wizard;

use Magento\Framework\Controller\Result\Forward as ResultForward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Store extends \Magento\Backend\App\Action
{
    /**
     * @var \Klevu\Search\Model\Session
     */
    protected $_searchModelSession;

    public function __construct(\Magento\Backend\App\Action\Context $Context, \Klevu\Search\Model\Session $searchModelSession)
    {
        $this->_searchModelSession = $searchModelSession;

        parent::__construct($Context);
    }

    /**
     * Show 404 error page
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var ResultForward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        $resultForward->forward('noroute');

        return $resultForward;
    }
}
