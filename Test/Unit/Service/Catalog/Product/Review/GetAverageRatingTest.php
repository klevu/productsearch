<?php

namespace Klevu\Search\Test\Unit\Service\Catalog\Product;

use Klevu\Search\Model\Attribute\Rating as RatingAttribute;
use Klevu\Search\Model\Attribute\ReviewCount as ReviewCountAttribute;
use Klevu\Search\Service\Catalog\Product\Review\GetAverageRating;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GetAverageRatingTest extends TestCase
{
    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    public function testSimpleProductWithNoRatingAttributeReturnsNull()
    {
        $this->setupPhp5();

        $product = $this->getProduct();
        $product->method('getId')->willReturn(1);
        $product->method('getData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 1;
                    case RatingAttribute::ATTRIBUTE_CODE:
                    default:
                        return null;
                }
            });
        $product->method('hasData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    case RatingAttribute::ATTRIBUTE_CODE:
                    default:
                        return false;
                }
            });

        $ratingService = $this->instantiateGetAverageRatingsService();
        $rating = $ratingService->execute([$product]);

        if (method_exists($this, 'assertNull')) {
            $this->assertNull($rating);
        } else {
            $this->assertSame(null, $rating, 'Is NULL');
        }
    }

    public function testSimpleProductWithRatingOfZeroAttributeReturnsZero()
    {
        $this->setupPhp5();

        $product = $this->getProduct();
        $product->method('getId')->willReturn(1);
        $product->method('getData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return 0;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 1;
                    default:
                        return null;
                }
            });
        $product->method('hasData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    default:
                        return false;
                }
            });

        $ratingService = $this->instantiateGetAverageRatingsService();
        $rating = $ratingService->execute([$product]);

        if (method_exists($this, 'assertIsFloat')) {
            $this->assertIsFloat($rating);
        } else {
            $this->assertTrue(is_float($rating), 'Is Float');
        }
        $this->assertSame(0.00, $rating);
    }

    public function testSimpleProductWithOneRatingAttributeReturnsFloat()
    {
        $this->setupPhp5();

        $product = $this->getProduct();
        $product->method('getId')->willReturn(1);
        $product->method('getData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return 50;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 1;
                    default:
                        return null;
                }
            });
        $product->method('hasData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    default:
                        return false;
                }
            });
        $ratingService = $this->instantiateGetAverageRatingsService();
        $rating = $ratingService->execute([$product]);

        if (method_exists($this, 'assertIsFloat')) {
            $this->assertIsFloat($rating);
        } else {
            $this->assertTrue(is_float($rating), 'Is Float');
        }
        $this->assertSame(50.00, $rating);
    }

    public function testSimpleProductWithConfigurableParentReturnsAveRating()
    {
        $this->setupPhp5();

        $product = $this->getProduct();
        $product->method('getId')->willReturn(1);
        $product->method('getData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return 40;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 3;
                    default:
                        return null;
                }
            });
        $product->method('hasData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    default:
                        return false;
                }
            });

        $parentProduct = $this->getProduct();
        $parentProduct->method('getId')->willReturn(2);
        $parentProduct->method('getData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return 80;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 1;
                    default:
                        return null;
                }
            });
        $parentProduct->method('hasData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    default:
                        return false;
                }
            });

        $ratingService = $this->instantiateGetAverageRatingsService();
        $rating = $ratingService->execute([$product, $parentProduct]);

        if (method_exists($this, 'assertIsFloat')) {
            $this->assertIsFloat($rating);
        } else {
            $this->assertTrue(is_float($rating), 'Is Float');
        }
        $this->assertSame(50.0, $rating);
    }

    public function testDuplicateProductRatingsAreNotCountedTwice()
    {
        $this->setupPhp5();

        $product = $this->getProduct();
        $product->method('getId')->willReturn(1);
        $product->method('getData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return 50;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 1;
                    default:
                        return null;
                }
            });
        $product->method('hasData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    default:
                        return false;
                }
            });

        $parentProduct = $this->getProduct();
        $parentProduct->method('getId')->willReturn(2);
        $parentProduct->method('getData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return 75;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 1;
                    default:
                        return null;
                }
            });
        $parentProduct->method('hasData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    default:
                        return false;
                }
            });

        $ratingService = $this->instantiateGetAverageRatingsService();
        $rating = $ratingService->execute([$product, $parentProduct, $product, $product]);

        if (method_exists($this, 'assertIsFloat')) {
            $this->assertIsFloat($rating);
        } else {
            $this->assertTrue(is_float($rating), 'Is Float');
        }
        $this->assertSame(62.5, $rating);
    }

    public function testSimpleProductWithConfigurableParentWithoutRatingReturnsSimpleRating()
    {
        $this->setupPhp5();

        $product = $this->getProduct();
        $product->method('getId')->willReturn(1);
        $product->method('getData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return 50;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 1;
                    default:
                        return null;
                }
            });
        $product->method('hasData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    default:
                        return false;
                }
            });

        $parentProduct = $this->getProduct();
        $parentProduct->method('getId')->willReturn(2);
        $parentProduct->method('getData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return null;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 1;
                    default:
                        return null;
                }
            });
        $parentProduct->method('hasData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    case RatingAttribute::ATTRIBUTE_CODE:
                    default:
                        return false;
                }
            });

        $ratingService = $this->instantiateGetAverageRatingsService();
        $rating = $ratingService->execute([$product, $parentProduct]);

        if (method_exists($this, 'assertIsFloat')) {
            $this->assertIsFloat($rating);
        } else {
            $this->assertTrue(is_float($rating), 'Is Float');
        }
        $this->assertSame(50.00, $rating);
    }

    public function testSimpleProductWithoutRatingAndWithConfigurableParentReturnsConfigurableRating()
    {
        $this->setupPhp5();

        $product = $this->getProduct();
        $product->method('getId')->willReturn(1);
        $product->method('getData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 1;
                    case RatingAttribute::ATTRIBUTE_CODE:
                    default:
                        return null;
                }
            });
        $product->method('hasData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    case RatingAttribute::ATTRIBUTE_CODE:
                    default:
                        return false;
                }
            });

        $parentProduct = $this->getProduct();
        $parentProduct->method('getId')->willReturn(2);
        $parentProduct->method('getData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return 75;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 1;
                    default:
                        return null;
                }
            });
        $parentProduct->method('hasData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    default:
                        return false;
                }
            });

        $ratingService = $this->instantiateGetAverageRatingsService();
        $rating = $ratingService->execute([$product, $parentProduct]);

        if (method_exists($this, 'assertIsFloat')) {
            $this->assertIsFloat($rating);
        } else {
            $this->assertTrue(is_float($rating), 'Is Float');
        }
        $this->assertSame(75.00, $rating);
    }

    /**
     * @dataProvider noneNumericRatingsAttributeDataProvider
     */
    public function testHandlesIncorrectTypeDataForRatingAttributes($ratingData)
    {
        $this->setupPhp5();

        $product = $this->getProduct();
        $product->method('getId')->willReturn(1);
        $product->method('getData')
            ->willReturnCallback(static function ($attribute) use ($ratingData) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return $ratingData;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 1;
                    default:
                        return null;
                }
            });
        $product->method('hasData')
            ->willReturnCallback(static function ($attribute) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    default:
                        return false;
                }
            });

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'Product Rating data is not numeric for product ID %s',
                    $product->getId()
                )
            );

        $ratingService = $this->instantiateGetAverageRatingsService();
        $rating = $ratingService->execute([$product]);

        if (method_exists($this, 'assertNull')) {
            $this->assertNull($rating);
        } else {
            $this->assertSame(null, $rating, 'Is NULL');
        }
    }

    /**
     * @dataProvider noneNumericRatingsAttributeDataProvider
     */
    public function testHandlesIncorrectTypeDataForRatingCountAttributes($ratingData)
    {
        $this->setupPhp5();

        $product = $this->getProduct();
        $productId = 1;

        $product->method('getId')->willReturn(1);
        $product->method('getData')
            ->willReturnCallback(static function ($attribute) use ($ratingData) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return 80;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return $ratingData;
                    default:
                        return null;
                }
            });
        $product->method('hasData')
            ->willReturnCallback(static function ($attribute) use ($ratingData) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return true;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return null !== $ratingData;
                    default:
                        return false;
                }
            });

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'Product Review Count data is not numeric for product ID %s',
                    $productId
                )
            );

        $ratingService = $this->instantiateGetAverageRatingsService();
        $rating = $ratingService->execute([$product]);

        if (method_exists($this, 'assertNull')) {
            $this->assertNull($rating);
        } else {
            $this->assertSame(null, $rating, 'Is NULL');
        }
    }

    /**
     * @dataProvider negativeRatingsAttributeDataProvider
     */
    public function testHandlesNegativeValuesForRatingAttributes($ratingData)
    {
        $this->setupPhp5();

        $product = $this->getProduct();
        $product->method('getId')->willReturn(1);
        $product->method('getData')
            ->willReturnCallback(static function ($attribute) use ($ratingData) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return $ratingData;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return 1;
                    default:
                        return null;
                }
            });
        $product->method('hasData')
            ->willReturnCallback(static function ($attribute) use ($ratingData) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return null !== $ratingData;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return true;
                    default:
                        return false;
                }
            });

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'Product Rating value (%s) is less than 0 for product ID %s',
                    $ratingData,
                    $product->getId()
                )
            );

        $ratingService = $this->instantiateGetAverageRatingsService();
        $rating = $ratingService->execute([$product]);

        if (method_exists($this, 'assertNull')) {
            $this->assertNull($rating);
        } else {
            $this->assertSame(null, $rating, 'Is NULL');
        }
    }

    /**
     * @dataProvider negativeRatingsAttributeDataProvider
     */
    public function testHandlesNegativeValuesForRatingCountAttributes($ratingData)
    {
        $this->setupPhp5();

        $product = $this->getProduct();
        $product->method('getId')->willReturn(1);
        $product->method('getData')
            ->willReturnCallback(static function ($attribute) use ($ratingData) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return 80;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return $ratingData;
                    default:
                        return null;
                }
            });
        $product->method('hasData')
            ->willReturnCallback(static function ($attribute) use ($ratingData) {
                switch ($attribute) {
                    case RatingAttribute::ATTRIBUTE_CODE:
                        return true;
                    case ReviewCountAttribute::ATTRIBUTE_CODE:
                        return null !== $ratingData;
                    default:
                        return false;
                }
            });

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                sprintf(
                    'Product Review Count value (%s) is less than 0 for product ID %s',
                    $ratingData,
                    $product->getId()
                )
            );

        $ratingService = $this->instantiateGetAverageRatingsService();
        $rating = $ratingService->execute([$product]);

        if (method_exists($this, 'assertNull')) {
            $this->assertNull($rating);
        } else {
            $this->assertSame(null, $rating, 'Is NULL');
        }
    }

    /**
     * @return array
     */
    public function noneNumericRatingsAttributeDataProvider()
    {
        return [
            ['string'],
            [['string', '123']],
            [[0, 84763]],
            [json_decode(json_encode(['a' => 'b']))],
            [false],
            [true],
            ['123string'],
            ['123.34 string'],
            ['123,45'],
        ];
    }

    /**
     * @return array
     */
    public function negativeRatingsAttributeDataProvider()
    {
        return [
            [-10],
            [-10.00],
            ['-10'],
            ['-10.00'],
        ];
    }

    /**
     * @return ProductInterface|Product|MockObject
     */
    private function getProduct()
    {
        return $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return GetAverageRating
     */
    private function instantiateGetAverageRatingsService()
    {
        return new GetAverageRating($this->logger);
    }

    /**
     * @return void
     */
    private function setupPhp5()
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
