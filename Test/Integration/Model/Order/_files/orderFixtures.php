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
    'lastname' => 'lastname',
    'firstname' => 'firstname',
    'street' => 'street',
    'city' => 'Los Angeles',
    'email' => 'admin@example.com',
    'telephone' => '11111111',
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
    'subtotal' => 100,
    'grand_total' => 100,
    'base_subtotal' => 100,
    'base_grand_total' => 100,
    'order_currency_code' => 'USD',
    'base_currency_code' => 'USD',
    'customer_is_guest' => true,
    'customer_email' => 'customer@null.com',
    'billing_address' => $defaultAddressFixture,
    'shipping_address' => $defaultAddressFixture,
];

$fixtures = [
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000001',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000002',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000003',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000004',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000005',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000006',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000007',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000008',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000009',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000010',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000011',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000012',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000013',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000014',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000015',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000016',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000017',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000018',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000019',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000020',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU100000021',
        'store_id' => $storeIds['klevu_test_store_1'],
    ]),

    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU200000001',
        'store_id' => $storeIds['klevu_test_store_2'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU200000002',
        'store_id' => $storeIds['klevu_test_store_2'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU200000003',
        'store_id' => $storeIds['klevu_test_store_2'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU200000004',
        'store_id' => $storeIds['klevu_test_store_2'],
    ]),
    array_merge($orderBoilerplate, [
        'increment_id' => 'KLEVU200000005',
        'store_id' => $storeIds['klevu_test_store_2'],
    ]),
];

$productRepository = $objectManager->create(ProductRepositoryInterface::class);
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
    $order->setPayment($payment);

    $orderRepository->save($order);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
