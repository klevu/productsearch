<?php

namespace Klevu\Search\Provider\Sync\Order\Item\Type;

use Klevu\Search\Api\Provider\Sync\Order\Item\DataProviderInterface as OrderItemDataProviderInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Api\Data\OrderItemInterface;

class ConfigurableDataProvider implements OrderItemDataProviderInterface
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
        $childOrderItem = $this->getChildOrderItem($orderItem);
        $productName = $childOrderItem ? $childOrderItem->getName() : $orderItem->getName();
        $groupId = $orderItem->getProductId();
        $variantId = $childOrderItem ? $childOrderItem->getProductId() : null;

        $data = $this->provider->getData($orderItem);

        $data[DefaultDataProvider::PRODUCT_NAME] = $productName;
        $data[DefaultDataProvider::PRODUCT_ID] = $variantId ? $groupId . '-' . $variantId : $groupId;
        $data[DefaultDataProvider::PRODUCT_GROUP_ID] = $groupId;
        $data[DefaultDataProvider::PRODUCT_VARIANT_ID] = $variantId ?: $groupId;

        return $data;
    }

    /**
     * @param OrderItemInterface $orderItem
     *
     * @return OrderItemInterface|null
     */
    private function getChildOrderItem(OrderItemInterface $orderItem)
    {
        $order = $orderItem->getOrder();
        if (!$order) {
            return null;
        }
        $allItems = $order->getAllItems();
        $simpleItems = array_filter($allItems, static function (OrderItemInterface $item) use ($orderItem) {
            return $item->getProductType() === Type::TYPE_SIMPLE
                && $item->getParentItemId() === $orderItem->getId();
        });
        $keys = array_keys($simpleItems);

        return isset($keys[0], $simpleItems[$keys[0]]) ? $simpleItems[$keys[0]] : null;
    }
}
