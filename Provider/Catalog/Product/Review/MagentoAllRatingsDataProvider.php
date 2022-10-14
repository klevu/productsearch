<?php

namespace Klevu\Search\Provider\Catalog\Product\Review;

use Klevu\Search\Api\Provider\Catalog\Product\Review\AllRatingsDataProviderInterface;
use Klevu\Search\Api\Provider\Catalog\Product\Review\AllReviewCountsDataProviderInterface;
use Klevu\Search\Api\Provider\Catalog\Product\Review\ProductsWithRatingAttributeDataProviderInterface;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use Magento\Framework\DB\Select;
use Magento\Reports\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\ResourceModel\Rating\Collection as RatingCollection;
use Magento\Review\Model\ResourceModel\Rating\CollectionFactory as RatingCollectionFactory;
use Magento\Review\Model\Review;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;

class MagentoAllRatingsDataProvider implements AllRatingsDataProviderInterface
{
    /**
     * @var RatingCollectionFactory
     */
    private $ratingCollectionFactory;
    /**
     * @var ReviewCollectionFactory
     */
    private $allReviewCountsDataProvider;
    /**
     * @var ProductsWithRatingAttributeDataProviderInterface
     */
    private $productsWithRatingAttributeDataProvider;
    /**
     * @var array
     */
    private $reviewStatus;
    /**
     * @var bool
     */
    private $isActive;

    /**
     * @param RatingCollectionFactory $ratingCollectionFactory
     * @param AllReviewCountsDataProviderInterface $allReviewCountsDataProvider
     * @param ProductsWithRatingAttributeDataProviderInterface $productsWithRatingAttributeDataProvider
     * @param array $reviewStatus
     * @param bool $isActive
     */
    public function __construct(
        RatingCollectionFactory $ratingCollectionFactory,
        AllReviewCountsDataProviderInterface $allReviewCountsDataProvider,
        ProductsWithRatingAttributeDataProviderInterface $productsWithRatingAttributeDataProvider,
        array $reviewStatus = [],
        $isActive = true
    ) {
        $this->ratingCollectionFactory = $ratingCollectionFactory;
        $this->allReviewCountsDataProvider = $allReviewCountsDataProvider;
        $this->productsWithRatingAttributeDataProvider = $productsWithRatingAttributeDataProvider;
        $this->reviewStatus = $reviewStatus ?: [Review::STATUS_APPROVED];
        $this->isActive = $isActive;
    }

    /**
     * @param StoreInterface|int $store
     *
     * @return array
     */
    public function getData($store)
    {
        $storeId = ($store instanceof StoreInterface) ? (int)$store->getId() : (int)$store;
        $ratingData = $storeId !== Store::DEFAULT_STORE_ID
            ? $this->getRatingData($storeId)
            : []; // Never write data globally, but allow merge below to clear out any existing global values

        $return = $this->addReviewCounts($storeId, $ratingData);

        $existingProductIds = $this->productsWithRatingAttributeDataProvider->getProductIdsForStore($storeId);
        $productIdsToClear = array_diff(
            $existingProductIds,
            array_column($return, RatingDataMapper::RATING_PRODUCT_ID)
        );

        return array_merge(
            array_map(static function ($productId) use ($storeId) {
                return [
                    RatingDataMapper::RATING_AVERAGE => null,
                    RatingDataMapper::RATING_COUNT => 0,
                    RatingDataMapper::RATING_PRODUCT_ID => (int)$productId,
                    RatingDataMapper::RATING_STORE => $storeId,
                    RatingDataMapper::RATING_SUM => null,
                    RatingDataMapper::REVIEW_COUNT => ($storeId !== Store::DEFAULT_STORE_ID) ? 0 : null,
                ];
            }, $productIdsToClear),
            $return
        );
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    private function getRatingData($storeId)
    {
        $ratingCollection = $this->getRatingCollection((int)$storeId);

        return $this->extendCollection($ratingCollection, (int)$storeId);
    }

    /**
     * @param int $storeId
     *
     * @return RatingCollection
     */
    private function getRatingCollection($storeId)
    {
        $ratingCollection = $this->ratingCollectionFactory->create();
        $ratingCollection->setStoreFilter($storeId);
        $ratingCollection->setActiveFilter($this->isActive);

        return $ratingCollection;
    }

    /**
     * @param RatingCollection $ratingCollection
     * @param int $storeId
     *
     * @return array
     */
    private function extendCollection(RatingCollection $ratingCollection, $storeId)
    {
        $connection = $ratingCollection->getConnection();

        $sumCond = new \Zend_Db_Expr("SUM(rating_option_vote.{$connection->quoteIdentifier('percent')})");
        $countCond = new \Zend_Db_Expr('COUNT(*)');

        $select = $ratingCollection->getSelect();
        $select->reset(Select::COLUMNS);
        $select->from(
            ['rating_option_vote' => $ratingCollection->getTable('rating_option_vote')],
            [
                RatingDataMapper::RATING_PRODUCT_ID => 'rating_option_vote.entity_pk_value',
                RatingDataMapper::RATING_SUM => $sumCond,
                RatingDataMapper::RATING_COUNT => $countCond,
                RatingDataMapper::RATING_STORE => 'store.store_id'
            ]
        );
        $select->join(
            ['review_store' => $ratingCollection->getTable('review_store')],
            'rating_option_vote.review_id=review_store.review_id',
            []
        );
        $select->join(
            ['review' => $ratingCollection->getTable('review')],
            'review_store.review_id=review.review_id',
            []
        );
        $select->where('review_store.store_id IN (:store_id)');
        $select->where('review.status_id IN (:status_codes)');
        $select->group('rating_option_vote.entity_pk_value');
        $select->group('review_store.store_id');
        $select->group('main_table.rating_id');

        $bind = [
            'status_codes' => implode(',', $this->reviewStatus),
            'store_id' => $storeId,
        ];

        return $connection->fetchAll($select, $bind);
    }

    /**
     * @param int $storeId
     * @param array $ratingData
     *
     * @return array
     */
    private function addReviewCounts($storeId, array $ratingData)
    {
        $reviewCounts = $this->allReviewCountsDataProvider->getData($storeId);

        return array_map(static function ($rating) use ($reviewCounts) {
            $productId = isset($rating[RatingDataMapper::RATING_PRODUCT_ID]) ?
                $rating[RatingDataMapper::RATING_PRODUCT_ID] :
                null;

            $rating[RatingDataMapper::REVIEW_COUNT] = isset($reviewCounts[$productId]) ?
                $reviewCounts[$productId] :
                null;

            return $rating;
        }, $ratingData);
    }
}
