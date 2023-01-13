<?php

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\GetStockStatusByIdInterface;
use Klevu\Search\Api\Service\Catalog\Product\Stock\GetCompositeProductStockStatusInterface;
use Klevu\Search\Api\Service\Catalog\Product\StockServiceInterface;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Helper\Stock as MagentoStockHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Downloadable\Model\Product\Type as DownloadableProductType;
use Magento\Framework\App\ObjectManager;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedType;

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
     * @var MagentoStockHelper
     */
    private $magentoStockHelper;
    /**
     * @var GetCompositeProductStockStatusInterface
     */
    private $getCompositeProductStockStatus;
    /**
     * @var string[]
     */
    private $cachedKlevuStockStatus = [];

    /**
     * @param StockRegistryInterface $stockRegistryInterface
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param GetStockStatusByIdInterface|null $getStockStatusById
     * @param MagentoStockHelper|null $magentoStockHelper
     * @param GetCompositeProductStockStatusInterface|null $getCompositeProductStockStatus
     */
    public function __construct(
        StockRegistryInterface $stockRegistryInterface,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaInterfaceFactory,
        StockItemRepositoryInterface $stockItemRepository,
        GetStockStatusByIdInterface $getStockStatusById = null,
        MagentoStockHelper $magentoStockHelper = null,
        GetCompositeProductStockStatusInterface $getCompositeProductStockStatus = null
    ) {
        $this->_stockRegistryInterface = $stockRegistryInterface;
        $this->stockItemCriteriaInterfaceFactory = $stockItemCriteriaInterfaceFactory;
        $this->stockItemRepository = $stockItemRepository;
        $objectManager = ObjectManager::getInstance();
        $this->getStockStatusById = $getStockStatusById
            ?: $objectManager->get(GetStockStatusByIdInterface::class);
        $this->magentoStockHelper = $magentoStockHelper
            ?: $objectManager->get(MagentoStockHelper::class);
        $this->getCompositeProductStockStatus = $getCompositeProductStockStatus
            ?: $objectManager->get(GetCompositeProductStockStatusInterface::class);
    }

    /**
     * @return void
     */
    public function clearCache()
    {
        $this->cache = [];
        $this->cachedKlevuStockStatus = [];
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
        $cacheKey = implode('::', [
            $product->getSku(),
            $parentProduct ? $parentProduct->getSku() : '',
            (int)$websiteId
        ]);

        if (empty($this->cachedKlevuStockStatus[$cacheKey])) {
            $this->cachedKlevuStockStatus[$cacheKey] = $this->isInStock($product, $parentProduct, $websiteId) ?
                static::KLEVU_IN_STOCK :
                static::KLEVU_OUT_OF_STOCK;
        }

        return $this->cachedKlevuStockStatus[$cacheKey];
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
        $isCacheable = in_array(
            $product->getTypeId(),
            [
                ProductType::TYPE_SIMPLE,
                ProductType::TYPE_VIRTUAL,
                DownloadableProductType::TYPE_DOWNLOADABLE,
                'giftcard',
            ],
            true
        );
        if ($isCacheable && isset($this->cache[$cacheId][$product->getId()])) {
            return $this->cache[$cacheId][$product->getId()];
        }

        if (in_array($product->getTypeId(), [BundleType::TYPE_CODE, ConfigurableType::TYPE_CODE], true)) {
            $stockStatus = $this->getCompositeProductStockStatus->execute($product, [], null);
        } else {
            $this->magentoStockHelper->assignStatusToProduct($product);
            $product->unsetData('salable');
            $product->unsetData('is_salable');
            $isSaleable = method_exists($product, 'isSaleable')
                ? $product->isSaleable()
                : true;
            $store = $product->getStore();
            $stockStatusInterface = $this->_stockRegistryInterface->getStockStatus(
                $product->getId(),
                $store->getWebsiteId()
            );

            if (GroupedType::TYPE_CODE === $product->getTypeId()) {
                $isAvailable = method_exists($product, 'isAvailable')
                    ? $product->isAvailable()
                    : $isSaleable;

                $stockStatus = $isAvailable
                    && $this->hasAssociatedProducts($product)
                    && (bool)$stockStatusInterface->getStockStatus();
            } else {
                $stockStatus = (bool)$stockStatusInterface->getStockStatus() && $isSaleable;
            }
        }

        if ($isCacheable) {
            $this->cache[$cacheId][$product->getId()] = $stockStatus;
        }

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

    /**
     * @param ProductInterface $product
     *
     * @return bool
     */
    private function hasAssociatedProducts(ProductInterface $product)
    {
        $associatedProducts = [];
        $productTypeInstance = $product->getTypeInstance();
        if (GroupedType::TYPE_CODE === $product->getTypeId()) {
            $associatedProducts = $productTypeInstance->getAssociatedProducts($product);
        }

        return (bool)count($associatedProducts);
    }
}
