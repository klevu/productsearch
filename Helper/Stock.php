<?php
namespace Klevu\Search\Helper;
use \Magento\CatalogInventory\Api\StockStateInterface;
class Stock extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $_stockStateInterface;
   
    public function __construct(
	\Magento\CatalogInventory\Api\StockStateInterface $stockStateInterface
	)
    {
        $this->_stockStateInterface = $stockStateInterface;
    }

    /**
     * Get stock data for simple and parent product
     *
     * @return string
     */

    public function getKlevuStockStatus($parent, $item)
    {
        if($parent) {
			$inStock = $this->_stockStateInterface->verifyStock($parent->getId(),$parent->getStore()->getWebsiteId());
			if($inStock) {
				$inStock = $this->_stockStateInterface->verifyStock($item->getId(),$item->getStore()->getWebsiteId());
				$product_stock_status =  ($inStock) ? "yes" : "no";
				return $product_stock_status;
			} else {
				$product_stock_status = "no";
				return $product_stock_status;
			}
		} else {
			$inStock = $this->_stockStateInterface->verifyStock($item->getId(),$item->getStore()->getWebsiteId());
			$product_stock_status =  ($inStock) ? "yes" : "no";
			return $product_stock_status;
		}
    }

}