<?php

namespace Klevu\Search\Provider\Sync\Order\Item;

use Klevu\Search\Api\Provider\Sync\Order\Item\DataProviderInterface as OrderItemDataProviderInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Api\Data\OrderItemInterface;
use Psr\Log\LoggerInterface;

class DataProvider implements OrderItemDataProviderInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    private $dataProviders;

    /**
     * @param LoggerInterface $logger
     * @param array $dataProviders
     */
    public function __construct(
        LoggerInterface $logger,
        array $dataProviders
    ) {
        $this->logger = $logger;
        array_walk($dataProviders, [$this, 'addDataProvider']);
    }

    /**
     * @param OrderItemInterface $orderItem
     *
     * @return array
     */
    public function getData(OrderItemInterface $orderItem)
    {
        $dataProviderType = $orderItem->getProductType() ?: Type::DEFAULT_TYPE;
        if (!isset($this->dataProviders[$dataProviderType])) {
            $this->logger->error(
                __('No data provider configured for product type %1', $dataProviderType)
            );

            return [];
        }
        /** @var OrderItemDataProviderInterface $dataProvider */
        $dataProvider = $this->dataProviders[$dataProviderType];

        return $dataProvider->getData($orderItem);
    }

    /**
     * @param OrderItemDataProviderInterface $dataProvider
     * @param string $type
     *
     * @return void
     */
    private function addDataProvider(OrderItemDataProviderInterface $dataProvider, $type)
    {
        $this->dataProviders[$type] = $dataProvider;
    }
}
