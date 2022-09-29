<?php

namespace Klevu\Search\Api\Provider\Customer;

interface SessionIdProviderInterface
{
    /**
     * @return string
     */
    public function execute();
}
