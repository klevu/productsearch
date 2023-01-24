<?php

namespace Klevu\Search\Service\Sync\Product;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Sync\Product\GetHistoryLengthInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class GetHistoryLength implements GetHistoryLengthInterface
{
    const DEFAULT_HISTORY_LENGTH = 1;
    const XML_PATH_PRODUCT_SYNC_HISTORY_LENGTH = 'klevu_search/product_sync/history_length';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @param int $storeId
     *
     * @return int
     */
    public function execute($storeId = null)
    {
        $return = null;
        try {
            $this->validateStore($storeId);
            $return = $this->scopeConfig->getValue(
                static::XML_PATH_PRODUCT_SYNC_HISTORY_LENGTH,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        } catch (InvalidArgumentException $e) {
            $this->logger->error(__('Exception: %1 - %2', __METHOD__, $e->getMessage()));
        } catch (NoSuchEntityException $e) {
            $this->logger->error(__('Exception: %1 - %2', __METHOD__, $e->getMessage()));
        }
        $isValueValid = $this->isConfigValueValid($return);
        if (!$isValueValid) {
            $this->logInvalidValue($return);
        }

        return $isValueValid
            ? (int)$return
            : static::DEFAULT_HISTORY_LENGTH;
    }

    /**
     * @param int $store
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     */
    private function validateStore($store)
    {
        if (is_bool($store) ||
            ($store !== null && !is_scalar($store) && !($store instanceof StoreInterface))
        ) {
            throw new InvalidArgumentException(__('Invalid Store Id'));
        }
        $this->storeManager->getStore($store);
    }

    /**
     * @param mixed $return
     *
     * @return bool
     */
    private function isConfigValueValid($return)
    {
        return is_numeric($return)
            && (int)$return == $return // intentionally used weak comparison to remove decimals
            && (int)$return > 0;
    }

    /**
     * @param mixed $return
     *
     * @return void
     */
    private function logInvalidValue($return)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $value = is_scalar($return) && !is_bool($return) ? $return : gettype($return);
        $this->logger->error(
            __(
                'Invalid value for product sync history length provided. Expected a positive integer, %1 provided',
                is_object($return) ? get_class($return) : $value
            )
        );
    }
}
