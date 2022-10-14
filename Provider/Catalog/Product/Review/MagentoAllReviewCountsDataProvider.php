<?php

namespace Klevu\Search\Provider\Catalog\Product\Review;

use Klevu\Search\Api\Provider\Catalog\Product\Review\AllReviewCountsDataProviderInterface;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use Magento\Framework\DB\Select;
use Magento\Reports\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\Review;

class MagentoAllReviewCountsDataProvider implements AllReviewCountsDataProviderInterface
{
    /**
     * @var ReviewCollectionFactory
     */
    private $reviewCollectionFactory;
    /**
     * @var array
     */
    private $reviewStatus;

    /**
     * @param ReviewCollectionFactory $reviewCollectionFactory
     * @param array $reviewStatus
     */
    public function __construct(
        ReviewCollectionFactory $reviewCollectionFactory,
        array $reviewStatus = []
    ) {
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->reviewStatus = $reviewStatus ?: [Review::STATUS_APPROVED];
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    public function getData($storeId)
    {
        $reviewsCounts = $this->getReviewsCountsData($storeId);

        return $this->formatData($reviewsCounts);
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    private function getReviewsCountsData($storeId)
    {
        $reviewCollection = $this->reviewCollectionFactory->create();
        $connection = $reviewCollection->getConnection();

        $reviewCollection->addStoreFilter($storeId);

        $countCond = new \Zend_Db_Expr('COUNT(*)');

        $select = $reviewCollection->getSelect();
        $select->reset(Select::COLUMNS);
        $select->from(
            [],
            [
                RatingDataMapper::RATING_PRODUCT_ID => 'main_table.entity_pk_value',
                RatingDataMapper::REVIEW_COUNT => $countCond,
            ]
        );
        $select->where('main_table.status_id IN (:status_id)');
        $select->group('main_table.entity_pk_value');

        $bind = [
            'status_id' => implode(',', $this->reviewStatus),
        ];

        return $connection->fetchAll($select, $bind);
    }

    /**
     * @param array $reviewsCounts
     *
     * @return array
     */
    private function formatData(array $reviewsCounts)
    {
        $data = array_column(
            $reviewsCounts,
            RatingDataMapper::REVIEW_COUNT,
            RatingDataMapper::RATING_PRODUCT_ID
        );

        return array_filter($data, static function ($productId) {
            return null !== $productId;
        }, ARRAY_FILTER_USE_KEY);
    }
}
