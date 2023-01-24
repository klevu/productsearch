<?php

namespace Klevu\Search\Service\Convertor;

use InvalidArgumentException;
use Klevu\Search\Api\Service\Convertor\Sync\Order\ItemDataConvertorInterface;
use Klevu\Search\Provider\Sync\Order\Item\Type\DefaultDataProvider;
use Magento\Sales\Api\Data\OrderItemInterface;

class OrderSyncItemDataConvertor implements ItemDataConvertorInterface
{
    /**
     * @var array
     */
    private $requiredFields = [
        'klevu_type' => DefaultDataProvider::TYPE,
        'klevu_productId' => DefaultDataProvider::PRODUCT_ID,
        'klevu_productGroupId' => DefaultDataProvider::PRODUCT_GROUP_ID,
        'klevu_productVariantId' => DefaultDataProvider::PRODUCT_VARIANT_ID,
        'klevu_unit' => DefaultDataProvider::UNIT,
        'klevu_salePrice' => DefaultDataProvider::UNIT_PRICE,
        'klevu_currency' => DefaultDataProvider::CURRENCY,
    ];
    /**
     * @var array
     */
    private $optionsFields = [
        'klevu_productPosition' => DefaultDataProvider::PRODUCT_POSITION,
        'klevu_orderId' => DefaultDataProvider::ORDER_ID,
        'klevu_orderLineId' => DefaultDataProvider::ORDER_ITEM_ID,
        'klevu_storeTimezone' => DefaultDataProvider::STORE_TIMEZONE,
        'klevu_orderDate' => DefaultDataProvider::ORDER_DATE,
        'klevu_checkoutDate' => DefaultDataProvider::CHECKOUT_DATE,
        'klevu_emailId' => DefaultDataProvider::EMAIL_ID,
        'klevu_sessionId' => DefaultDataProvider::SESSION_ID,
        'klevu_clientIp' => DefaultDataProvider::CLIENT_IP,
    ];

    /**
     * @param array $orderItem
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function convert(array $orderItem)
    {
        $this->validate($orderItem);

        $return = [];
        foreach ($this->requiredFields as $key => $requiredField) {
            $return[$key] = $orderItem[$requiredField];
        }
        foreach ($this->optionsFields as $key => $optionsField) {
            if (array_key_exists($optionsField, $orderItem)) {
                $return[$key] = $orderItem[$optionsField];
            }
        }

        return $return;
    }

    /**
     * @param array $orderItem
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validate(array $orderItem)
    {
        foreach ($this->requiredFields as $requiredField) {
            if (!isset($orderItem[$requiredField])) {
                throw new InvalidArgumentException(
                    __('Required Order Item field %1 missing from analytics data', $requiredField)
                );
            }
        }
    }
}
