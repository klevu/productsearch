<?php

namespace Klevu\Search\Plugin\Api;

use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class StockUpdatePlugin
 * @package Klevu\Search\Plugin\Api
 */
class StockUpdatePlugin
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var MagentoProductActionsInterface
     */
    private $magentoProductActions;

    /**
     * StockUpdatePlugin constructor.
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param MagentoProductActionsInterface $magentoProductActions
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        MagentoProductActionsInterface $magentoProductActions
    )
    {
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->magentoProductActions = $magentoProductActions;
    }

    /**
     * @param StockRegistryInterface $subject
     * @param $result
     * @return mixed
     */
    public function afterUpdateStockItemBySku(
        StockRegistryInterface $subject, $result)
    {
        /**
         * updateStockItemBySku will return the ItemId (getItemId)
         * skipping arguments for backward compatibility
         */
        try {
            $storeId = (int)$this->storeManager->getStore()->getId();
            $product = $this->productRepository->getById((int)$result);
            if ($product instanceof \Magento\Catalog\Api\Data\ProductInterface) {
                $this->magentoProductActions->markRecordIntoQueue([$result], 'products', $storeId);
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $result;
        }
        return $result;
    }
}

