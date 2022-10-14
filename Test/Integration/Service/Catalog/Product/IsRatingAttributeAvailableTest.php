<?php

namespace Klevu\Search\Test\Integration\Service\Catalog\Product;

use Klevu\Search\Api\Service\Catalog\Product\IsRatingAttributeAvailableInterface;
use Klevu\Search\Model\Attribute\Rating;
use Klevu\Search\Service\Catalog\Product\IsRatingAttributeAvailable;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class IsRatingAttributeAvailableTest extends TestCase
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

        $this->assertInstanceOf(IsRatingAttributeAvailableInterface::class, $isRatingAttributeAvailable);
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
            Rating::ATTRIBUTE_CODE
        );
        $this->attributeRepository->delete($attribute);

        $isRatingAttributeAvailable = $this->instantiateIsRatingAttributeAvailable();
        $available = $isRatingAttributeAvailable->execute();

        $this->assertFalse($available);
    }

    /**
     * @return IsRatingAttributeAvailable
     */
    private function instantiateIsRatingAttributeAvailable()
    {
        return $this->objectManager->create(IsRatingAttributeAvailable::class);
    }

    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();

        $this->attributeRepository = $this->objectManager->get(AttributeRepositoryInterface::class);
    }
}
