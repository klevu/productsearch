<?php

namespace Klevu\Search\Service\Sync\Product;

use Klevu\Search\Api\Service\Sync\Product\GetNextActionsInterface;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Klevu\Search\Model\Source\NextAction;
use Magento\Store\Api\Data\StoreInterface;

class GetNextActions implements GetNextActionsInterface
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
     * @param StoreInterface $store
     * @param array $productIds
     *
     * @return array[]
     */
    public function execute(StoreInterface $store, array $productIds)
    {
        return [
            NextAction::ACTION_ADD => $this->getProductsToAdd($store, $productIds),
            NextAction::ACTION_DELETE => $this->getProductsToDelete($store, []),
            NextAction::ACTION_UPDATE => $this->getProductsToUpdate($store, $productIds),
        ];
    }

    /**
     * @param StoreInterface $store
     * @param array $productIds
     *
     * @return array
     */
    private function getProductsToAdd(StoreInterface $store, array $productIds)
    {
        return $this->magentoProductActions->addProductCollection($store, $productIds);
    }

    /**
     * @param StoreInterface $store
     * @param array $productIds
     *
     * @return array
     */
    private function getProductsToDelete(StoreInterface $store, array $productIds)
    {
        return $this->magentoProductActions->deleteProductCollection($store, $productIds);
    }

    /**
     * @param StoreInterface $store
     * @param array $productIds
     *
     * @return array
     */
    private function getProductsToUpdate(StoreInterface $store, array $productIds)
    {
        return $this->magentoProductActions->updateProductCollection($store, $productIds);
    }
}
