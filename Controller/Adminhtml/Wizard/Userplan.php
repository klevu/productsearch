<?php

namespace Klevu\Search\Controller\Adminhtml\Wizard;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Forward as ResultForward;
use Magento\Framework\Controller\ResultInterface;

class Userplan extends \Magento\Backend\App\Action
{
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
