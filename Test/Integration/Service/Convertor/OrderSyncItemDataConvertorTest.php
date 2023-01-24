<?php

namespace Klevu\Search\Test\Integration\Service\Convertor;

use Klevu\Search\Provider\Sync\Order\Item\Type\DefaultDataProvider;
use Klevu\Search\Service\Convertor\OrderSyncItemDataConvertor;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class OrderSyncItemDataConvertorTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @dataProvider missingRequiredFieldsDataProvider
     */
    public function testThrowsExceptionIfRequiredDataMissing($field)
    {
        $this->setUpPhp5();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('Required Order Item field %s missing from analytics data', $field)
        );

        $orderItem = [
            DefaultDataProvider::TYPE => DefaultDataProvider::DATA_TYPE,
            DefaultDataProvider::PRODUCT_POSITION => '1',
            DefaultDataProvider::ORDER_ID => '1',
            DefaultDataProvider::ORDER_ITEM_ID => '2',
            DefaultDataProvider::PRODUCT_ID => '4-3',
            DefaultDataProvider::PRODUCT_GROUP_ID => '4',
            DefaultDataProvider::PRODUCT_VARIANT_ID => '3',
            DefaultDataProvider::UNIT => '1.000',
            DefaultDataProvider::UNIT_PRICE => '1234.56',
            DefaultDataProvider::CURRENCY => 'USD',
            DefaultDataProvider::STORE_TIMEZONE => 'Europe/London',
            DefaultDataProvider::ORDER_DATE => date_format(date_create(''), "Y-m-d"),
            DefaultDataProvider::CHECKOUT_DATE => time(),
            DefaultDataProvider::EMAIL_ID => 'oiwefunwiuefn',
            DefaultDataProvider::SESSION_ID => 'sufgnifgnrg',
            DefaultDataProvider::CLIENT_IP => '127.0.0.1',
        ];
        unset($orderItem[$field]);

        $convertor = $this->instantiateConvertor();
        $actual = $convertor->convert($orderItem);
        $expected = [];

        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider missingOptionalFieldsDataProvider
     */
    public function testReturnsDataIfOptionalDataMissing($optionalField)
    {
        $this->setUpPhp5();

        $keys = array_keys($optionalField);
        $optionalFieldKey = $keys[0];
        $field = $optionalField[$optionalFieldKey];

        $orderItem = [
            DefaultDataProvider::TYPE => DefaultDataProvider::DATA_TYPE,
            DefaultDataProvider::PRODUCT_POSITION => '1',
            DefaultDataProvider::ORDER_ID => '1',
            DefaultDataProvider::ORDER_ITEM_ID => '2',
            DefaultDataProvider::PRODUCT_ID => '4-3',
            DefaultDataProvider::PRODUCT_GROUP_ID => '4',
            DefaultDataProvider::PRODUCT_VARIANT_ID => '3',
            DefaultDataProvider::UNIT => '1.000',
            DefaultDataProvider::UNIT_PRICE => '1234.56',
            DefaultDataProvider::CURRENCY => 'USD',
            DefaultDataProvider::STORE_TIMEZONE => 'Europe/London',
            DefaultDataProvider::ORDER_DATE => date_format(date_create(''), "Y-m-d"),
            DefaultDataProvider::CHECKOUT_DATE => time(),
            DefaultDataProvider::EMAIL_ID => 'oiwefunwiuefn',
            DefaultDataProvider::SESSION_ID => 'sufgnifgnrg',
            DefaultDataProvider::CLIENT_IP => '127.0.0.1',
        ];

        $expected = [
            'klevu_type' => $orderItem[DefaultDataProvider::TYPE],
            'klevu_productId' => $orderItem[DefaultDataProvider::PRODUCT_ID],
            'klevu_productGroupId' => $orderItem[DefaultDataProvider::PRODUCT_GROUP_ID],
            'klevu_productVariantId' => $orderItem[DefaultDataProvider::PRODUCT_VARIANT_ID],
            'klevu_unit' => $orderItem[DefaultDataProvider::UNIT],
            'klevu_salePrice' => $orderItem[DefaultDataProvider::UNIT_PRICE],
            'klevu_currency' => $orderItem[DefaultDataProvider::CURRENCY],
            'klevu_productPosition' => $orderItem[DefaultDataProvider::PRODUCT_POSITION],
            'klevu_orderId' => $orderItem[DefaultDataProvider::ORDER_ID],
            'klevu_orderLineId' => $orderItem[DefaultDataProvider::ORDER_ITEM_ID],
            'klevu_storeTimezone' => $orderItem[DefaultDataProvider::STORE_TIMEZONE],
            'klevu_orderDate' => $orderItem[DefaultDataProvider::ORDER_DATE],
            'klevu_checkoutDate' => $orderItem[DefaultDataProvider::CHECKOUT_DATE],
            'klevu_emailId' => $orderItem[DefaultDataProvider::EMAIL_ID],
            'klevu_sessionId' => $orderItem[DefaultDataProvider::SESSION_ID],
            'klevu_clientIp' => $orderItem[DefaultDataProvider::CLIENT_IP],
        ];

        unset($orderItem[$field]);

        $expected = array_filter($expected, static function ($key) use ($optionalFieldKey) {
            return $optionalFieldKey !== $key;
        }, ARRAY_FILTER_USE_KEY);

        $convertor = $this->instantiateConvertor();
        $actual = $convertor->convert($orderItem);

        $this->assertSame($expected, $actual);
    }

    public function testReturnsData()
    {
        $this->setUpPhp5();

        $orderItem = [
            DefaultDataProvider::TYPE => DefaultDataProvider::DATA_TYPE,
            DefaultDataProvider::PRODUCT_POSITION => '1',
            DefaultDataProvider::ORDER_ID => '1',
            DefaultDataProvider::ORDER_ITEM_ID => '2',
            DefaultDataProvider::PRODUCT_ID => '4-3',
            DefaultDataProvider::PRODUCT_GROUP_ID => '4',
            DefaultDataProvider::PRODUCT_VARIANT_ID => '3',
            DefaultDataProvider::UNIT => '1.000',
            DefaultDataProvider::UNIT_PRICE => '1234.56',
            DefaultDataProvider::CURRENCY => 'USD',
            DefaultDataProvider::STORE_TIMEZONE => 'Europe/London',
            DefaultDataProvider::ORDER_DATE => date_format(date_create(''), "Y-m-d"),
            DefaultDataProvider::CHECKOUT_DATE => time(),
            DefaultDataProvider::EMAIL_ID => 'oiwefunwiuefn',
            DefaultDataProvider::SESSION_ID => 'sufgnifgnrg',
            DefaultDataProvider::CLIENT_IP => '127.0.0.1',
        ];

        $convertor = $this->instantiateConvertor();
        $actual = $convertor->convert($orderItem);
        $expected = [
            'klevu_type' => $orderItem[DefaultDataProvider::TYPE],
            'klevu_productId' => $orderItem[DefaultDataProvider::PRODUCT_ID],
            'klevu_productGroupId' => $orderItem[DefaultDataProvider::PRODUCT_GROUP_ID],
            'klevu_productVariantId' => $orderItem[DefaultDataProvider::PRODUCT_VARIANT_ID],
            'klevu_unit' => $orderItem[DefaultDataProvider::UNIT],
            'klevu_salePrice' => $orderItem[DefaultDataProvider::UNIT_PRICE],
            'klevu_currency' => $orderItem[DefaultDataProvider::CURRENCY],
            'klevu_productPosition' => $orderItem[DefaultDataProvider::PRODUCT_POSITION],
            'klevu_orderId' => $orderItem[DefaultDataProvider::ORDER_ID],
            'klevu_orderLineId' => $orderItem[DefaultDataProvider::ORDER_ITEM_ID],
            'klevu_storeTimezone' => $orderItem[DefaultDataProvider::STORE_TIMEZONE],
            'klevu_orderDate' => $orderItem[DefaultDataProvider::ORDER_DATE],
            'klevu_checkoutDate' => $orderItem[DefaultDataProvider::CHECKOUT_DATE],
            'klevu_emailId' => $orderItem[DefaultDataProvider::EMAIL_ID],
            'klevu_sessionId' => $orderItem[DefaultDataProvider::SESSION_ID],
            'klevu_clientIp' => $orderItem[DefaultDataProvider::CLIENT_IP],
        ];

        $this->assertSame($expected, $actual);
    }

    /**
     * @return array[]
     */
    public function missingRequiredFieldsDataProvider()
    {
        return [
            [DefaultDataProvider::TYPE],
            [DefaultDataProvider::PRODUCT_ID],
            [DefaultDataProvider::PRODUCT_GROUP_ID],
            [DefaultDataProvider::PRODUCT_VARIANT_ID],
            [DefaultDataProvider::UNIT],
            [DefaultDataProvider::UNIT_PRICE],
            [DefaultDataProvider::CURRENCY],
        ];
    }

    /**
     * @return array[]
     */
    public function missingOptionalFieldsDataProvider()
    {
        return [
            [['klevu_productPosition' => DefaultDataProvider::PRODUCT_POSITION]],
            [['klevu_orderId' => DefaultDataProvider::ORDER_ID]],
            [['klevu_orderLineId' => DefaultDataProvider::ORDER_ITEM_ID]],
            [['klevu_storeTimezone' => DefaultDataProvider::STORE_TIMEZONE]],
            [['klevu_orderDate' => DefaultDataProvider::ORDER_DATE]],
            [['klevu_checkoutDate' => DefaultDataProvider::CHECKOUT_DATE]],
            [['klevu_emailId' => DefaultDataProvider::EMAIL_ID]],
            [['klevu_sessionId' => DefaultDataProvider::SESSION_ID]],
            [['klevu_clientIp' => DefaultDataProvider::CLIENT_IP]],
        ];
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @return OrderSyncItemDataConvertor|mixed
     */
    private function instantiateConvertor()
    {
        return $this->objectManager->get(OrderSyncItemDataConvertor::class);
    }
}
