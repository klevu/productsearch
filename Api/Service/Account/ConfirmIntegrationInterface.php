<?php

namespace Klevu\Search\Api\Service\Account;

use Magento\Framework\Exception\NoSuchEntityException;

interface ConfirmIntegrationInterface
{
    /**
     * @param array $apiKeys
     * @param string $storeId
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws \InvalidArgumentException
     * @throws \Zend_Validate_Exception
     */
    public function execute(array $apiKeys, $storeId);
}
