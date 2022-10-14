<?php

namespace Klevu\Search\Provider\Catalog\Product\Review;

use InvalidArgumentException;
use Klevu\Search\Api\Provider\Catalog\Product\Review\ReviewCountDataProviderInterface;
use Magento\Review\Model\ReviewFactory;
use Psr\Log\LoggerInterface;

class MagentoReviewCountDataProvider implements ReviewCountDataProviderInterface
{
    const REVIEWS_APPROVED_ONLY = true;

    /**
     * @var ReviewFactory
     */
    private $reviewFactory;
    /**
     * @var bool
     */
    private $approvedOnly;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ReviewFactory $reviewFactory
     * @param LoggerInterface $logger
     * @param bool $approvedOnly
     */
    public function __construct(
        ReviewFactory $reviewFactory,
        LoggerInterface $logger,
        $approvedOnly = null
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->logger = $logger;
        $this->approvedOnly = (null !== $approvedOnly) ? $approvedOnly : self::REVIEWS_APPROVED_ONLY;
    }

    /**
     * @param int $productId
     * @param int|null $storeId
     *
     * @return int|null
     */
    public function getData($productId, $storeId = null)
    {
        try {
            $this->validateProductId($productId);
        } catch (InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage());

            return null;
        }
        $review = $this->reviewFactory->create();

        return (int)$review->getTotalReviews(
            (int)$productId,
            (bool)$this->approvedOnly,
            (int)$storeId
        );
    }

    /**
     * @param int $productId
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateProductId($productId)
    {
        if (!is_numeric($productId)) {
            throw new InvalidArgumentException(
                __('Provided ProductId for %1 is not numeric', __CLASS__)
            );
        }
        if ((int)$productId <= 0) {
            throw new InvalidArgumentException(
                __('Provided ProductId for %1 is not valid', __CLASS__)
            );
        }
    }
}
