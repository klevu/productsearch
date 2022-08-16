<?php

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\GetStockStatusByIdInterface;
use Klevu\Search\Api\Service\Catalog\Product\StockServiceInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\ObjectManager;

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
    /**
     * @var GetStockStatusByIdInterface
     */
    private $getStockStatusById;

    /**
     * @param StockRegistryInterface $stockRegistryInterface
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param GetStockStatusByIdInterface|null $getStockStatusById
     */
    public function __construct(
        StockRegistryInterface $stockRegistryInterface,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory,
        StockItemRepositoryInterface $stockItemRepository,
        GetStockStatusByIdInterface $getStockStatusById = null
    ) {
        $this->_stockRegistryInterface = $stockRegistryInterface;
        $this->stockItemCriteriaInterfaceFactory = $stockItemCriteriaInterfaceFactory;
        $this->stockItemRepository = $stockItemRepository;
        $this->getStockStatusById = $getStockStatusById ?:
            ObjectManager::getInstance()->get(GetStockStatusByIdInterface::class);
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
     * @param string|int|null $websiteId
     *
     * @return void
     */
    public function preloadKlevuStockStatus(array $productIds, $websiteId = null)
    {
        $cacheId = $this->getCacheId($websiteId);
        $productIds = array_map('intval', $productIds);
        $websiteId = $websiteId ? (int)$websiteId : null;

        $stockStatusById = $this->getStockStatusById->execute($productIds, $websiteId);
        foreach ($stockStatusById as $productId => $stockStatus) {
            $this->cache[$cacheId][$productId] = (bool)$stockStatus;
        }
    }

    /**
     * Get stock data for simple and parent product
     *
     * @param ProductInterface $product
     * @param ProductInterface|null $parentProduct
     * @param int|null $websiteId
     *
     * @return string
     */
    public function getKlevuStockStatus(ProductInterface $product, $parentProduct = null, $websiteId = null)
    {
        return $this->isInStock($product, $parentProduct, $websiteId) ?
            static::KLEVU_IN_STOCK :
            static::KLEVU_OUT_OF_STOCK;
    }

    /**
     * @param ProductInterface $product
     * @param ProductInterface|null $parentProduct
     * @param int|null $websiteId
     *
     * @return bool
     */
    public function isInStock(ProductInterface $product, $parentProduct = null, $websiteId = null)
    {
        if (!$parentProduct) {
            return $this->getStockStatus($product, $websiteId);
        }

        // if parent product is in stock also check child product
        return $this->getStockStatus($parentProduct, $websiteId)
            && $this->getStockStatus($product, $websiteId);
    }

    /**
     * @param ProductInterface $product
     * @param int|null $websiteId
     *
     * @return bool
     */
    private function getStockStatus(ProductInterface $product, $websiteId = null)
    {
        $cacheId = $this->getCacheId($websiteId);
        if (isset($this->cache[$cacheId][$product->getId()])) {
            return $this->cache[$cacheId][$product->getId()];
        }
        $stockStatusInterface = $this->_stockRegistryInterface->getStockStatus(
            $product->getId(),
            $product->getStore()->getWebsiteId()
        );
        $stockStatus = (bool)$stockStatusInterface->getStockStatus();
        $this->cache[$cacheId][$product->getId()] = $stockStatus;

        return $stockStatus;
    }

    /**
     * @param int|null $websiteId
     *
     * @return string
     */
    private function getCacheId($websiteId)
    {
        return $websiteId ? (string)$websiteId : "default";
    }
}
