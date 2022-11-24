<?php

namespace Klevu\Search\Model\Product\Provider;

use Magento\Catalog\Api\Data\ProductInterface;
use Klevu\Search\Api\Provider\CommonProviderInterface;
use Magento\Catalog\Model\Product\Type;

/**
 * SimpleProvider class to fetch the parent and child ids
 */
class SimpleProvider implements CommonProviderInterface
{
    /**
     * @var array
     */
    private $providers = [];
    /**
     * @var array
     */
    private $parentIds = [];

    /**
     * @param array $providers
     */
    public function __construct(
        array $providers = []
    ) {
        array_walk($providers, [$this, 'addProvider']);
    }

    /**
     * @param CommonProviderInterface $provider
     * @param string $productTypeId
     */
    public function addProvider(CommonProviderInterface $provider, $productTypeId)
    {
        $this->providers[$productTypeId] = $provider;
    }

    /**
     * Always return empty array for simple products because simples cannot have children
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getChildIds(ProductInterface $product)
    {
        if ($product->getTypeId() !== Type::TYPE_SIMPLE) {
            throw new \InvalidArgumentException(
                sprintf("Incorrect ProductTypeId %s provided while fetching childIds", $product->getTypeId())
            );
        }
        return [];
    }

    /**
     * Returns the parent ids for given product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getParentIds(ProductInterface $product)
    {
        if ($product->getTypeId() !== Type::TYPE_SIMPLE) {
            throw new \InvalidArgumentException(
                sprintf("Incorrect ProductTypeId %s provided while fetching parentIds", $product->getTypeId())
            );
        }
        /**
         * To grab the parentIDs where simple belongs to parent providers
         */
        if (!isset($this->parentIds[$product->getSku()])) {
            $parentIds = [];
            foreach ($this->providers as $provider) {
                $parentIds[] = $provider->getParentIds($product);
            }
            $this->parentIds[$product->getSku()] = array_values(
                array_unique(
                    array_filter(
                        array_merge([], ...$parentIds)
                    )
                )
            );
        }
        return $this->parentIds[$product->getSku()];
    }
}
