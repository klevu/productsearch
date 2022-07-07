<?php

namespace Klevu\Search\Api\Service\Account;

use Klevu\Search\Api\Service\Account\Model\AccountDetailsInterface;
use Magento\Framework\Exception\NoSuchEntityException;

interface UpdateEndpointsInterface
{
    /**
     * @param AccountDetailsInterface $accountDetails
     * @param string|int $storeId
     *
     * @return void
     * @throws \Zend_Validate_Exception|NoSuchEntityException
     */
    public function execute(AccountDetailsInterface $accountDetails, $storeId);
}
