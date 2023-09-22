<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Plugin\CatalogRule\Model\Indexer\IndexBuilder;

use Klevu\Search\Api\Provider\CatalogRule\GetProductIdsProviderInterface;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\CatalogRule\Model\Indexer\IndexBuilder;

class FullReindexPlugin
{
    /**
     * @var MagentoProductActionsInterface
     */
    private $magentoProductActions;
    /**
     * @var GetProductIdsProviderInterface
     */
    private $getProductIdsProvider;

    /**
     * @param MagentoProductActionsInterface $magentoProductActions
     * @param GetProductIdsProviderInterface $getProductIdsProvider
     */
    public function __construct(
        MagentoProductActionsInterface $magentoProductActions,
        GetProductIdsProviderInterface $getProductIdsProvider
    ) {
        $this->magentoProductActions = $magentoProductActions;
        $this->getProductIdsProvider = $getProductIdsProvider;
    }

    /**
     * @param IndexBuilder $subject
     * @param callable $proceed
     *
     * @return mixed
     */
    public function aroundReindexFull(IndexBuilder $subject, callable $proceed)
    {
        $initialIds = $this->getProductIdsProvider->get() ?: [];

        $result = $proceed();

        $updatedIds = $this->getProductIdsProvider->get() ?: [];
        $productsToUpdate = array_unique(array_merge($initialIds, $updatedIds));
        if ($productsToUpdate) {
            $this->markProductsForUpdate($productsToUpdate);
        }

        return $result;
    }

    /**
     * @param array $unFilteredProductIds
     *
     * @return void
     */
    private function markProductsForUpdate(array $unFilteredProductIds)
    {
        $productIds = $this->removeNonNumericValues($unFilteredProductIds);
        if (!$productIds) {
            return;
        }
        $this->magentoProductActions->markRecordIntoQueue($productIds);
    }

    /**
     * @param array $productIds
     *
     * @return array
     */
    private function removeNonNumericValues(array $productIds)
    {
        return array_filter($productIds, static function ($id) {
            return is_numeric($id) && (int)$id == $id; // intentionally used weak comparison
        });
    }
}
