<?php

namespace Klevu\Search\Api\Service\Account\KlevuApi;

interface GetAccountDetailsInterface
{
    /**
     * @param array $apiKeys
     * @param int $storeId
     *
     * @return array
     */
    public function execute(array $apiKeys, $storeId);
}
