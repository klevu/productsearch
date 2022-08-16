<?php

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\GetStockStatusByIdInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;

class GetStockStatusById implements GetStockStatusByIdInterface
{
    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaInterfaceFactory;
    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory
     * @param StockItemRepositoryInterface $stockItemRepository
     */
    public function __construct(
        StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory,
        StockItemRepositoryInterface $stockItemRepository
    ) {
        $this->stockItemCriteriaInterfaceFactory = $stockItemCriteriaInterfaceFactory;
        $this->stockItemRepository = $stockItemRepository;
    }

    /**
     * @param int[] $productIds
     * @param int|null $websiteId
     *
     * @return bool[]
     */
    public function execute(array $productIds, $websiteId = null)
    {
        $result = [];
        if (empty($productIds)) {
            return $result;
        }
        $stockItemCriteria = $this->stockItemCriteriaInterfaceFactory->create();
        $stockItemCriteria->setProductsFilter($productIds);

        $stockList = $this->stockItemRepository->getList($stockItemCriteria);
        foreach ($stockList->getItems() as $stockProduct) {
            $result[$stockProduct->getProductId()] = (bool)$stockProduct->getIsInStock();
        }

        return $result;
    }
}
