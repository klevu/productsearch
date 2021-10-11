<?php

namespace Klevu\Search\Model\Product;

use Klevu\Search\Api\Provider\CommonProviderInterface;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Catalog\Api\Data\ProductInterface as MagentoProductInterface;

/**
 * Class ProductCommonUpdater
 * @package Klevu\Search\Model\Product
 */
class ProductCommonUpdater implements ProductCommonUpdaterInterface
{
    /**
     * @var MagentoProductActionsInterface
     */
    private $magentoProductActions;

    /**
     * @var CommonProviderInterface
     */
    private $commonProviderInterface;

    /**
     * @param MagentoProductActionsInterface $magentoProductActions
     * @param CommonProviderInterface $commonProvider
     */
    public function __construct(
        MagentoProductActionsInterface   $magentoProductActions,
        CommonProviderInterface $commonProvider
    )
    {
        $this->magentoProductActions = $magentoProductActions;
        $this->commonProviderInterface = $commonProvider;
    }

    /**
     * @param MagentoProductInterface $product
     * @return void
     */
    public function markProductToQueue(MagentoProductInterface $product)
    {
        $product_ids[] = $product->getId();

        $productIdsToUpdate = $this->commonProviderInterface->getParentIds($product);
        $ids = $this->convertSingleArrayUniqueIds($productIdsToUpdate);
        if (!empty($ids)) {
            $product_ids = array_merge($product_ids, $ids);
        }

        $childIdsToUpdate = $this->commonProviderInterface->getChildIds($product);
        $ids = $this->convertSingleArrayUniqueIds($childIdsToUpdate);
        if (!empty($ids)) {
            $product_ids = array_merge($product_ids, $ids);
        }

        $storeIds = method_exists($product, 'getStoreIds') ? $product->getStoreIds() : [];
        $this->magentoProductActions->markRecordIntoQueue($product_ids, 'products', array_filter($storeIds));
    }

    /**
     * Converts single unique array
     * @param array $ids
     * @return array
     */
    private function convertSingleArrayUniqueIds($ids)
    {
        if (!is_array($ids) || empty($ids)) {
            return [];
        }
        $result = [];
        array_map(function ($arr) use (&$result) {
            $result = array_merge($result, !is_array($arr) ? [$arr] : $arr);
        }, $ids);

        return array_unique($result);
    }
}
