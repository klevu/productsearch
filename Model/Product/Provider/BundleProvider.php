<?php

namespace Klevu\Search\Model\Product\Provider;


use Magento\Bundle\Model\ResourceModel\Selection as BundleSelector;
use Magento\Catalog\Api\Data\ProductInterface;
use Klevu\Search\Api\Provider\CommonProviderInterface;

/**
 * BundleProvider class to fetch the parent and child ids
 */
class BundleProvider implements CommonProviderInterface
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
     * @var BundleSelector
     */
    protected $bundleSelector;

    /**
     * @param BundleSelector $bundleSelector
     */
    public function __construct(
        BundleSelector $bundleSelector
    )
    {
        $this->bundleSelector = $bundleSelector;
    }

    /**
     * Returns the parent ids for given product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getParentIds(ProductInterface $product)
    {
        if (!isset($this->parentIds[$product->getSku()])) {
            $this->parentIds[$product->getSku()] = $this->bundleSelector->getParentIdsByChild($product->getId());
        }
        return $this->parentIds[$product->getSku()];
    }

    /**
     * Returns the child ids for given product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getChildIds(ProductInterface $product)
    {
        if (!isset($this->childIds[$product->getSku()])) {
            $this->childIds[$product->getSku()] = $this->bundleSelector->getChildrenIds($product->getId(), false);
        }
        return $this->childIds[$product->getSku()];
    }
}

