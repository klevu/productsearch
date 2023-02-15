<?php

namespace Klevu\Search\Service\Catalog\Product;

use Exception;
use Klevu\Search\Api\Service\Catalog\Product\IsRatingAttributeAvailableInterface;
use Klevu\Search\Api\Service\Catalog\Product\IsRatingCountAttributeAvailableInterface;
use Klevu\Search\Api\Service\Catalog\Product\Review\RatingDataMapperInterface;
use Klevu\Search\Api\Service\Catalog\Product\UpdateRatingsAttributesInterface;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingDataException;
use Klevu\Search\Exception\Catalog\Product\Review\InvalidRatingDataMappingKey;
use Klevu\Search\Model\Attribute\Rating as RatingAttribute;
use Klevu\Search\Model\Attribute\ReviewCount as ReviewCountAttribute;
use Klevu\Search\Service\Catalog\Product\Review\RatingDataMapper;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\Product\ActionFactory;
use Psr\Log\LoggerInterface;

class UpdateRatingsAttributes implements UpdateRatingsAttributesInterface
{
    /**
     * @var ActionFactory
     */
    private $actionFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var RatingDataMapperInterface
     */
    private $ratingDataMapper;
    /**
     * @var ProductAction
     */
    private $productAction;
    /**
     * @var IsRatingAttributeAvailableInterface
     */
    private $isRatingAttributeAvailable;
    /**
     * @var IsRatingCountAttributeAvailableInterface
     */
    private $isRatingCountAttributeAvailable;

    /**
     * @param ActionFactory $actionFactory
     * @param LoggerInterface $logger
     * @param RatingDataMapperInterface $ratingDataMapper
     * @param IsRatingAttributeAvailableInterface $isRatingAttributeAvailable
     * @param IsRatingCountAttributeAvailableInterface $isRatingCountAttributeAvailable
     */
    public function __construct(
        ActionFactory $actionFactory,
        LoggerInterface $logger,
        RatingDataMapperInterface $ratingDataMapper,
        IsRatingAttributeAvailableInterface $isRatingAttributeAvailable,
        IsRatingCountAttributeAvailableInterface $isRatingCountAttributeAvailable
    ) {
        $this->actionFactory = $actionFactory;
        $this->logger = $logger;
        $this->ratingDataMapper = $ratingDataMapper;
        $this->isRatingAttributeAvailable = $isRatingAttributeAvailable;
        $this->isRatingCountAttributeAvailable = $isRatingCountAttributeAvailable;
    }

    /**
     * @param array[] $ratings
     *
     * @return void
     */
    public function execute(array $ratings)
    {
        try {
            $ratings = $this->ratingDataMapper->execute($ratings);
        } catch (InvalidRatingDataMappingKey $exception) {
            $this->logger->error($exception->getMessage());

            return;
        }
        foreach ($ratings as $rating) {
            $this->processRating($rating);
        }
    }

    /**
     * @param array $rating
     *
     * @return void
     */
    private function processRating(array $rating)
    {
        try {
            $this->validateRating($rating);
            if (!isset($rating[RatingDataMapper::RATING_AVERAGE])) {
                $rating[RatingDataMapper::RATING_AVERAGE] = $this->calculateAverage($rating);
            }
        } catch (InvalidRatingDataException $exception) {
            $this->logger->error($exception->getMessage(), ['rating' => $rating]);

            return;
        }
        try {
            $this->updateRatingAttributes($rating);
            $this->logger->debug(
                sprintf("Rating is updated for product id %s", $rating[RatingDataMapper::RATING_PRODUCT_ID]),
                [
                    'rating' => $rating,
                ]
            );
        } catch (Exception $exception) {
            $this->logger->error(
                sprintf(
                    'There was an error updating the the rating for product id %s',
                    $rating[RatingDataMapper::RATING_PRODUCT_ID]
                ),
                [
                    'rating' => $rating,
                ]
            );
        }
    }

    /**
     * @param array $rating
     *
     * @return int|null
     */
    private function calculateAverage(array $rating)
    {
        if (!isset($rating[RatingDataMapper::RATING_SUM]) ||
            !isset($rating[RatingDataMapper::RATING_COUNT]) ||
            (int)$rating[RatingDataMapper::RATING_COUNT] === 0
        ) {
            return null;
        }

        return (int)($rating[RatingDataMapper::RATING_SUM] / $rating[RatingDataMapper::RATING_COUNT]);
    }

