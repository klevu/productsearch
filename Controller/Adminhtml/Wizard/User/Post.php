<?php

namespace Klevu\Search\Controller\Adminhtml\Wizard\User;

use Magento\Framework\Controller\Result\Forward as ResultForward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Post extends \Magento\Backend\App\Action
{
    /**
     * @var \Klevu\Search\Helper\Api
     */
    protected $_searchHelperApi;

    /**
     * @var \Klevu\Search\Model\Session
     */
    protected $_searchModelSession;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Klevu\Search\Helper\Api $searchHelperApi
    ) {
        $this->_searchHelperApi = $searchHelperApi;
        $this->_searchModelSession = $context->getSession();
        parent::__construct($context);
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
