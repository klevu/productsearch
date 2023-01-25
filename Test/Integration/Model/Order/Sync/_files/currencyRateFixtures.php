<?php

use Magento\Directory\Model\Currency;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

$rates = [
    'USD' => [
        'GBP' => '1.5000',
        'JPY' => '150.0000'
    ]
];

/** @var Currency $currencyModel */
$currencyModel = $objectManager->create(Currency::class);
$currencyModel->saveRates($rates);
