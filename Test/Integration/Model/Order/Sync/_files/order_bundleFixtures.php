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
$childProduct1 = $productRepository->get('klevu_simple_bundle_child_1');
$childProduct2 = $productRepository->get('klevu_simple_bundle_child_2');
$parentProduct = $productRepository->get('klevu_bundle_1');

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

$orderItem1Qty = 2;
/** @var OrderItem $orderItem */
$orderItem1 = $objectManager->create(OrderItem::class);
$orderItem1->setProductId($childProduct1->getId());
$orderItem1->setQtyOrdered($orderItem1Qty);
$orderItem1->setWeight(1.0000);
$orderItem1->setIsVirtual(0);
$orderItem1->setIsQtyDecimal(0);
$orderItem1->setProductType($childProduct1->getTypeId());
$orderItem1->setName($childProduct1->getName());
$orderItem1->setSku($childProduct1->getSku());
$orderItem1->setStoreId($store->getId());

// base prices
$orderItem1->setBaseOriginalPrice($childProduct1->getPrice());
$orderItem1->setBasePrice($childProduct1->getPrice());
$orderItem1->setBasePriceInclTax($childProduct1->getPrice());
$orderItem1->setBaseRowTotal($childProduct1->getPrice() * $orderItem1Qty);
$orderItem1->setBaseRowTotalInclTax($childProduct1->getPrice() * $orderItem1Qty);
// prices
$orderItem1->setOriginalPrice($childProduct1->getPrice() * $exchangeRate);
$orderItem1->setPrice($childProduct1->getPrice() * $exchangeRate);
$orderItem1->setPriceInclTax($childProduct1->getPrice() * $exchangeRate);
$orderItem1->setRowTotal($childProduct1->getPrice() * $exchangeRate * $orderItem1Qty);
$orderItem1->setRowTotalInclTax($childProduct1->getPrice() * $exchangeRate * $orderItem1Qty);

$orderTotal += $childProduct1->getPrice() * $orderItem1Qty;

$orderItem2Qty = 3;
/** @var OrderItem $orderItem */
$orderItem2 = $objectManager->create(OrderItem::class);
$orderItem2->setProductId($childProduct2->getId());
$orderItem2->setQtyOrdered($orderItem2Qty);
$orderItem2->setWeight(1.0000);
$orderItem2->setIsVirtual(0);
$orderItem2->setIsQtyDecimal(0);
$orderItem2->setProductType($childProduct1->getTypeId());
$orderItem2->setName($childProduct2->getName());
$orderItem2->setSku($childProduct2->getSku());
$orderItem2->setStoreId($store->getId());
// base prices
$orderItem2->setBaseOriginalPrice($childProduct2->getPrice());
$orderItem2->setBasePrice($childProduct2->getPrice());
$orderItem2->setBasePriceInclTax($childProduct2->getPrice());
$orderItem2->setBaseRowTotal($childProduct2->getPrice() * $orderItem2Qty);
$orderItem2->setBaseRowTotalInclTax($childProduct2->getPrice() * $orderItem2Qty);
// prices
$orderItem2->setOriginalPrice($childProduct2->getPrice() * $exchangeRate);
$orderItem2->setPrice($childProduct2->getPrice() * $exchangeRate);
$orderItem2->setPriceInclTax($childProduct2->getPrice() * $exchangeRate);
$orderItem2->setRowTotal($childProduct2->getPrice() * $exchangeRate * $orderItem2Qty);
$orderItem2->setRowTotalInclTax($childProduct2->getPrice() * $exchangeRate * $orderItem2Qty);

$orderTotal += $childProduct2->getPrice() * $orderItem2Qty;

/** @var OrderItem $orderItem */
$orderItem3 = $objectManager->create(OrderItem::class);
$orderItem3->setProductId($parentProduct->getId());
$orderItem3->setQtyOrdered(1);
$orderItem3->setWeight(1.0000);
$orderItem3->setIsVirtual(0);
$orderItem3->setIsQtyDecimal(0);
$orderItem3->setProductType($parentProduct->getTypeId());
$orderItem3->setName($parentProduct->getName());
$orderItem3->setSku($parentProduct->getSku());
$orderItem3->setStoreId($store->getId());
// base prices
$orderItem3->setBaseOriginalPrice(
    ($orderItem1->getBaseOriginalPrice() * $orderItem1Qty) + ($orderItem2->getBaseOriginalPrice() * $orderItem2Qty)
);
$orderItem3->setBasePrice(
    ($orderItem1->getBasePrice() * $orderItem1Qty) + ($orderItem2->getBasePrice() * $orderItem2Qty)
);
$orderItem3->setBasePriceInclTax(
    ($orderItem1->getBasePriceInclTax() * $orderItem1Qty) + ($orderItem2->getBasePriceInclTax() * $orderItem2Qty)
);
$orderItem3->setBaseRowTotal(
    ($orderItem1->getBaseRowTotal()) + ($orderItem2->getBaseRowTotal())
);
$orderItem3->setBaseRowTotalInclTax(
    ($orderItem1->getBaseRowTotalInclTax()) + ($orderItem2->getBaseRowTotalInclTax())
);
// prices
$orderItem3->setOriginalPrice(
    ($orderItem1->getOriginalPrice() * $orderItem1Qty) + ($orderItem2->getOriginalPrice() * $orderItem2Qty)
);
$orderItem3->setPrice(
    ($orderItem1->getPrice() * $orderItem1Qty) + ($orderItem2->getPrice() * $orderItem2Qty)
);
$orderItem3->setPriceInclTax(
    ($orderItem1->getPriceInclTax() * $orderItem1Qty) + ($orderItem2->getPriceInclTax() * $orderItem2Qty)
);
$orderItem3->setRowTotal(
    ($orderItem1->getRowTotal()) + ($orderItem2->getRowTotal())
);
$orderItem3->setRowTotalInclTax(
    ($orderItem1->getRowTotalInclTax()) + ($orderItem2->getRowTotalInclTax())
);

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
    'increment_id' => 'KLEVU400000001',
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
$order->addItem($orderItem3);
$order->setPayment(clone $payment);

$orderRepository->save($order);

$orderItem1->setParentItemId($orderItem3->getItemId());
$orderItemRepository->save($orderItem1);

$orderItem2->setParentItemId($orderItem3->getItemId());
$orderItemRepository->save($orderItem2);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
