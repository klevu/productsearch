<?php

namespace Klevu\Search\Plugin\Review\Model\ResourceModel\Rating\Option;

use Klevu\Search\Api\Service\Catalog\Product\Review\UpdateRatingInterface;
use Klevu\Search\Model\Product\MagentoProductActionsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Review\Model\ResourceModel\Rating\Option as RatingOptionResource;
use Psr\Log\LoggerInterface;

class UpdateRatingAttributesOnAggregatePlugin
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var MagentoProductActionsInterface
     */
    private $magentoProductActions;

    /**
     * @var UpdateRatingInterface
     */
    private $updateRating;

    /**
     * @param LoggerInterface $logger
     * @param ProductRepositoryInterface $productRepository
     * @param MagentoProductActionsInterface $magentoProductActions
     * @param UpdateRatingInterface $updateRating
     */
    public function __construct(
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        MagentoProductActionsInterface $magentoProductActions,
        UpdateRatingInterface $updateRating
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->magentoProductActions = $magentoProductActions;
        $this->updateRating = $updateRating;
    }

    /**
     * @param RatingOptionResource $subject
     * @param mixed $result
     * @param int $ratingId
     * @param int $entityPkValue
     * @return null
     */
    public function afterAggregateEntityByRatingId(
        RatingOptionResource $subject,
        $result,
        $ratingId,
        $entityPkValue
    ) {
        try {
            $product = $this->productRepository->getById($entityPkValue);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(
                'Error in regenerating product rating counts: product not found',
                [
                    'method' => __METHOD__,
                    'product_id' => $entityPkValue,
                ]
            );

            return $result;
        }

        try {
            $this->updateRating->execute($product);
            $this->magentoProductActions->updateSpecificProductIds([(int)$product->getId()]);
        } catch (\Exception $e) {
            $this->logger->error(
                'Error in regenerating product rating counts: ' . $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'method' => __METHOD__,
                    'product_id' => $product->getId(),
                    'sku' => $product->getSku(),
                ]
            );
        }

        return $result;
    }
}
