<?php

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\StockServiceInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class Stock implements StockServiceInterface
{
    const KLEVU_IN_STOCK = "yes";
    const KLEVU_OUT_OF_STOCK = "no";

    /**
     * @var StockRegistryInterface
     */
    protected $_stockRegistryInterface;
    /**
     * @var array
     */
    private $cache = [];
    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaInterfaceFactory;
    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    public function __construct(
        StockRegistryInterface $stockRegistryInterface,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory,
        StockItemRepositoryInterface $stockItemRepository
    ) {
        $this->_stockRegistryInterface = $stockRegistryInterface;
        $this->stockItemCriteriaInterfaceFactory = $stockItemCriteriaInterfaceFactory;
        $this->stockItemRepository = $stockItemRepository;
    }

    /**
     * @return void
     */
    public function clearCache()
    {
        $this->cache = [];
    }

    /**
     * @param array $productIds
     *
     * @return void
     */
    public function preloadKlevuStockStatus(array $productIds)
    {
        $stockItemCriteria = $this->stockItemCriteriaInterfaceFactory->create();
        $stockItemCriteria->setProductsFilter($productIds);

        $stockProducts = $this->stockItemRepository->getList($stockItemCriteria);
        foreach ($stockProducts->getItems() as $stockProduct) {
            $this->cache[$stockProduct->getProductId()] = (bool)$stockProduct->getIsInStock();
        }
    }

    /**
     * Get stock data for simple and parent product
     *
     * @param ProductInterface $product
     * @param ProductInterface|null $parentProduct
     *
     * @return string
     */
    public function getKlevuStockStatus(ProductInterface $product, $parentProduct = null)
    {
        return $this->isInStock($product, $parentProduct) ?
            static::KLEVU_IN_STOCK :
            static::KLEVU_OUT_OF_STOCK;
    }

    /**
     * @param ProductInterface $product
     * @param ProductInterface|null $parentProduct
     *
     * @return bool
     */
    public function isInStock(ProductInterface $product, $parentProduct = null)
    {
        if (!$parentProduct) {
            return $this->getStockStatus($product);
        }
        // if parent product is in stock also check child product
        if ($inStock = $this->getStockStatus($parentProduct)) {
            $inStock = $this->getStockStatus($product);
        }

        return $inStock;
    }

    /**
     * @param ProductInterface $product
     *
     * @return bool
     */
    private function getStockStatus(ProductInterface $product)
    {
        if (isset($this->cache[$product->getId()])) {
            return $this->cache[$product->getId()];
        }
        $stockStatusInterface = $this->_stockRegistryInterface->getStockStatus(
            $product->getId(),
            $product->getStore()->getWebsiteId()
        );
        $stockStatus = (bool)$stockStatusInterface->getStockStatus();
        $this->cache[$product->getId()] = $stockStatus;

        return $stockStatus;
    }
}
