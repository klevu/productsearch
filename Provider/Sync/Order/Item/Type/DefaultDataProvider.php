<?php

namespace Klevu\Search\Provider\Sync\Order\Item\Type;

use Klevu\Search\Api\Provider\Sync\Order\Item\DataProviderInterface as OrderItemDataProviderInterface;
use Klevu\Search\Helper\Data as SearchHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemExtension;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class DefaultDataProvider implements OrderItemDataProviderInterface
{
    const DATA_TYPE = "checkout";
    const FIELD_KLEVU_SESSION_ID = 'klevu_session_id';
    const FIELD_KLEVU_EMAIL_ID = 'idcode';
    const FIELD_KLEVU_IP_ADDRESS = 'ip_address';
    const FIELD_CHECKOUT_DATE = 'checkoutdate';
    const FIELD_ORDER_DATE = 'date';
    const CHECKOUT_DATE = "checkoutDate";
    const CLIENT_IP = "clientIp";
    const CURRENCY = "currency";
    const EMAIL_ID = "emailId";
    const ORDER_ID = "orderId";
    const ORDER_ITEM_ID = "orderItemId";
    const ORDER_DATE = "orderDate";
    const PRODUCT_POSITION = "productPosition";
    const PRODUCT_NAME = "productName";
    const PRODUCT_ID = "productId";
    const PRODUCT_GROUP_ID = "productGroupId";
    const PRODUCT_VARIANT_ID = "productVariantId";
    const SESSION_ID = "sessionId";
    const STORE_TIMEZONE = "storeTimezone";
    const TYPE = "type";
    const UNIT = "unit";
    const UNIT_PRICE = "unitPrice";

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;
    /**
     * @var SearchHelper
     */
    private $searchHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @param PriceCurrencyInterface $priceCurrency
     * @param SearchHelper $searchHelper
     * @param StoreManagerInterface|null $storeManager
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        SearchHelper $searchHelper,
        StoreManagerInterface $storeManager = null,
        LoggerInterface $logger = null
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->searchHelper = $searchHelper;
        $objectManager = ObjectManager::getInstance();
        $this->storeManager = $storeManager
            ?: $objectManager->get(StoreManagerInterface::class);
        $this->logger = $logger
            ?: $objectManager->get(LoggerInterface::class);
    }

    /**
     * @param OrderItemInterface $orderItem
     *
     * @return array
     */
    public function getData(OrderItemInterface $orderItem)
    {
        /** @var OrderItemExtension $extensionAttributes */
        $extensionAttributes = $orderItem->getExtensionAttributes();
        $klevuSyncData = $extensionAttributes
            ? $extensionAttributes->getKlevuOrderSync()
            : [];

        /** @var OrderInterface $order */
        $order = $orderItem->getOrder();
        $unitPrice = method_exists($this->priceCurrency, 'roundPrice')
            ? $this->priceCurrency->roundPrice($orderItem->getBasePriceInclTax())
            : $this->priceCurrency->round($orderItem->getBasePriceInclTax());

        $orderDate = !empty($klevuSyncData[self::FIELD_ORDER_DATE])
            ? date_format(date_create($klevuSyncData[self::FIELD_ORDER_DATE]), "Y-m-d")
            : '';
        $checkoutDate = isset($klevuSyncData[self::FIELD_CHECKOUT_DATE])
            ? $klevuSyncData[self::FIELD_CHECKOUT_DATE]
            : null;
        $sessionId = isset($klevuSyncData[static::FIELD_KLEVU_SESSION_ID])
            ? $klevuSyncData[static::FIELD_KLEVU_SESSION_ID]
            : null;
        $emailId = isset($klevuSyncData[static::FIELD_KLEVU_EMAIL_ID])
            ? $klevuSyncData[static::FIELD_KLEVU_EMAIL_ID]
            : null;
        $clientIp = isset($klevuSyncData[static::FIELD_KLEVU_IP_ADDRESS])
            ? $klevuSyncData[static::FIELD_KLEVU_IP_ADDRESS]
            : null;

        return [
            static::ORDER_ID => $order->getId(),
            static::ORDER_ITEM_ID => $orderItem->getId(),
            static::TYPE => static::DATA_TYPE,
            static::PRODUCT_POSITION => "1",
            static::PRODUCT_NAME => $orderItem->getName(),
            static::PRODUCT_ID => $orderItem->getProductId(),
            static::PRODUCT_GROUP_ID => $orderItem->getProductId(),
            static::PRODUCT_VARIANT_ID => $orderItem->getProductId(),
            static::UNIT => $orderItem->getQtyOrdered(),
            static::UNIT_PRICE => $unitPrice,
            static::CURRENCY => $this->getCurrencyCode($order),
            static::STORE_TIMEZONE => $this->searchHelper->getStoreTimeZone($orderItem->getStoreId()),
            static::ORDER_DATE => $orderDate,
            static::CHECKOUT_DATE => $checkoutDate,
            static::SESSION_ID => $sessionId,
            static::EMAIL_ID => $emailId,
            static::CLIENT_IP => $clientIp,
        ];
    }

    /**
     * @param OrderInterface $order
     *
     * @return string
     */
    private function getCurrencyCode(OrderInterface $order)
    {
        $baseCurrency = $order->getBaseCurrencyCode();
        if ($baseCurrency) {
            return $baseCurrency;
        }
        $store = $this->getStore($order);

        return $store
            ? $store->getBaseCurrencyCode()
            : '';
    }

    /**
     * @param OrderInterface $order
     *
     * @return StoreInterface|null
     */
    private function getStore(OrderInterface $order)
    {
        $store = method_exists($order, 'getStore')
            ? $order->getStore()
            : null;
        if (null === $store) {
            try {
                $store = $this->storeManager->getStore($order->getStoreId());
            } catch (NoSuchEntityException $exception) {
                $this->logger->error(
                    sprintf('Method: %s - Error: %s', __METHOD__, $exception->getMessage())
                );
            }
        }

        return $store;
    }
}
