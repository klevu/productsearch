<?php

namespace Klevu\Search\Test\Unit\Model\Product\Provider;

use Klevu\Search\Api\Provider\CommonProviderInterface;
use Klevu\Search\Model\Product\Provider\SimpleProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SimpleProviderTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var \array[][][]
     */
    private $mockProviderFixtures = [
        'grouped' => [
            'childIds' => [
                'simple-1' => [],
                'simple-2' => [],
                'grouped-1' => [999],
                'grouped-2' => [10, 20, 30],
                'bundle-1' => [],
                'bundle-2' => [],
            ],
            'parentIds' => [
                'simple-1' => [101, 102, 103, 104],
                'simple-2' => [201, 202, 203],
                'grouped-1' => [999],
                'grouped-2' => [],
                'bundle-1' => [],
                'bundle-2' => [],
            ],
        ],
        'bundle' => [
            'childIds' => [
                'simple-1' => [],
                'simple-2' => [],
                'grouped-1' => [],
                'grouped-2' => [],
                'bundle-1' => [301, 302],
                'bundle-2' => [311, 312, 313],
            ],
            'parentIds' => [
                'simple-1' => [104, 105, 106, 101],
                'simple-2' => [],
                'grouped-1' => [],
                'grouped-2' => [],
                'bundle-1' => [],
                'bundle-2' => [],
            ],
        ],
    ];

    /**
     * Tests any internal caching
     * Additionally tests that simple products never return children
     */
    public function testGetChildIds_SingleProvider_MultipleProducts_Simple()
    {
        $this->setupPhp5();

        /** @var SimpleProvider $simpleProvider */
        $simpleProvider = $this->objectManager->getObject(SimpleProvider::class, [
            'providers' => [
                'grouped' => $this->getGroupedProviderMock(),
            ],
        ]);

        $expectedResult = [];
        $actualResult = $simpleProvider->getChildIds($this->getProductMock('simple-1', 'simple'));
        $this->assertSame($expectedResult, $actualResult, 'First product request');

        $expectedResult = [];
        $actualResult = $simpleProvider->getChildIds($this->getProductMock('simple-2', 'simple'));
        $this->assertSame($expectedResult, $actualResult, 'Second product request');
    }

    /**
     * Tests merging of results from multiple providers
     * Additionally tests that simple products never return children
     */
    public function testGetChildIds_MultipleProviders_SingleProduct_Simple()
    {
        $this->setupPhp5();

        /** @var SimpleProvider $simpleProvider */
        $simpleProvider = $this->objectManager->getObject(SimpleProvider::class, [
            'providers' => [
                'grouped' => $this->getGroupedProviderMock(),
                'bundle' => $this->getBundleProviderMock(),
            ],
        ]);

        $expectedResult = [];
        $actualResult = $simpleProvider->getChildIds($this->getProductMock('simple-1', 'simple'));
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * Tests unexpected product type
     */
    public function testGetChildIds_SingleProvider_SingleProduct_Grouped()
    {
        $this->setupPhp5();

        /** @var SimpleProvider $simpleProvider */
        $simpleProvider = $this->objectManager->getObject(SimpleProvider::class, [
            'providers' => [
                'grouped' => $this->getGroupedProviderMock(),
            ],
        ]);

        // Even though grouped-1 has both children and parents, this class should never return any
        //  children as it expects a simple product; and simples do not have children
        $expectedResult = [];
        if (method_exists($this, 'expectException')) {
            $this->expectException(\InvalidArgumentException::class);
        } else {
            $this->setExpectedException(\InvalidArgumentException::class);
        }
        if (method_exists($this, 'expectExceptionMessage')) {
            $this->expectExceptionMessage('Incorrect ProductTypeId grouped provided while fetching childIds');
        }
        $actualResult = $simpleProvider->getChildIds($this->getProductMock('grouped-1', 'grouped'));
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * Tests any internal caching
     */
    public function testGetParentIds_SingleProvider_MultipleProducts_Simple()
    {
        $this->setupPhp5();

        /** @var SimpleProvider $simpleProvider */
        $simpleProvider = $this->objectManager->getObject(SimpleProvider::class, [
            'providers' => [
                'grouped' => $this->getGroupedProviderMock(),
            ],
        ]);

        // see fixtures
        // * grouped.parentIds.simple-1
        //      ie the grouped provider returning ids for grouped products which contain simple-1 as a child
        $expectedResult = [101, 102, 103, 104];
        $actualResult = $simpleProvider->getParentIds($this->getProductMock('simple-1', 'simple'));
        $this->assertSame($expectedResult, $actualResult, 'First product request');

        // see fixtures
        // * grouped.parentIds.simple-2
        //      ie the grouped provider returning ids for grouped products which contain simple-2 as a child
        $expectedResult = [201, 202, 203];
        $actualResult = $simpleProvider->getParentIds($this->getProductMock('simple-2', 'simple'));
        $this->assertSame($expectedResult, $actualResult, 'Second product request');
    }

    /**
     * Tests merging of results from multiple providers
     */
    public function testGetParentIds_MultipleProviders_SingleProduct_Simple()
    {
        $this->setupPhp5();

        /** @var SimpleProvider $simpleProvider */
        $simpleProvider = $this->objectManager->getObject(SimpleProvider::class, [
            'providers' => [
                'grouped' => $this->getGroupedProviderMock(),
                'bundle' => $this->getBundleProviderMock(),
            ],
        ]);

        // see a merge of fixtures
        // * grouped.parentIds.simple-1
        //      ie the grouped provider returning ids for grouped products which contain simple-1 as a child
        // * bundle.parentIds.simple-1
        $expectedResult = [101, 102, 103, 104, 105, 106];
        $actualResult = $simpleProvider->getParentIds($this->getProductMock('simple-1', 'simple'));
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * Tests unexpected product type
     */
    public function testGetParentIds_SingleProvider_SingleProduct_Grouped()
    {

        $this->setupPhp5();

        /** @var SimpleProvider $simpleProvider */
        $simpleProvider = $this->objectManager->getObject(SimpleProvider::class, [
            'providers' => [
                'grouped' => $this->getGroupedProviderMock(),
            ],
        ]);

        // Even though grouped-1 has both children and parents, this class should never return any
        //  parents as it expects a simple product; and this is not a simple product
        $expectedResult = [];
        if (method_exists($this, 'expectException')) {
            $this->expectException(\InvalidArgumentException::class);
        } else {
            $this->setExpectedException(\InvalidArgumentException::class);
        }
        if (method_exists($this, 'expectExceptionMessage')) {
            $this->expectExceptionMessage('Incorrect ProductTypeId grouped provided while fetching parentIds');
        }
        $actualResult = $simpleProvider->getParentIds($this->getProductMock('grouped-1', 'grouped'));
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @param array $childIdsReturnValues
     * @param array $parentIdsReturnValues
     * @return CommonProviderInterface|MockObject
     */
    private function getProviderMock(array $childIdsReturnValues, array $parentIdsReturnValues)
    {
        if (!method_exists($this, 'createMock')) {
            return $this->getProviderMockLegacy($childIdsReturnValues, $parentIdsReturnValues);
        }

        $providerMock = $this->createMock(CommonProviderInterface::class);

        $providerMock->method('getChildIds')->willReturnCallback(
            static function (ProductInterface $product) use ($childIdsReturnValues) {
                return isset($childIdsReturnValues[$product->getSku()])
                    ? $childIdsReturnValues[$product->getSku()]
                    : [];
            }
        );
        $providerMock->method('getParentIds')->willReturnCallback(
            static function (ProductInterface $product) use ($parentIdsReturnValues) {
                return isset($parentIdsReturnValues[$product->getSku()])
                    ? $parentIdsReturnValues[$product->getSku()]
                    : [];
            }
        );

        return $providerMock;
    }

    /**
     * @param array $childIdsReturnValues
     * @param array $parentIdsReturnValues
     * @return CommonProviderInterface|MockObject
     */
    private function getProviderMockLegacy(array $childIdsReturnValues, array $parentIdsReturnValues)
    {
        $providerMock = $this->getMockBuilder(CommonProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $providerMock->expects($this->any())
            ->method('getChildIds')
            ->willReturnCallback(
                static function (ProductInterface $product) use ($childIdsReturnValues) {
                    return isset($childIdsReturnValues[$product->getSku()])
                        ? $childIdsReturnValues[$product->getSku()]
                        : [];
                }
            );
        $providerMock->expects($this->any())
            ->method('getParentIds')
            ->willReturnCallback(
                static function (ProductInterface $product) use ($parentIdsReturnValues) {
                    return isset($parentIdsReturnValues[$product->getSku()])
                        ? $parentIdsReturnValues[$product->getSku()]
                        : [];
                }
            );

        return $providerMock;
    }

    /**
     * @return CommonProviderInterface|MockObject
     */
    private function getGroupedProviderMock()
    {
        return $this->getProviderMock(
            $this->mockProviderFixtures['grouped']['childIds'],
            $this->mockProviderFixtures['grouped']['parentIds']
        );
    }

    /**
     * @return CommonProviderInterface|MockObject
     */
    private function getBundleProviderMock()
    {
        return $this->getProviderMock(
            $this->mockProviderFixtures['bundle']['childIds'],
            $this->mockProviderFixtures['bundle']['parentIds']
        );
    }

    /**
     * @param string $sku
     * @param string $typeId
     * @return ProductInterface|MockObject
     */
    private function getProductMock($sku, $typeId)
    {
        if (!method_exists($this, 'createMock')) {
            return $this->getProductMockLegacy($sku, $typeId);
        }

        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getSku')->willReturn($sku);
        $productMock->method('getTypeId')->willReturn($typeId);

        return $productMock;
    }

    /**
     * @param string $sku
     * @param string $typeId
     * @return ProductInterface|MockObject
     */
    private function getProductMockLegacy($sku, $typeId)
    {
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock->expects($this->any())
            ->method('getSku')
            ->willReturn($sku);
        $productMock->expects($this->any())
            ->method('getTypeId')
            ->willReturn($typeId);

        return $productMock;
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = new ObjectManager($this);
    }
}