    /**
     * @param array $rating
     *
     * @return void
     * @throws Exception
     */
    private function updateRatingAttributes(array $rating)
    {
        if (!isset($rating[RatingDataMapper::RATING_PRODUCT_ID], $rating[RatingDataMapper::RATING_STORE])
            || !array_key_exists(RatingDataMapper::RATING_AVERAGE, $rating)
            || !array_key_exists(RatingDataMapper::REVIEW_COUNT, $rating)
        ) {
            return;
        }

        $productAction = $this->getProductAction();
        $attrData = [];
        if ($this->isRatingAttributeAvailable->execute()) {
            $attrData[RatingAttribute::ATTRIBUTE_CODE] = $rating[RatingDataMapper::RATING_AVERAGE];
        }
        if ($this->isRatingCountAttributeAvailable->execute()) {
            $attrData[ReviewCountAttribute::ATTRIBUTE_CODE] = $rating[RatingDataMapper::REVIEW_COUNT];
        }
        if (!$attrData) {
            return;
        }

        $productAction->updateAttributes(
            [$rating[RatingDataMapper::RATING_PRODUCT_ID]],
            $attrData,
            $rating[RatingDataMapper::RATING_STORE]
        );
    }

    /**
     * @return ProductAction
     */
    private function getProductAction()
    {
        if (null === $this->productAction) {
            $this->productAction = $this->actionFactory->create();
        }

        return $this->productAction;
    }

    /**
     * @param array $rating
     *
     * @return void
     * @throws InvalidRatingDataException
     */
    private function validateRating(array $rating)
    {
        if (!array_key_exists(RatingDataMapper::REVIEW_COUNT, $rating)) {
            throw new InvalidRatingDataException(
                __(
                    'Rating data missing %1',
                    isset($this->fieldMapping[RatingDataMapper::REVIEW_COUNT]) ?
                        $this->fieldMapping[RatingDataMapper::REVIEW_COUNT] :
                        RatingDataMapper::REVIEW_COUNT
                )
            );
        }
        if (!isset($rating[RatingDataMapper::RATING_PRODUCT_ID]) ||
            empty($rating[RatingDataMapper::RATING_PRODUCT_ID])
        ) {
            throw new InvalidRatingDataException(
                __(
                    'Rating data missing %1',
                    isset($this->fieldMapping[RatingDataMapper::RATING_PRODUCT_ID]) ?
                        $this->fieldMapping[RatingDataMapper::RATING_PRODUCT_ID] :
                        RatingDataMapper::RATING_PRODUCT_ID
                )
            );
        }
        if (!isset($rating[RatingDataMapper::RATING_STORE])) {
            throw new InvalidRatingDataException(
                __(
                    'Rating data missing %1',
                    isset($this->fieldMapping[RatingDataMapper::RATING_STORE]) ?
                        $this->fieldMapping[RatingDataMapper::RATING_STORE] :
                        RatingDataMapper::RATING_STORE
                )
            );
        }
        if (isset($rating[RatingDataMapper::RATING_AVERAGE])) {
            return;
        }
        if (!array_key_exists(RatingDataMapper::RATING_SUM, $rating) ||
            !array_key_exists(RatingDataMapper::RATING_COUNT, $rating)
        ) {
            throw new InvalidRatingDataException(
                __(
                    'Rating data missing. Either %1 or (%2 and %3) are required',
                    isset($this->fieldMapping[RatingDataMapper::RATING_AVERAGE]) ?
                        $this->fieldMapping[RatingDataMapper::RATING_AVERAGE] :
                        RatingDataMapper::RATING_AVERAGE,
                    isset($this->fieldMapping[RatingDataMapper::RATING_SUM]) ?
                        $this->fieldMapping[RatingDataMapper::RATING_SUM] :
                        RatingDataMapper::RATING_SUM,
                    isset($this->fieldMapping[RatingDataMapper::RATING_COUNT]) ?
                        $this->fieldMapping[RatingDataMapper::RATING_COUNT] :
                        RatingDataMapper::RATING_COUNT
                )
            );
        }
    }
}
