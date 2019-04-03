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
		$searchHelperData = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Helper\Data');
        //Check if User is Logged In
        if ($customerSession->isLoggedIn()) {
            return [
                'klevuSessionId' => md5(session_id()),
                'klevuLoginCustomerGroup' => $customerSession->getCustomer()->getGroupId(),
                'klevuIdCode' => "enc-".md5($customerSession->getCustomer()->getEmail()),
				'klevuShopperIP' => $searchHelperData->getIp()
            ];
        } else {
            return [
                'klevuSessionId' => md5(session_id()),
				'klevuShopperIP' => $searchHelperData->getIp()
            ];
        }
    }
}
