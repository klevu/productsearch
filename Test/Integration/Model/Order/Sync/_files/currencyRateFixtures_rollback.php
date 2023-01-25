<?php

use Magento\Directory\Model\ResourceModel\Currency as CurrencyResource;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var CurrencyResource $deleteRateByCode */
$currencyResource = $objectManager->get(CurrencyResource::class);

$rates = [
    'GBP',
    'JPY'
];

foreach ($rates as $rate) {
    $connection = $currencyResource->getConnection();
    $rateTable = $currencyResource->getTable('directory_currency_rate');
    $connection->delete($rateTable, $connection->quoteInto('currency_to = ? OR currency_from = ?', $rate));
}
