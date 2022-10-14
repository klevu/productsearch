<?php

namespace Klevu\Search\Service\Catalog\Product\Review;

use Klevu\Logger\Api\StoreScopeResolverInterface;
use Klevu\Search\Api\Provider\Catalog\Product\Review\RatingDataProviderInterface;
use Klevu\Search\Api\Service\Catalog\Product\IsRatingAttributeAvailableInterface;
use Klevu\Search\Api\Service\Catalog\Product\IsRatingCountAttributeAvailableInterface;
use Klevu\Search\Api\Service\Catalog\Product\Review\UpdateRatingInterface;
use Klevu\Search\Api\Service\Catalog\Product\UpdateRatingsAttributesInterface;
use Klevu\Search\Exception\Catalog\Product\Review\KlevuProductAttributeMissingException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class UpdateRating implements UpdateRatingInterface
{
    const PRODUCT_ID = 'entity_pk_value';
    const RATING_STORES = 'stores';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var StoreScopeResolverInterface
     */
    private $storeScopeResolver;
    /**
     * @var IsRatingAttributeAvailableInterface
     */
    private $isRatingAttributeAvailable;
    /**
     * @var IsRatingCountAttributeAvailableInterface
     */
    private $isRatingCountAttributeAvailable;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var RatingDataProviderInterface
     */
    private $ratingDataProvider;
    /**
     * @var UpdateRatingsAttributesInterface
     */
    private $updateRatingsAttributes;

    /**
     * @param LoggerInterface $logger
     * @param StoreScopeResolverInterface $storeScopeResolver
     * @param IsRatingAttributeAvailableInterface $isRatingAttributeAvailable
     * @param IsRatingCountAttributeAvailableInterface $isRatingCountAttributeAvailable
     * @param StoreManagerInterface $storeManager
     * @param RatingDataProviderInterface $ratingDataProvider
     * @param UpdateRatingsAttributesInterface $updateRatingsAttributes
     */
    public function __construct(
        LoggerInterface $logger,
        StoreScopeResolverInterface $storeScopeResolver,
        IsRatingAttributeAvailableInterface $isRatingAttributeAvailable,
        IsRatingCountAttributeAvailableInterface $isRatingCountAttributeAvailable,
        StoreManagerInterface $storeManager,
        RatingDataProviderInterface $ratingDataProvider,
        UpdateRatingsAttributesInterface $updateRatingsAttributes
    ) {
        $this->logger = $logger;
        $this->storeScopeResolver = $storeScopeResolver;
        $this->isRatingAttributeAvailable = $isRatingAttributeAvailable;
        $this->isRatingCountAttributeAvailable = $isRatingCountAttributeAvailable;
        $this->storeManager = $storeManager;
        $this->ratingDataProvider = $ratingDataProvider;
        $this->updateRatingsAttributes = $updateRatingsAttributes;
    }

    /**
     * @param ProductInterface $product
     *
     * @return void
     * @throws KlevuProductAttributeMissingException
     */
    public function execute(ProductInterface $product)
    {
        $this->validateRatingAttributes();

        $stores = $this->getStoresToUpdate($product);
        if (empty($stores)) {
            return;
        }

        $initialStoreScope = $this->storeScopeResolver->getCurrentStore();
        foreach ($stores as $storeId) {
            try {
                $this->storeScopeResolver->setCurrentStoreById($storeId);
            } catch (NoSuchEntityException $e) {
                $this->storeScopeResolver->setCurrentStore($initialStoreScope);
                $this->logger->warning($e->getMessage(), ['method' => __METHOD__]);
            }

            $ratingData = $this->ratingDataProvider->getData((int)$product->getId(), $storeId);
            $this->updateRatingsAttributes->execute([$ratingData]);
        }
        $this->storeScopeResolver->setCurrentStore($initialStoreScope);
    }

    /**
     * filter out store 0, unless single store mode
     *
     * @param ProductInterface $product
     * @return int[]
     */
    private function getStoresToUpdate(ProductInterface $product)
    {
        $isSingleStoreMode = $this->storeManager->isSingleStoreMode();

        if (method_exists($product, 'getStoreIds')) {
            $storeIds = array_map('intval', $product->getStoreIds());
        } else {
            $storeIds = array_map(static function (StoreInterface $store) {
                return (int)$store->getId();
            }, $this->storeManager->getStores($isSingleStoreMode) ?: []);
        }

        if (!$isSingleStoreMode) {
            $storeIds = array_filter($storeIds);
        }

        return $storeIds;
    }

    /**
     * @return void
     * @throws KlevuProductAttributeMissingException
     */
    private function validateRatingAttributes()
    {
        $ratingAvailable = $this->isRatingAttributeAvailable->execute();
        $reviewCountAvailable = $this->isRatingCountAttributeAvailable->execute();

        switch (true) {
            case $ratingAvailable && $reviewCountAvailable:
                break;

            case !$ratingAvailable && !$reviewCountAvailable:
                throw new KlevuProductAttributeMissingException(
                    __('Klevu product attributes for rating and review count do not exist')
                );
                break; // phpcs:ignore

            case !$ratingAvailable:
                $this->logger->warning(
                    __('Klevu product attribute for rating does not exist')
                );
                break;

            case !$reviewCountAvailable:
                $this->logger->warning(
                    __('Klevu product attribute for review count does not exist')
                );
                break;
        }
    }
}
