<?php

namespace Klevu\Search\Api\Service\Account;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Account\Model\AccountDetailsInterface;
use Klevu\Search\Exception\InvalidApiResponseException;

interface GetAccountDetailsInterface
{
    /**
     * @param array $apiKeys
     * @param int $storeId
     *
     * @return AccountDetailsInterface
     * @throws InvalidArgumentException
     * @throws \Zend_Validate_Exception
     * @throws InvalidApiResponseException
     */
    public function execute(array $apiKeys, $storeId);
}
