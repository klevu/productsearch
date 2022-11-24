<?php

namespace Klevu\Search\Service\Catalog\Product\Stock;

use Klevu\Search\Api\Service\Catalog\Product\Stock\GetCompositeProductStockStatusInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class GetCompositeProductStockStatus implements GetCompositeProductStockStatusInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaFactory;
    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @param LoggerInterface $logger
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
     * @param StockItemRepositoryInterface $stockItemRepository
     */
    public function __construct(
        LoggerInterface $logger,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory,
        StockItemRepositoryInterface $stockItemRepository
    ) {
        $this->logger = $logger;
        $this->stockItemCriteriaFactory = $stockItemCriteriaFactory;
        $this->stockItemRepository = $stockItemRepository;
    }

    /**
     * @param ProductInterface $product
     * @param array $bundleOptions
     * @param int|null $stockId
     * @return bool
     */
    public function execute(ProductInterface $product, array $bundleOptions, $stockId = null)
    {
        if (!$product->getId()) {
            return false;
        }
        $product->unsetData('salable');
        $product->unsetData('is_salable');
        if (!$product->isAvailable()) {
            return false;
        }

        $searchCriteria = $this->stockItemCriteriaFactory->create();

        try {
            $searchCriteria->addFilter(
                StockItemInterface::PRODUCT_ID,
                StockItemInterface::PRODUCT_ID,
                (int)$product->getId()
            );
            $searchCriteria->addFilter(
                StockItemInterface::STOCK_ID,
                StockItemInterface::STOCK_ID,
                (null !== $stockId) ? $stockId : Stock::DEFAULT_STOCK_ID
            );
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage(), [
                'method' => __METHOD__,
            ]);

            return false;
        }

        $searchCriteria->setLimit(1, 1);

        $stockItemCollection = $this->stockItemRepository->getList($searchCriteria);
        $stockItems = $stockItemCollection->getItems();
        /** @var StockItemInterface $stockItem */
        $stockItem = reset($stockItems);

        return $stockItem && (bool)(int)$stockItem->getIsInStock();
    }
}
