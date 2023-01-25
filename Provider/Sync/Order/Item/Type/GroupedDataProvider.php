<?php

namespace Klevu\Search\Provider\Sync\Order\Item\Type;

use Klevu\Search\Api\Provider\Sync\Order\Item\DataProviderInterface as OrderItemDataProviderInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Sales\Api\Data\OrderItemInterface;

class GroupedDataProvider implements OrderItemDataProviderInterface
{
    /**
     * @var OrderItemDataProviderInterface
     */
    private $provider;

    /**
     * @param OrderItemDataProviderInterface $provider
     */
    public function __construct(OrderItemDataProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param OrderItemInterface $orderItem
     *
     * @return array
     */
    public function getData(OrderItemInterface $orderItem)
    {
        $newOrderItem = $this->createNewOrderItem($orderItem);

        return $this->provider->getData($newOrderItem);
    }

    /**
     * @param OrderItemInterface $orderItem
     *
     * @return OrderItemInterface
     */
    private function createNewOrderItem(OrderItemInterface $orderItem)
    {
        $data = $orderItem->getBuyRequest();
        $superProductConfig = $data->getDataUsingMethod('super_product_config');
        $groupedProductId = !empty($superProductConfig['product_id'])
            ? $superProductConfig['product_id']
            : null;

        $groupedChildProducts = $this->getChildOrderItem($orderItem, $groupedProductId);

        $newItem = clone $orderItem;
        $newItem->setProductId($groupedProductId);
        $newItem->setQtyOrdered("1.0000");
        $basePrice = 0;
        $basePriceIncl = 0;
        $price = 0;
        $priceIncl = 0;
        foreach ($groupedChildProducts as $childProduct) {
            $qty = $childProduct->getQtyOrdered();
            $basePrice += $childProduct->getBasePrice() * $qty;
            $basePriceIncl += $childProduct->getBasePriceInclTax() * $qty;
            $price += $childProduct->getPrice() * $qty;
            $priceIncl += $childProduct->getPriceInclTax() * $qty;
        }
        $newItem->setBasePrice($basePrice);
        $newItem->setBasePriceInclTax($basePriceIncl);
        $newItem->setPrice($price);
        $newItem->setPriceInclTax($priceIncl);

        return $newItem;
    }

    /**
     * @param OrderItemInterface $orderItem
     * @param int $groupedProductId
     *
     * @return OrderItemInterface[]
     */
    private function getChildOrderItem(OrderItemInterface $orderItem, $groupedProductId)
    {
        $order = $orderItem->getOrder();
        if (!$order) {
            return [];
        }
        $allItems = $order->getAllItems();

        return array_filter($allItems, static function (OrderItemInterface $item) use ($groupedProductId) {
            $data = $item->getBuyRequest();
            $superProductConfig = $data->getDataUsingMethod('super_product_config');
            $parentId = !empty($superProductConfig['product_id'])
                ? $superProductConfig['product_id']
                : null;

            return $item->getProductType() === Grouped::TYPE_CODE &&
                (int)$groupedProductId === (int)$parentId;
        });
    }
}
