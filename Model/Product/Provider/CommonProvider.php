<?php

namespace Klevu\Search\Model\Product\Provider;

use Magento\Catalog\Api\Data\ProductInterface;
use Klevu\Search\Api\Provider\CommonProviderInterface;

/**
 *
 */
class CommonProvider implements CommonProviderInterface
{
    /**
     * @var array
     */
    private $providers = [];

    /**
     * @param array $providers
     */
    public function __construct(
        array $providers = []
    )
    {
        array_walk($providers, [$this, 'addProvider']);
    }

    /**
     * @param CommonProviderInterface $provider
     * @param string $productTypeId
     */
    private function addProvider(CommonProviderInterface $provider, $productTypeId)
    {
        $this->providers[$productTypeId] = $provider;
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    public function getChildIds(ProductInterface $product)
    {
        return isset($this->providers[$product->getTypeId()])
            ? $this->providers[$product->getTypeId()]->getChildIds($product)
            : [];
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    public function getParentIds(ProductInterface $product)
    {
        return isset($this->providers[$product->getTypeId()])
            ? $this->providers[$product->getTypeId()]->getParentIds($product)
            : [];
    }
}

