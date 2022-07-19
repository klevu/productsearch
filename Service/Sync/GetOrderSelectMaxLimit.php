<?php

namespace Klevu\Search\Service\Sync;

use Klevu\Search\Api\Service\Sync\GetOrderSelectMaxLimitInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

class GetOrderSelectMaxLimit implements GetOrderSelectMaxLimitInterface
{
    const MAX_SYNC_SELECT_LIMIT = 1000;
    const XML_PATH_ORDER_SYNC_MAX_SELECT_LIMIT = 'klevu_search/order_sync/max_sync_select_limit';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param StoreInterface $store
     *
     * @return int
     */
    public function execute(StoreInterface $store)
    {
        $selectLimit = $this->scopeConfig->getValue(
            static::XML_PATH_ORDER_SYNC_MAX_SELECT_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        );

        return $selectLimit ? (int)$selectLimit : static::MAX_SYNC_SELECT_LIMIT;
    }
}
