<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Payment;
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
    'lastname' => 'Lastname',
    'firstname' => 'Firstname',
    'street' => 'street',
    'city' => 'Los Angeles',
    'email' => 'observer@example.com',
    'telephone' => '123456789',
    'country_id' => 'US'
];

$storeIds = [
    'klevu_test_store_1' => $objectManager->get(StoreManagerInterface::class)
        ->getStore('klevu_test_store_1')
        ->getId(),
    'klevu_test_store_2' => $objectManager->get(StoreManagerInterface::class)
        ->getStore('klevu_test_store_2')
        ->getId(),
];

$orderBoilerplate = [
    'state' => Order::STATE_PROCESSING,
    'status' => 'processing',
    'subtotal' => 230,
    'grand_total' => 230,
    'base_subtotal' => 230,
    'base_grand_total' => 230,
    'order_currency_code' => 'USD',
    'base_currency_code' => 'USD',
    'customer_is_guest' => true,
    'customer_email' => 'customer@null.com',
    'billing_address' => $defaultAddressFixture,
    'shipping_address' => $defaultAddressFixture,
];

$fixtures = [
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVUOBS100000001',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVUOBS100000002',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
];

$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$product = $productRepository->get('klevu_simple_1');

/** @var Payment $payment */
$payment = $objectManager->create(Payment::class);
$payment->setMethod('checkmo')
    ->setAdditionalInformation('last_trans_id', '21232')
    ->setAdditionalInformation(
        'metadata',
        [
            'type' => 'free',
            'fraudulent' => false,
        ]
    );

/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->create(OrderRepositoryInterface::class);

foreach ($fixtures as $fixture) {
    $billingAddress = $objectManager->create(OrderAddress::class, [
        'data' => $fixture['billing_address'],
    ]);
    $billingAddress->setAddressType('billing');

    $shippingAddress = $objectManager->create(OrderAddress::class, [
        'data' => $fixture['shipping_address'],
    ]);
    $shippingAddress->setAddressType('shipping');

    /** @var OrderItem $orderItem */
    $orderItem = $objectManager->create(OrderItem::class);
    $orderItem->setProductId($product->getId())
        ->setQtyOrdered(1)
        ->setBasePrice($product->getPrice())
        ->setPrice($product->getPrice())
        ->setRowTotal($product->getPrice())
        ->setProductType('simple')
        ->setName($product->getName())
        ->setSku($product->getSku())
        ->setStoreId($fixture['store_id']);

    unset(
        $fixture['billing_address'],
        $fixture['shipping_address']
    );

    /** @var Order $order */
    $order = $objectManager->create(Order::class);
    $order->load($fixture['increment_id'], 'increment_id');
    if ($order->getId()) {
        $order->delete();
        $order = $objectManager->create(Order::class);
    }

    $order->addData($fixture);
    $order->setBillingAddress($billingAddress);
    $order->setShippingAddress($shippingAddress);
    $order->addItem($orderItem);
    $order->setPayment(clone $payment);

    $orderRepository->save($order);

}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
