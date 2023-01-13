<?php

namespace Klevu\Search\Service\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Sync\Product\GetRecordsPerPageInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

class GetRecordsPerPage implements GetRecordsPerPageInterface
{
    const XML_PATH_PRODUCT_SYNC_RECORDS_PER_PAGE = "klevu_search/developer/product_sync_records_per_page";
    const PRODUCT_SYNC_RECORDS_PER_PAGE_DEFAULT = 100;
    const PRODUCT_SYNC_RECORDS_PER_PAGE_MIN = 1;
    const PRODUCT_SYNC_RECORDS_PER_PAGE_MAX = 500;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return int
     */
    public function execute($store = null)
    {
        $store = (null !== $store) ? $store : Store::DEFAULT_STORE_ID;
        $value = null;
        try {
            $this->validateStore($store);
            $value = $this->scopeConfig->getValue(
                static::XML_PATH_PRODUCT_SYNC_RECORDS_PER_PAGE,
                ScopeInterface::SCOPE_STORES,
                $store
            );
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf('Exception thrown in %s: %s', __METHOD__, $exception->getMessage())
            );
        }

        return $this->isReturnValueValid($value)
            ? (int)$value
            : self::PRODUCT_SYNC_RECORDS_PER_PAGE_DEFAULT;
    }

    /**
     * @param StoreInterface|string|int|null $store
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateStore($store = null)
    {
        $valid = true;
        if (is_object($store)) {
            if (!($store instanceof StoreInterface)) {
                $valid = false;
            }
        } elseif (!is_scalar($store)) {
            $valid = false;
        } elseif (is_bool($store)) {
            $valid = false;
        } elseif (is_numeric($store) && (int)$store != $store) { // weak comparison to remove none integers
            $valid = false;
        }

        if (!$valid) {
            throw new InvalidArgumentException(
                __('Incorrect store value passed, revert to default value for records per page.')
            );
        }
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private function isReturnValueValid($value)
    {
        return is_numeric($value)
            && ((int)$value == $value) // weak comparison intentionally used here to remove none integers
            && ($value >= self::PRODUCT_SYNC_RECORDS_PER_PAGE_MIN)
            && ($value <= self::PRODUCT_SYNC_RECORDS_PER_PAGE_MAX);
    }
}
