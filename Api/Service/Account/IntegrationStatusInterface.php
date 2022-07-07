<?php

namespace Klevu\Search\Api\Service\Account;

use Magento\Store\Api\Data\StoreInterface;

interface IntegrationStatusInterface
{
    /**
     * @return bool
     */
    public function isJustIntegrated();

    /**
     * @param StoreInterface $store
     *
     * @return void
     */
    public function setJustIntegrated(StoreInterface $store);

    /**
     * @param StoreInterface $store
     *
     * @return bool
     */
    public function isIntegrated(StoreInterface $store);

    /**
     * @param StoreInterface $store
     *
     * @return void
     */
    public function setIntegrated(StoreInterface $store);
}
