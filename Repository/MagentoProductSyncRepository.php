<?php

namespace Klevu\Search\Repository;

use Klevu\Search\Api\MagentoProductSyncRepositoryInterface;
use Klevu\Search\Api\Service\Catalog\Product\JoinParentStatusToSelectInterface;
use Klevu\Search\Api\Service\Catalog\Product\JoinParentVisibilityToSelectInterface;
use Klevu\Search\Api\Service\Catalog\Product\AddParentProductStockToCollectionInterface;
use Klevu\Search\Model\Product\ProductIndividualInterface;
use Klevu\Search\Model\Product\ProductParentInterface;
use Klevu\Search\Model\Product\ResourceModel\Product as KlevuProductResourceModel;
use Klevu\Search\Model\Product\ResourceModel\Product\Collection as KlevuProductCollection;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class MagentoProductSyncRepository implements MagentoProductSyncRepositoryInterface
{
    const XML_PATH_PRODUCT_SYNC_CATALOGVISIBILITY = "klevu_search/product_sync/catalogvisibility";
    const MAX_ITERATIONS = 100000;
    const CATALOG_PRODUCT_ENTITY_ALIAS = 'e';
    const CATALOG_PRODUCT_SUPER_LINK_ALIAS = 'l';
    const STOCK_STATUS_ALIAS = 'stock_status';
    const PARENT_STOCK_STATUS_ALIAS = 'parent_stock_status';
    const PARENT_CATALOG_PRODUCT_ENTITY_ALIAS = 'parent';

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
     * @var OptionProvider
     */
    private $optionProvider;
    /**
     * @var JoinParentVisibilityToSelectInterface
     */
    private $joinParentVisibilityToSelectService;
    /**
     * @var JoinParentStatusToSelectInterface
     */
    private $joinParentStatusToSelectService;
    /**
     * @var array
     */
    private $visibility = [];
    /**
     * @var AddParentProductStockToCollectionInterface|null
     */
    private $addParentProductStockToCollection;

    /**
     * @param ProductIndividualInterface $klevuProductIndividual
     * @param ProductParentInterface $klevuProductParent
     * @param ScopeConfigInterface $scopeConfig
     * @param KlevuProductResourceModel $productResourceModel
     * @param KlevuProductCollection $productCollection
     * @param OptionProvider|null $optionProvider
     * @param JoinParentVisibilityToSelectInterface|null $joinParentVisibilityToSelectService
     * @param JoinParentStatusToSelectInterface|null $joinParentStatusToSelectService
     * @param AddParentProductStockToCollectionInterface|null $addParentProductStockToCollection
     */
    public function __construct(
        ProductIndividualInterface $klevuProductIndividual,
        ProductParentInterface $klevuProductParent,
        ScopeConfigInterface $scopeConfig,
        KlevuProductResourceModel $productResourceModel,
        KlevuProductCollection $productCollection,
        OptionProvider $optionProvider = null,
        JoinParentVisibilityToSelectInterface $joinParentVisibilityToSelectService = null,
        JoinParentStatusToSelectInterface $joinParentStatusToSelectService = null,
        AddParentProductStockToCollectionInterface $addParentProductStockToCollection = null
    ) {
        $this->klevuProductIndividual = $klevuProductIndividual;
        $this->klevuProductParent = $klevuProductParent;
        $this->scopeConfig = $scopeConfig;
        $this->productResourceModel = $productResourceModel;
        $this->productCollection = $productCollection;

        $objectManager = ObjectManager::getInstance();
        $this->optionProvider = $optionProvider ?: $objectManager->get(OptionProvider::class);
        $this->joinParentVisibilityToSelectService = $joinParentVisibilityToSelectService
            ?: $objectManager->get(JoinParentVisibilityToSelectInterface::class);
        // Hardcoded FQCN as using virtualType - concrete class requires status parameter set via di.xml
        // phpcs:disable Magento2.PHP.LiteralNamespaces.LiteralClassUsage
        $this->joinParentStatusToSelectService = $joinParentStatusToSelectService
            ?: $objectManager->get('\Klevu\Search\Service\Catalog\Product\JoinParentStatusToSelect\Enabled');
        // phpcs:enable Magento2.PHP.LiteralNamespaces.LiteralClassUsage
        $this->addParentProductStockToCollection = $addParentProductStockToCollection
            ?: $objectManager->get(AddParentProductStockToCollectionInterface::class);
    }

    /**
     * @param StoreInterface $store
     * @param int $visibility self::NOT_VISIBLE_EXCLUDED|self::NOT_VISIBLE_INCLUDED|self::NOT_VISIBLE_ONLY
     * @param bool $includeOosProducts
     *
     * @return ProductCollection
     */
    public function getProductIdsCollection(
        StoreInterface $store,
        $visibility = self::NOT_VISIBLE_EXCLUDED,
        $includeOosProducts = true
    ) {
        return $this->getInitCollectionByType(
            $store,
            $this->klevuProductIndividual->getProductIndividualTypeArray(),
            $visibility,
            $includeOosProducts
        );
    }

    /**
     * @param StoreInterface $store
     * @param int $parentVisibility self::NOT_VISIBLE_EXCLUDED|self::NOT_VISIBLE_INCLUDED|self::NOT_VISIBLE_ONLY
     * @param bool $includeOosProducts
     *
     * @return ProductCollection
     */
    public function getChildProductIdsCollection(
        StoreInterface $store,
        $parentVisibility = self::NOT_VISIBLE_EXCLUDED,
        $includeOosProducts = true
    ) {
        $return = $this->getInitCollectionByType(
            $store,
            $this->klevuProductIndividual->getProductChildTypeArray(),
            null,
            $includeOosProducts
        );

        $select = $return->getSelect();
        $resource = $return->getResource();
        $select->distinct();
        $select->joinInner(
            [self::CATALOG_PRODUCT_SUPER_LINK_ALIAS => $resource->getTable('catalog_product_super_link')],
            sprintf(
                '%s.entity_id = %s.product_id',
                self::CATALOG_PRODUCT_ENTITY_ALIAS,
                self::CATALOG_PRODUCT_SUPER_LINK_ALIAS
            ),
            []
        );
        $storeId = (int)$store->getId();

        $this->joinParentStatusToSelectService->setTableAlias(
            'catalog_product_super_link',
            self::CATALOG_PRODUCT_SUPER_LINK_ALIAS
        );
        $this->joinParentStatusToSelectService->setTableAlias(
            'parent_catalog_product_entity',
            self::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS
        );
        $select = $this->joinParentStatusToSelectService->execute($select, $storeId);

        if ($parentVisibility !== static::NOT_VISIBLE_INCLUDED) {
            $this->joinParentVisibilityToSelectService->setTableAlias(
                'catalog_product_super_link',
                self::CATALOG_PRODUCT_SUPER_LINK_ALIAS
            );
            $this->joinParentVisibilityToSelectService->setTableAlias(
                'parent_catalog_product_entity',
                self::PARENT_CATALOG_PRODUCT_ENTITY_ALIAS
            );
            $this->joinParentVisibilityToSelectService->execute($select, $storeId);
        }
        $this->addParentProductStockToCollection->execute($return, $store, $includeOosProducts);

        return $return;
    }

    /**
     * @param StoreInterface $store
     * @param bool $includeOosProducts
     *
     * @return array
     */
    public function getParentProductIds(StoreInterface $store, $includeOosProducts = true)
    {
        $parentProductTypeArray = $this->klevuProductParent->getProductParentTypeArray();
        $productCollection = $this->getInitCollectionByType(
            $store,
            $parentProductTypeArray,
            static::NOT_VISIBLE_EXCLUDED,
            $includeOosProducts
        );

        $enabledParentIds = [];
        $lastEntityId = 0;
        $i = 0;
        while (true) {
            $enabledParentIds[$i] = $this->productResourceModel->getBatchDataForCollection(
                $productCollection,
                $store,
                [],
                $lastEntityId
            );
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
     * @param string[] $productIds
     * @param int $storeId
     * @param bool $includeOosParents
     *
     * @return array[]
     * @throws NoSuchEntityException
     */
    public function getParentProductRelations(
        $productIds,
        $storeId = Store::DEFAULT_STORE_ID,
        $includeOosParents = true
    ) {
        return $this->productResourceModel->getParentProductRelations($productIds, $storeId, $includeOosParents);
    }

    /**
     * @param ProductCollection $productCollection
     * @param StoreInterface $store
     * @param array|null $productIds
     * @param int|null $lastEntityId
     *
     * @return array
     */
    public function getBatchDataForCollection(
        ProductCollection $productCollection,
        StoreInterface $store,
        $productIds = [],
        $lastEntityId = null
    ) {
        return $this->productResourceModel->getBatchDataForCollection(
            $productCollection,
            $store,
            $productIds,
            $lastEntityId
        );
    }

    /**
     * @param StoreInterface $store
     * @param array $productTypeArray
     * @param int|null $visibility
     * @param bool $includeOosProducts
     *
     * @return ProductCollection
     */
    private function getInitCollectionByType(
        StoreInterface $store,
        array $productTypeArray,
        $visibility,
        $includeOosProducts = true
    ) {
        $visibilityArray = (is_int($visibility))
            ? $this->getVisibility($store, $visibility)
            : [];

        return $this->productCollection->initCollectionByType(
            $store,
            $productTypeArray,
            $visibilityArray,
            $includeOosProducts
        );
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
        return $this->scopeConfig->isSetFlag(
            static::XML_PATH_PRODUCT_SYNC_CATALOGVISIBILITY,
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        );
    }
}
