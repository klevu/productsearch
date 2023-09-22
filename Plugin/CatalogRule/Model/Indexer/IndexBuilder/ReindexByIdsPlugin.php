<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

namespace Klevu\Search\Plugin\CatalogRule\Model\Indexer\IndexBuilder;

use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\CatalogRule\Model\Indexer\IndexBuilder;

class ReindexByIdsPlugin
{
    /**
     * @var MagentoProductActionsInterface
     */
    private $magentoProductActions;

    /**
     * @param MagentoProductActionsInterface $magentoProductActions
     */
    public function __construct(MagentoProductActionsInterface $magentoProductActions)
    {
        $this->magentoProductActions = $magentoProductActions;
    }

    /**
     * @param IndexBuilder $subject
     * @param array $productIds
     *
     * @return array
     */
    public function afterReindexByIds(IndexBuilder $subject, array $productIds)
    {
        $this->markProductsForUpdate($productIds);

        return $productIds;
    }

    /**
     * @param IndexBuilder $subject
     * @param int $productId
     *
     * @return int
     */
    public function afterReindexById(IndexBuilder $subject, $productId)
    {
        $this->markProductsForUpdate([$productId]);

        return $productId;
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
