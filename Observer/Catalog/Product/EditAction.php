<?php

namespace Klevu\Search\Observer\Catalog\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class EditAction implements ObserverInterface
{
    /**
     * @var array
     */
    private $attributesToLock;

    /**
     * @param array $attributesToLock
     */
    public function __construct(array $attributesToLock)
    {
        $this->attributesToLock = $attributesToLock;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        /** @var ProductInterface $product */
        $product = $event->getDataUsingMethod('product');
        if (!($product instanceof ProductInterface) || !method_exists($product, 'lockAttribute')) {
            return;
        }
        foreach ($this->attributesToLock as $attributeCode) {
            $product->lockAttribute($attributeCode);
        }
    }
}
