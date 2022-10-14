<?php

namespace Klevu\Search\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\IsRatingAttributeAvailableInterface;
use Klevu\Search\Model\Attribute\Rating;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class IsRatingAttributeAvailable implements IsRatingAttributeAvailableInterface
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var bool
     */
    private $cachedResult;

    /**
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(AttributeRepositoryInterface $attributeRepository)
    {
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @returns bool
     */
    public function execute()
    {
        if (null === $this->cachedResult) {
            $this->cachedResult = true;
            try {
                $this->attributeRepository->get(
                    Product::ENTITY,
                    Rating::ATTRIBUTE_CODE
                );
            } catch (NoSuchEntityException $exception) {
                $this->cachedResult = false;
            }
        }

        return $this->cachedResult;
    }
}
