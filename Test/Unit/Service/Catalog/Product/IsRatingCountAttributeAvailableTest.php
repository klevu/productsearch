<?php

namespace Klevu\Search\Test\Unit\Service\Catalog\Product;

use Klevu\Search\Model\Attribute\ReviewCount;
use Klevu\Search\Service\Catalog\Product\IsRatingCountAttributeAvailable;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;

class IsRatingCountAttributeAvailableTest extends TestCase
{
    public function testReturnsTrueIfAttributeExists()
    {
        $mockAttribute = $this->getMockBuilder(AttributeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockAttributeRepository = $this->getMockBuilder(AttributeRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAttributeRepository->expects($this->once())
            ->method('get')
            ->with(Product::ENTITY, ReviewCount::ATTRIBUTE_CODE)
            ->willReturn($mockAttribute);

        $isRatingAttributeAvailable = new IsRatingCountAttributeAvailable($mockAttributeRepository);
        $available = $isRatingAttributeAvailable->execute();

        $this->assertTrue($available);
    }

    public function testReturnsFalseIfAttributeDoesNotExist()
    {
        $exception = new NoSuchEntityException(__(
            'The attribute with a "%1" attributeCode doesn\'t exist. Verify the attribute and try again.',
            ReviewCount::ATTRIBUTE_CODE
        ));

        $mockAttributeRepository = $this->getMockBuilder(AttributeRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAttributeRepository->expects($this->once())
            ->method('get')
            ->with(Product::ENTITY, ReviewCount::ATTRIBUTE_CODE)
            ->willThrowException($exception);

        $isRatingAttributeAvailable = new IsRatingCountAttributeAvailable($mockAttributeRepository);
        $available = $isRatingAttributeAvailable->execute();

        $this->assertFalse($available);
    }
}
