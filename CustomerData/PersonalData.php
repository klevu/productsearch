<?php

namespace Klevu\Search\CustomerData;

use Klevu\FrontendJs\Api\CustomerDataProviderInterface;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Klevu\Search\Helper\Data as Klevu_HelperData;
use Magento\Framework\App\ObjectManager;


/**
 * PersonalData Customer section
 */
class PersonalData implements SectionSourceInterface
{
    const KEY_SESSION_ID = 'klevuSessionId';
    const KEY_CUSTOMER_GROUP = 'klevuLoginCustomerGroup';
    const KEY_ID_CODE = 'klevuIdCode';
    const KEY_SHOPPER_IP = 'klevuShopperIp';

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var CustomerDataProviderInterface
     */
    private $customerDataProvider;

    /**
     * @var Klevu_HelperData
     */
    protected $_klevuHelperData;

    /**
     * @param CustomerSession $customerSession
     * @param Klevu_HelperData $klevuHelperData
     * @param CustomerDataProviderInterface|null $customerDataProvider
     */
    public function __construct(
        CustomerSession $customerSession,
        Klevu_HelperData $klevuHelperData,
        CustomerDataProviderInterface $customerDataProvider = null
    ) {
        $this->_customerSession = $customerSession;
        $this->_klevuHelperData = $klevuHelperData;

        $this->customerDataProvider = $customerDataProvider ?: ObjectManager::getInstance()->get(
            CustomerDataProviderInterface::class
        );
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function getSectionData()
    {
        $customerData = $this->customerDataProvider->execute();

        $return = [
            static::KEY_SESSION_ID => $customerData->getSessionId(),
            static::KEY_SHOPPER_IP => $customerData->getShopperIp(),
        ];
        if (null !== $customerData->getCustomerGroupId()) {
            $return[static::KEY_CUSTOMER_GROUP] = $customerData->getCustomerGroupId();
        }
        if (null !== $customerData->getIdCode()) {
            $return[static::KEY_ID_CODE] = $customerData->getIdCode();
        }

        return $return;
    }
}
