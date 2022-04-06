<?php

namespace Klevu\Search\Service\Sync;

use Klevu\Search\Api\Service\Sync\GetBatchSizeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

class GetBatchSize implements GetBatchSizeInterface
{
    const DEFAULT_BATCH_SIZE = 100;
    const XML_PATH_PRODUCT_SYNC_BATCH_SIZE = 'klevu_search/product_sync/batch_size';

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
        $batchSize = $this->scopeConfig->getValue(
            static::XML_PATH_PRODUCT_SYNC_BATCH_SIZE,
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        );

        return $batchSize ? (int)$batchSize : static::DEFAULT_BATCH_SIZE;
    }
}
