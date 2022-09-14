<?php

namespace Klevu\Search\Api\Service\Account;

use Klevu\Search\Api\Service\Account\Model\AccountFeaturesInterface;

interface GetFeaturesInterface
{
    /**
     * @param $store
     *
     * @return AccountFeaturesInterface|null
     */
    public function execute($store = null);
}
