<?php

namespace Klevu\Search\Controller\Adminhtml\Wizard\Userplan;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Post extends \Magento\Backend\App\Action
{
    /**
     * @var \Klevu\Search\Model\Session
     */
    protected $_searchModelSession;

    /**
     * @var \Klevu\Search\Helper\Api
     */
    protected $_searchHelperApi;

    /**
     * @var \Magento\Framework\Model\Session
     */
    protected $_frameworkModelSession;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Klevu\Search\Helper\Api $searchHelperApi
    ) {
        $this->_searchHelperApi = $searchHelperApi;
        $this->_searchModelSession = $context->getSession();

        parent::__construct($context);
    }

    public function execute()
    {
        /* if partner account selected as UserPlan then change plan to trial*/
        $partnerAccount = false;
        $request = $this->getRequest();
        $session = $this->_searchModelSession;
        $userPlan = $request->getPost("userPlan");
        if ($userPlan=="partnerAccount") {
            $partnerAccount = true;
        }

        if (empty($userPlan)) {
            $this->messageManager->addErrorMessage(__("Not sure, which plan to select? Select Premium to try all features free for 14-days."));
            return $this->_forward("userplan");
        }

        $store = null;
        if ($request->getPost('store_id')) {
            try {
                /** @var StoreManagerInterface $storeManager */
                $storeManager = ObjectManager::getInstance()->get(StoreManagerInterface::class);
                $store = $storeManager->getStore($request->getPost('store_id'));
            } catch (NoSuchEntityException $e) {
                // Let this default to null
            }
        }

        $api = $this->_searchHelperApi;
        $result = $api->createUser(
            $this->_searchModelSession->getKlevuNewEmail(),
            $this->_searchModelSession->getKlevuNewPassword(),
            $userPlan,
            $partnerAccount,
            $this->_searchModelSession->getKlevuNewUrl(),
            $this->_searchModelSession->getMerchantEmail(),
            $this->_searchModelSession->getContactNo(),
            $store
        );

        if ($result["success"]) {
            $this->_searchModelSession->setConfiguredCustomerId($result["customer_id"]);
            if (isset($result["message"])) {
                $this->messageManager->addSuccessMessage(__($result["message"]));
            }
            return $this->_forward("store");
        } else {
            $this->messageManager->addErrorMessage(__($result["message"]));
            return $this->_forward("userplan");
        }
        return $this->_forward("store");
    }
}
