<?php

namespace Klevu\Search\Model\Product\Provider;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Klevu\Search\Api\Provider\CommonProviderInterface;

/**
 * GroupedProvider class to fetch the parent and child ids
 */
class GroupedProvider implements CommonProviderInterface
{
    /**
     * Child ids
     *
     * @var array
     */
    private $childIds = [];

    /**
     * @var array
     */
    private $parentIds = [];

    /**
     * @var Grouped
     */
    private $groupedType;

    /**
     * @param Grouped $groupedType
     */
    public function __construct(
        Grouped $groupedType
    )
    {
        $this->groupedType = $groupedType;
    }

    /**
     * Returns the child ids for given grouped product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getChildIds(ProductInterface $product)
    {
        if (!isset($this->childIds[$product->getSku()])) {
            $this->childIds[$product->getSku()] = $this->groupedType->getChildrenIds($product->getId(), false);
        }
        return $this->childIds[$product->getSku()];
    }

    /**
     * Returns the parent ids for given grouped product if any
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getParentIds(ProductInterface $product)
    {
        if (!isset($this->parentIds[$product->getSku()])) {
            $this->parentIds[$product->getSku()] = $this->groupedType->getParentIdsByChild($product->getId());
        }
        return $this->parentIds[$product->getSku()];
    }
}
