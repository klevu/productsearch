<?php

namespace Klevu\Search\Helper;

use Klevu\Search\Api\Service\Catalog\Product\StockServiceInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;

class Stock extends AbstractHelper
{
    /**
     * @var StockRegistryInterface
     */
    protected $_stockRegistryInterface;
    /**
     * @var StockServiceInterface
     */
    private $stockService;

    public function __construct(
        StockRegistryInterface $stockRegistryInterface,
        StockServiceInterface $stockService = null
    ) {
        // There is no need for this class to extend AbstractHelper. It is left in place for backwards compatibility.
        // parent::__construct call is not required.
        $this->_stockRegistryInterface = $stockRegistryInterface;
        $this->stockService = $stockService ?: ObjectManager::getInstance()->get(StockServiceInterface::class);
    }

    /**
     * Get stock data for simple and parent product
     * @deprecated see \Klevu\Search\Service\Catalog\Product\Stock::getKlevuStockStatus
     *
     * @param ProductInterface|null $parentProduct
     * @param ProductInterface $product
     *
     * @return string
     */
    public function getKlevuStockStatus($parentProduct, $product)
    {
        // params are intentionally inverted here, $parentProduct can be null, $product cannot
        return $this->stockService->getKlevuStockStatus($product, $parentProduct);
    }
}
