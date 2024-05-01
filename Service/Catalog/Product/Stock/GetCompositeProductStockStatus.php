<?php

namespace Klevu\Search\Service\Catalog\Product\Stock;

use Klevu\Search\Api\Service\Catalog\Product\Stock\GetCompositeProductStockStatusInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterface;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockStatusRepositoryInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\ObjectManager;
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
     * @var StockStatusRepositoryInterface|null
     */
    private $stockStatusRepository;
    /**
     * @var StockStatusCriteriaInterfaceFactory|null
     */
    private $stockStatusCriteriaFactory;

    /**
     * @param LoggerInterface $logger
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockStatusCriteriaInterfaceFactory|null $stockStatusCriteriaFactory
     * @param StockStatusRepositoryInterface|null $stockStatusRepository
     */
    public function __construct(
        LoggerInterface $logger,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory,
        StockItemRepositoryInterface $stockItemRepository,
        StockStatusCriteriaInterfaceFactory $stockStatusCriteriaFactory = null,
        StockStatusRepositoryInterface $stockStatusRepository = null
    ) {
        $this->logger = $logger;
        $this->stockItemCriteriaFactory = $stockItemCriteriaFactory;
        $this->stockItemRepository = $stockItemRepository;
        $objectManager = ObjectManager::getInstance();
        $this->stockStatusCriteriaFactory = $stockStatusCriteriaFactory
            ?: $objectManager->get(StockStatusCriteriaInterfaceFactory::class);
        $this->stockStatusRepository = $stockStatusRepository
            ?: $objectManager->get(StockStatusRepositoryInterface::class);
    }

    /**
     * @param ProductInterface $product
     * @param array $bundleOptions
     * @param int|null $stockId
     * @return bool
     */
    public function execute(ProductInterface $product, array $bundleOptions, $stockId = null)
    {
        // We can not use stockRegistry->getStockStatus here
        // as it always returns true for configurable and bundle products.
        if (!$product->getId()) {
            return false;
        }
        $product->unsetData('salable');
        $product->unsetData('is_salable');
        if (!$product->isAvailable()) { // isAvailable returns false if no child products are available
             return false;
        }
        /** @var StockStatusCriteriaInterface $searchCriteria */
        $searchCriteria = $this->stockStatusCriteriaFactory->create();
        try {
            $searchCriteria->addFilter(
                StockStatusInterface::PRODUCT_ID,
                StockStatusInterface::PRODUCT_ID,
                (int)$product->getId()
            );
            $searchCriteria->addFilter(
                StockStatusInterface::STOCK_ID,
                StockStatusInterface::STOCK_ID,
                (null !== $stockId) ? $stockId : Stock::DEFAULT_STOCK_ID
            );
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage(), [
                'method' => __METHOD__,
            ]);

            return false;
        }
        $searchCriteria->setLimit(1, 1);

        $stockItemCollection = $this->stockStatusRepository->getList($searchCriteria);
        $stockItems = $stockItemCollection->getItems();
        /** @var StockStatusInterface $stockStatus */
        $stockStatus = reset($stockItems);

        return $stockStatus && (bool)(int)$stockStatus->getStockStatus();
    }
}
