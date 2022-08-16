<?php

namespace Klevu\Search\Api\Service\Account;

interface GetStoresUsingApiKeysInterface
{
    /**
     * @param string $restApiKey
     * @param string  $jsApiKey
     *
     * @return string[][]
     */
    public function execute($restApiKey, $jsApiKey);
}
