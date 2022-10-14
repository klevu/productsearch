<?php

namespace Klevu\Search\Service\Catalog\Product\Review;

use InvalidArgumentException;
use Klevu\Search\Api\Provider\Catalog\Product\Review\AllRatingsDataProviderInterface;
use Klevu\Search\Api\Service\Catalog\Product\IsRatingAttributeAvailableInterface;
use Klevu\Search\Api\Service\Catalog\Product\IsRatingCountAttributeAvailableInterface;
use Klevu\Search\Api\Service\Catalog\Product\Review\UpdateAllRatingsInterface;
use Klevu\Search\Api\Service\Catalog\Product\UpdateRatingsAttributesInterface;
use Klevu\Search\Exception\Catalog\Product\Review\KlevuProductAttributeMissingException;
use Klevu\Search\Model\Attribute\Rating;
use Klevu\Search\Model\Attribute\ReviewCount;

class UpdateAllRatings implements UpdateAllRatingsInterface
{
    /**
     * @var IsRatingAttributeAvailableInterface
     */
    private $isRatingAttributeAvailable;
    /**
     * @var IsRatingCountAttributeAvailableInterface
     */
    private $isRatingCountAttributeAvailable;
    /**
     * @var AllRatingsDataProviderInterface
     */
    private $allRatingsDataProvider;
    /**
     * @var UpdateRatingsAttributesInterface
     */
    private $updateRatingsAttributes;

    /**
     * @param IsRatingAttributeAvailableInterface $isRatingAttributeAvailable
     * @param IsRatingCountAttributeAvailableInterface $isRatingCountAttributeAvailable
     * @param AllRatingsDataProviderInterface $allRatingsDataProvider
     * @param UpdateRatingsAttributesInterface $updateRatingsAttributes
     */
    public function __construct(
        IsRatingAttributeAvailableInterface $isRatingAttributeAvailable,
        IsRatingCountAttributeAvailableInterface $isRatingCountAttributeAvailable,
        AllRatingsDataProviderInterface $allRatingsDataProvider,
        UpdateRatingsAttributesInterface $updateRatingsAttributes
    ) {
        $this->isRatingAttributeAvailable = $isRatingAttributeAvailable;
        $this->isRatingCountAttributeAvailable = $isRatingCountAttributeAvailable;
        $this->allRatingsDataProvider = $allRatingsDataProvider;
        $this->updateRatingsAttributes = $updateRatingsAttributes;
    }

    /**
     * @param int $storeId
     *
     * @return void
     * @throws KlevuProductAttributeMissingException
     * @throws InvalidArgumentException
     */
    public function execute($storeId)
    {
        $this->validateStore($storeId);
        $this->validateRatingAttributes();

        $storeRatings = $this->allRatingsDataProvider->getData((int)$storeId);
        if (!$storeRatings) {
            return;
        }
        $this->updateRatingsAttributes->execute($storeRatings);
    }

    /**
     * @param int $storeId
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateStore($storeId)
    {
        if (!is_int($storeId) && (!is_string($storeId) || !ctype_digit($storeId))) {
            throw new InvalidArgumentException(__('Store param must be an integer'));
        }

        if ((int)$storeId < 0) {
            throw new InvalidArgumentException(__('Store param must be a non-negative integer'));
        }
    }

    /**
     * @return void
     * @throws KlevuProductAttributeMissingException
     */
    private function validateRatingAttributes()
    {
        if (!$this->isRatingAttributeAvailable->execute()) {
            throw new KlevuProductAttributeMissingException(
                __('Klevu product attribute %1 does not exist', Rating::ATTRIBUTE_CODE)
            );
        }
        if (!$this->isRatingCountAttributeAvailable->execute()) {
            throw new KlevuProductAttributeMissingException(
                __('Klevu product attribute %1 does not exist', ReviewCount::ATTRIBUTE_CODE)
            );
        }
    }
}
