<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderItemRepositoryInterface;
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
$childProduct = $productRepository->get('klevu_simple_child_1');
$parentProduct = $productRepository->get('klevu_configurable_1');

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
$orderItem1 = $objectManager->create(OrderItem::class);
$orderItem1->setProductId($childProduct->getId());
$orderItem1->setQtyOrdered($quantity);
$orderItem1->setWeight(1.0000);
$orderItem1->setIsVirtual(0);
$orderItem1->setIsQtyDecimal(0);
$orderItem1->setProductType($childProduct->getTypeId());
$orderItem1->setName($childProduct->getName());
$orderItem1->setSku($childProduct->getSku());
$orderItem1->setStoreId($store->getId());

/** @var OrderItem $orderItem */
$orderItem2 = $objectManager->create(OrderItem::class);
$orderItem2->setProductId($parentProduct->getId());
$orderItem2->setQtyOrdered($quantity);
$orderItem2->setWeight(1.0000);
$orderItem2->setIsVirtual(0);
$orderItem2->setIsQtyDecimal(0);
$orderItem2->setProductType($parentProduct->getTypeId());
$orderItem2->setName($parentProduct->getName());
$orderItem2->setSku($parentProduct->getSku());
$orderItem2->setStoreId($store->getId());
// base prices
$orderItem2->setBaseOriginalPrice($childProduct->getPrice());
$orderItem2->setBasePrice($childProduct->getPrice());
$orderItem2->setBasePriceInclTax($childProduct->getPrice());
$orderItem2->setBaseRowTotal($childProduct->getPrice() * $quantity);
$orderItem2->setBaseRowTotalInclTax($childProduct->getPrice() * $quantity);
// prices
$orderItem2->setOriginalPrice($childProduct->getPrice() * $exchangeRate);
$orderItem2->setPrice($childProduct->getPrice() * $exchangeRate);
$orderItem2->setPriceInclTax($childProduct->getPrice() * $exchangeRate);
$orderItem2->setRowTotal($childProduct->getPrice() * $exchangeRate * $quantity);
$orderItem2->setRowTotalInclTax($childProduct->getPrice() * $exchangeRate * $quantity);

$orderTotal += $childProduct->getPrice() * $quantity;

$orderData = [
    'state' => Order::STATE_PROCESSING,
    'status' => 'processing',
    'subtotal' => $orderTotal * $exchangeRate,
    'grand_total' => $orderTotal * $exchangeRate,
    'base_subtotal' => $orderTotal,
    'base_grand_total' => $orderTotal,
    'order_currency_code' => $orderCurrency,
    'store_currency_code' => $orderCurrency,
    'global_currency_code' => $baseCurrency,
    'shipping_description' => 'Flat Rate - Fixed',
    'customer_is_guest' => true,
    'customer_firstname' => 'John',
    'customer_lastname' => 'Smith',
    'customer_email' => 'customer@klevu.com',
    'increment_id' => 'KLEVU200000001',
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
/** @var OrderItemRepositoryInterface $orderItemRepository */
$orderItemRepository = $objectManager->create(OrderItemRepositoryInterface::class);

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
$order->addItem($orderItem1);
$order->addItem($orderItem2);
$order->setPayment(clone $payment);

$orderRepository->save($order);

$orderItem1->setParentItemId($orderItem2->getItemId());
$orderItemRepository->save($orderItem1);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
