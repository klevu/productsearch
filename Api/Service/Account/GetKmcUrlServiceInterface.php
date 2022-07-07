<?php

namespace Klevu\Search\Api\Service\Account;

interface GetKmcUrlServiceInterface
{
    /**
     * @param string|int|null$storeId
     *
     * @return string
     */
    public function execute($storeId = null);
}
