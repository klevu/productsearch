<?php
namespace Klevu\Search\Helper;

use \Magento\CatalogInventory\Api\StockRegistryInterface;

class Stock extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $_stockRegistryInterface;

    public function __construct(
        StockRegistryInterface $stockRegistryInterface
    )
    {
        $this->_stockRegistryInterface = $stockRegistryInterface;
    }

    /**
     * Get stock data for simple and parent product
     *
     * @return string
     */

    public function getKlevuStockStatus($parent, $item)
    {
        if($parent) {
            $stockStatusRegistry = $this->_stockRegistryInterface->getStockStatus($parent->getId(), $parent->getStore()->getWebsiteId());
            $inStock = $stockStatusRegistry->getStockStatus();
            if($inStock) {
                //get stock status of child if parent is not set
                $stockStatusRegistry = $this->_stockRegistryInterface->getStockStatus($item->getId(), $item->getStore()->getWebsiteId());
                $inStock = $stockStatusRegistry->getStockStatus();
            }
        } else {
            $stockStatusRegistry = $this->_stockRegistryInterface->getStockStatus($item->getId(), $item->getStore()->getWebsiteId());
            $inStock = $stockStatusRegistry->getStockStatus();
        }

        $product_stock_status =  ($inStock) ? "yes" : "no";
        return $product_stock_status;
    }

}