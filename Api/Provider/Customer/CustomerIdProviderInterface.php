<?php

namespace Klevu\Search\Api\Provider\Customer;

interface CustomerIdProviderInterface
{
    /**
     * @param string $email
     *
     * @return string
     */
    public function execute($email);
}
