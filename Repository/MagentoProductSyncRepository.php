<?php

namespace Klevu\Search\Repository;

use Klevu\Search\Api\MagentoProductSyncRepositoryInterface;
use Klevu\Search\Model\Product\ProductIndividualInterface;
use Klevu\Search\Model\Product\ProductParentInterface;
use Klevu\Search\Model\Product\ResourceModel\Product as KlevuProductResourceModel;
use Klevu\Search\Model\Product\ResourceModel\Product\Collection as KlevuProductCollection;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

class MagentoProductSyncRepository implements MagentoProductSyncRepositoryInterface
{
    const XML_PATH_PRODUCT_SYNC_CATALOGVISIBILITY = "klevu_search/product_sync/catalogvisibility";
    const MAX_ITERATIONS = 100000;

    /**
     * @var ProductIndividualInterface
     */
    private $klevuProductIndividual;
    /**
     * @var ProductParentInterface
     */
    private $klevuProductParent;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var KlevuProductResourceModel
     */
    private $productResourceModel;
    /**
     * @var KlevuProductCollection
     */
    private $productCollection;
    /**
     * @var array
     */
    private $visibility = [];

    public function __construct(
        ProductIndividualInterface $klevuProductIndividual,
        ProductParentInterface $klevuProductParent,
        ScopeConfigInterface $scopeConfig,
        KlevuProductResourceModel $productResourceModel,
        KlevuProductCollection $productCollection
    ) {
        $this->klevuProductIndividual = $klevuProductIndividual;
        $this->klevuProductParent = $klevuProductParent;
        $this->scopeConfig = $scopeConfig;
        $this->productResourceModel = $productResourceModel;
        $this->productCollection = $productCollection;
    }

    /**
     * @param StoreInterface $store
     * @param int|null $visibility self::NOT_VISIBLE_EXCLUDED|self::NOT_VISIBLE_INCLUDED|self::NOT_VISIBLE_ONLY
     *
     * @return ProductCollection
     */
    public function getProductIdsCollection(StoreInterface $store, $visibility = self::NOT_VISIBLE_EXCLUDED)
    {
        $productTypeArray = $this->klevuProductIndividual->getProductIndividualTypeArray();

        return $this->getInitCollectionByType($store, $productTypeArray, $visibility);
    }

    /**
     * @param StoreInterface $store
     *
     * @return ProductCollection
     */
    public function getChildProductIdsCollection(StoreInterface $store)
    {
        $productTypeArray = $this->klevuProductIndividual->getProductChildTypeArray();

        return $this->getInitCollectionByType($store, $productTypeArray, static::NOT_VISIBLE_ONLY);
    }

    /**
     * @param StoreInterface $store
     *
     * @return array
     */
    public function getParentProductIds(StoreInterface $store)
    {
        $parentProductTypeArray = $this->klevuProductParent->getProductParentTypeArray();
        $productCollection = $this->getInitCollectionByType($store, $parentProductTypeArray, static::NOT_VISIBLE_INCLUDED);

        $enabledParentIds = [];
        $lastEntityId = 0;
        $i = 0;
        while (true) {
            $enabledParentIds[$i] = $this->productResourceModel->getBatchDataForCollection($productCollection, $store, [], $lastEntityId);
            if (!$enabledParentIds[$i]) {
                break;
            }
            $lastEntityId = (int)max($enabledParentIds[$i]);
            if (++$i >= static::MAX_ITERATIONS) {
                break;
            }
        }

        return array_merge([], ...array_filter($enabledParentIds));
    }

    /**
     * @param StoreInterface $store
     *
     * @return int
     */
    public function getMaxProductId(StoreInterface $store)
    {
        return $this->productCollection->getMaxProductId($store);
    }

    /**
     * @param array $productIds
     *
     * @return array
     */
    public function getParentProductRelations($productIds)
    {
        return $this->productResourceModel->getParentProductRelations($productIds);
    }

    /**
     * @param ProductCollection $productCollection
     * @param StoreInterface $store
     * @param array|null $productIds
     * @param int|null $lastEntityId
     *
     * @return array
     */
    public function getBatchDataForCollection(ProductCollection $productCollection, StoreInterface $store, $productIds = [], $lastEntityId = null)
    {
        return $this->productResourceModel->getBatchDataForCollection($productCollection, $store, $productIds, $lastEntityId);
    }

    /**
     * @param StoreInterface $store
     * @param array $productTypeArray
     * @param int $visibility
     *
     * @return ProductCollection
     */
    private function getInitCollectionByType(StoreInterface $store, array $productTypeArray, $visibility)
    {
        $visibilityArray = $this->getVisibility($store, $visibility);

        return $this->productCollection->initCollectionByType($store, $productTypeArray, $visibilityArray);
    }

    /**
     * @param StoreInterface $store
     * @param int $visibilityLevel
     *
     * @return array
     */
    private function getVisibility(StoreInterface $store, $visibilityLevel)
    {
        if (!isset($this->visibility[$visibilityLevel])) {
            if ($visibilityLevel === static::NOT_VISIBLE_ONLY) {
                $this->visibility[$visibilityLevel] = [Visibility::VISIBILITY_NOT_VISIBLE];
            } else {
                $this->visibility[$visibilityLevel] = [Visibility::VISIBILITY_BOTH, Visibility::VISIBILITY_IN_SEARCH];
                if ($visibilityLevel === static::NOT_VISIBLE_INCLUDED) {
                    $this->visibility[$visibilityLevel][] = Visibility::VISIBILITY_NOT_VISIBLE;
                }
                if ($this->includeVisibilityInCatalog($store)) {
                    $this->visibility[$visibilityLevel][] = Visibility::VISIBILITY_IN_CATALOG;
                }
            }
        }

        return $this->visibility[$visibilityLevel];
    }

    /**
     * @param StoreInterface $store
     *
     * @return bool
     */
    private function includeVisibilityInCatalog($store)
    {
        $isSetFlag = $this->scopeConfig->isSetFlag(
            static::XML_PATH_PRODUCT_SYNC_CATALOGVISIBILITY,
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        );

        return !$isSetFlag;
    }
}
