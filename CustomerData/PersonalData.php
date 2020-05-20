<?php

namespace Klevu\Search\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Klevu\Search\Helper\Data as Klevu_HelperData;

/**
 * PersonalData Customer section
 */
class PersonalData implements SectionSourceInterface
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Klevu_HelperData
     */
    private $klevuHelperData;

    /**
     * @param CustomerSession $customerSession
     * @param Klevu_HelperData $klevuHelperData
     */
    public function __construct(
        CustomerSession $customerSession,
        Klevu_HelperData $klevuHelperData
    ) {
        $this->_customerSession = $customerSession;
        $this->_klevuHelperData = $klevuHelperData;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        // show group price based on logged in customer group
        //$customerSession = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Customer\Model\Session');
		//$searchHelperData = \Magento\Framework\App\ObjectManager::getInstance()->get('\Klevu\Search\Helper\Data');

        //Check if User is Logged In
        if ($this->_customerSession->isLoggedIn()) {
            return [
                'klevuSessionId' => md5(session_id()),
                'klevuLoginCustomerGroup' => $this->_customerSession->getCustomer()->getGroupId(),
                'klevuIdCode' => "enc-".md5($this->_customerSession->getCustomer()->getEmail()),
				'klevuShopperIP' => $this->_klevuHelperData->getIp()
            ];
        } else {
            return [
                'klevuSessionId' => md5(session_id()),
				'klevuShopperIP' => $this->_klevuHelperData->getIp()
            ];
        }
    }
}
