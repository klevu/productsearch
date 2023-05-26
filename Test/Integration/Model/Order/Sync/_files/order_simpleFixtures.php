<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$defaultAddressFixture = [
    'region' => 'CA',
    'region_id' => '12',
    'postcode' => '11111',
    'lastname' => 'lastname',
    'firstname' => 'firstname',
    'street' => 'street',
    'city' => 'Los Angeles',
    'email' => 'customer@klevu.com',
    'telephone' => '11111111',
    'country_id' => 'US'
];

/** @var ScopeConfigInterface $scopeConfig */
$scopeConfig = $objectManager->get(ScopeConfigInterface::class);

/** @var Store $store */
$store = $objectManager->get(StoreManagerInterface::class)->getStore('klevu_test_store_1');
$baseCurrency = $scopeConfig->getValue(
    'currency/options/base',
    ScopeInterface::SCOPE_STORES,
    $store->getId()
);
$orderCurrency = $scopeConfig->getValue(
    'currency/options/default',
    ScopeInterface::SCOPE_STORES,
    $store->getId()
);
$priceCurrencyFactory = $objectManager->get(CurrencyFactory::class);
$priceCurrency = $priceCurrencyFactory->create();
$exchangeRate = $priceCurrency->load($baseCurrency)->getAnyRate($orderCurrency);

$orderTotal = 0;

$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$product = $productRepository->get('klevu_simple_1');

/** @var Payment $payment */
$payment = $objectManager->create(Payment::class);
$payment->setMethod('checkmo')
    ->setAdditionalInformation('last_trans_id', '11122')
    ->setAdditionalInformation(
        'metadata',
        [
            'type' => 'free',
            'fraudulent' => false,
        ]
    );

$billingAddress = $objectManager->create(OrderAddress::class, [
    'data' => $defaultAddressFixture,
]);
$billingAddress->setAddressType('billing');

$shippingAddress = $objectManager->create(OrderAddress::class, [
    'data' => $defaultAddressFixture,
]);
$shippingAddress->setAddressType('shipping');

$quantity = 1;
/** @var OrderItem $orderItem */
$orderItem = $objectManager->create(OrderItem::class);
$orderItem->setProductId($product->getId());
$orderItem->setQtyOrdered($quantity);
$orderItem->setWeight(1.0000);
$orderItem->setIsVirtual(0);
$orderItem->setIsQtyDecimal(0);
$orderItem->setProductType('simple');
$orderItem->setName($product->getName());
$orderItem->setSku($product->getSku());
$orderItem->setStoreId($store->getId());
// base prices
$orderItem->setBaseOriginalPrice($product->getPrice());
$orderItem->setBasePrice($product->getPrice());
$orderItem->setBasePriceInclTax($product->getPrice());
$orderItem->setBaseRowTotal($product->getPrice() * $quantity);
$orderItem->setBaseRowTotalInclTax($product->getPrice() * $quantity);
// prices
$orderItem->setOriginalPrice($product->getPrice() * $exchangeRate);
$orderItem->setPrice($product->getPrice() * $exchangeRate);
$orderItem->setPriceInclTax($product->getPrice() * $exchangeRate);
$orderItem->setRowTotal($product->getPrice() * $exchangeRate * $quantity);
$orderItem->setRowTotalInclTax($product->getPrice() * $exchangeRate * $quantity);

$orderTotal += $product->getPrice() * $quantity;


$orderData = [
    'state' => Order::STATE_PROCESSING,
    'status' => 'processing',
    'subtotal' => $orderTotal * $exchangeRate,
    'grand_total' => $orderTotal * $exchangeRate,
    'base_subtotal' => $orderTotal,
    'base_grand_total' => $orderTotal,
    'order_currency_code' => $orderCurrency,
    'store_currency_code' => $orderCurrency,
    'base_currency_code' => $baseCurrency,
    'global_currency_code' => $baseCurrency,
    'shipping_description' => 'Flat Rate - Fixed',
    'customer_is_guest' => true,
    'customer_firstname' => 'John',
    'customer_lastname' => 'Smith',
    'customer_email' => 'customer@klevu.com',
    'increment_id' => 'KLEVU100000001',
    'store_id' => $store->getId(),
    'is_virtual' => 0,
    'base_discount_amount' => 0.0000,
    'base_shipping_amount' => 0.0000,
    'base_tax_shipping_amount' => 0.0000,
    'base_tax_amount' => 0.0000,
    'base_to_global_rate' => 1.000,
    'base_to_order_rate' => $exchangeRate,
    'discount_amount' => 0.0000,
    'shipping_amount' => 0.0000,
];

/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->create(OrderRepositoryInterface::class);

/** @var Order $order */
$order = $objectManager->create(Order::class);
$order->load($orderData['increment_id'], 'increment_id');
if ($order->getId()) {
    $order->delete();
    $order = $objectManager->create(Order::class);
}

$order->addData($orderData);
$order->setBillingAddress($billingAddress);
$order->setShippingAddress($shippingAddress);
$order->addItem($orderItem);
$order->setPayment(clone $payment);

$orderRepository->save($order);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
