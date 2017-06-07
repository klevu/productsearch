<?php

namespace Klevu\Search\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;

class PersonalData implements SectionSourceInterface
{

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        // show group price based on logged in customer group
        $customerSession = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Customer\Model\Session');
        //Check if User is Logged In
        if ($customerSession->isLoggedIn()) {
            return [
                'klevuSessionId' => md5(session_id()),
                'klevuLoginCustomerGroup' => $customerSession->getCustomer()->getGroupId(),
                'klevuIdCode' => "enc-".md5($customerSession->getCustomer()->getEmail()),
            ];
        } else {
            return [
                'klevuSessionId' => md5(session_id()),
            ];
        }
    }
}
