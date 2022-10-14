<?php

namespace Klevu\Search\Test\Integration\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\IsRatingCountAttributeAvailableInterface;
use Klevu\Search\Model\Attribute\ReviewCount;
use Klevu\Search\Service\Catalog\Product\IsRatingCountAttributeAvailable;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class IsReviewCountAttributeAvailableTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var mixed
     */
    private $attributeRepository;

    public function testImplementsInterface()
    {
        $this->setupPhp5();

        $isRatingAttributeAvailable = $this->instantiateIsRatingAttributeAvailable();

        $this->assertInstanceOf(IsRatingCountAttributeAvailableInterface::class, $isRatingAttributeAvailable);
    }

    public function testReturnsTrueIfAttributeExists()
    {
        $this->setupPhp5();

        $isRatingAttributeAvailable = $this->instantiateIsRatingAttributeAvailable();
        $available = $isRatingAttributeAvailable->execute();

        $this->assertTrue($available);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testReturnsFalseIfAttributeDoesNotExist()
    {
        $this->setupPhp5();

        $attribute = $this->attributeRepository->get(
            Product::ENTITY,
            ReviewCount::ATTRIBUTE_CODE
        );
        $this->attributeRepository->delete($attribute);

        $isRatingAttributeAvailable = $this->instantiateIsRatingAttributeAvailable();
        $available = $isRatingAttributeAvailable->execute();

        $this->assertFalse($available);
    }

    /**
     * @return IsRatingCountAttributeAvailable
     */
    private function instantiateIsRatingAttributeAvailable()
    {
        return $this->objectManager->create(IsRatingCountAttributeAvailable::class);
    }

    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();

        $this->attributeRepository = $this->objectManager->get(AttributeRepositoryInterface::class);
    }
}
