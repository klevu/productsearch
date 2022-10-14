<?php

namespace Klevu\Search\Provider\Catalog\Product\Review;

use Klevu\Search\Api\Provider\Catalog\Product\Review\RatingDataProviderInterface;
use Klevu\Search\Api\Provider\Catalog\Product\Review\ReviewCountDataProviderInterface;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\Rating;
use Magento\Review\Model\RatingFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class MagentoRatingDataProvider implements RatingDataProviderInterface
{
    /**
     * @var RatingFactory
     */
    private $ratingFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ReviewCountDataProviderInterface
     */
    private $reviewCountDataProvider;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RatingFactory $ratingFactory
     * @param StoreManagerInterface $storeManager
     * @param ReviewCountDataProviderInterface $reviewCountDataProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        RatingFactory $ratingFactory,
        StoreManagerInterface $storeManager,
        ReviewCountDataProviderInterface $reviewCountDataProvider,
        LoggerInterface $logger
    ) {
        $this->ratingFactory = $ratingFactory;
        $this->storeManager = $storeManager;
        $this->reviewCountDataProvider = $reviewCountDataProvider;
        $this->logger = $logger;
    }

    /**
     * @param int $productId
     * @param int|null $storeId
     *
     * @return array
     */
    public function getData($productId, $storeId = null)
    {
        $return = [
            RatingDataMapper::RATING_AVERAGE => null,
            RatingDataMapper::RATING_COUNT => 0,
            RatingDataMapper::RATING_PRODUCT_ID => (int)$productId,
            RatingDataMapper::RATING_STORE => $storeId ? (int)$storeId : (int)Store::DEFAULT_STORE_ID,
            RatingDataMapper::RATING_SUM => null,
            RatingDataMapper::REVIEW_COUNT => 0,
        ];

        try {
            $storeId = $this->setCurrentStore($storeId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(
                sprintf("Exception thrown in %s::%s - %s", __CLASS__, __METHOD__, $e->getMessage())
            );

            return $return;
        }

        $return[RatingDataMapper::RATING_STORE] = (int)$storeId;
        $return[RatingDataMapper::REVIEW_COUNT] = (int)$this->reviewCountDataProvider->getData(
            (int)$productId,
            $storeId
        );

        list($ratingSum, $ratingCount) = $this->getRatingData((int)$productId);
        if (!$this->isRatingDataValid($ratingSum, $ratingCount)) {
            $this->logger->debug(
                sprintf("Rating Data invalid for StoreId: %s, productId: %s", $storeId, $productId)
            );

            return $return;
        }

        if (!$ratingCount) {
            return $return;
        }

        $return[RatingDataMapper::RATING_AVERAGE] = (float)$ratingSum / (int)$ratingCount;
        $return[RatingDataMapper::RATING_COUNT] = (int)$ratingCount;
        $return[RatingDataMapper::RATING_SUM] = (float)$ratingSum;

        return $return;
    }

    /**
     * @param int|null $storeId
     *
     * @return int
     * @throws NoSuchEntityException
     */
    private function setCurrentStore($storeId = null)
    {
        if (null === $storeId) {
            $store = $this->storeManager->getStore();
            $storeId = $store->getId();
        }
        $this->storeManager->setCurrentStore($storeId);

        return (int)$storeId;
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    private function getRatingData($productId)
    {
        $rating = $this->ratingFactory->create();
        /** @var Rating $ratingSummary */
        $ratingSummary = $rating->getEntitySummary($productId);
        $ratingSum = $ratingSummary->getData(RatingDataMapper::RATING_SUM);
        $ratingCount = $ratingSummary->getData(RatingDataMapper::RATING_COUNT);

        return [$ratingSum, $ratingCount];
    }

    /**
     * @param int|null $ratingSum
     * @param int|null $ratingCount
     *
     * @return bool
     */
    private function isRatingDataValid($ratingSum, $ratingCount)
    {
        return (null === $ratingSum || (is_numeric($ratingSum) && (float)$ratingSum >= 0))
            && (null === $ratingCount || (is_numeric($ratingCount) && (int)$ratingCount) > 0)
            && !($ratingSum && (int)$ratingCount < 1);
    }
}
