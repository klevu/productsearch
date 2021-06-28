<?php

namespace Klevu\Search\Test\Unit\Block\Catalog\Product;

use Klevu\Search\Block\Catalog\Product\Tracking;
use Magento\Catalog\Block\Product\Context as ProductBlockContext;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TrackingTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Tests existence and content of JsonTrackingData return given expected values
     * Happy path
     */
    public function testGetJsonTrackingDataValid()
    {
        $this->setupPhp5();

        $productFixture = [
            'id' => '1',
            'name' => 'Test Product',
            'productUrl' => 'https://test.klevu.com/foo',
        ];
        $productMock = $this->getProductMock($productFixture['id'], $productFixture['name'], $productFixture['productUrl']);

        /** @var Tracking $trackingBlock */
        $trackingBlock = $this->objectManager->getObject(Tracking::class, [
            'context' => $this->getContextMock(
                $this->getCoreRegistryMock([['product', $productMock]])
            ),
        ]);

        $actualResult = $trackingBlock->getJsonTrackingData();
        $actualResultUnserialized = json_decode($actualResult, true);

        /// @todo Remove conditional is_array test when support for Magento < 2.2.0 is dropped
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($actualResultUnserialized);
        } else {
            $this->assertTrue(is_array($actualResultUnserialized));
        }

        $this->assertArrayHasKey('klevu_apiKey', $actualResultUnserialized, 'Result has key: klevu_apiKey');
        $this->assertArrayHasKey('klevu_type', $actualResultUnserialized, 'Result has key: klevu_type');
        // Note, uppercase K intentional
        $this->assertArrayHasKey('Klevu_typeOfRecord', $actualResultUnserialized, 'Result has key: klevu_typeOfRecord');
        $this->assertSame('KLEVU_PRODUCT', $actualResultUnserialized['Klevu_typeOfRecord'], 'klevu_typeOfRecord equal to KLEVU_PRODUCT');
        $this->assertArrayHasKey('klevu_productGroupId', $actualResultUnserialized, 'Result has key: klevu_productGroupId');
        $this->assertArrayHasKey('klevu_productVariantId', $actualResultUnserialized, 'Result has key: klevu_productVariantId');

        $this->assertArrayHasKey('klevu_productId', $actualResultUnserialized, 'Result has key: klevu_apiKey');
        $this->assertSame($productFixture['id'], $actualResultUnserialized['klevu_productId'], 'klevu_productId equal to fixture');
        $this->assertArrayHasKey('klevu_productName', $actualResultUnserialized, 'Result has key: klevu_apiKey');
        $this->assertSame($productFixture['name'], $actualResultUnserialized['klevu_productName'], 'klevu_productName equal to fixture');
        $this->assertArrayHasKey('klevu_productUrl', $actualResultUnserialized, 'Result has key: klevu_apiKey');
        $this->assertSame($productFixture['productUrl'], $actualResultUnserialized['klevu_productUrl'], 'klevu_productUrl equal to fixture');
    }

    /**
     * @param $registryMock
     * @return ProductBlockContext|MockObject
     */
    private function getContextMock($registryMock)
    {
        if (!method_exists($this, 'createMock')) {
            return $this->getContextMockLegacy($registryMock);
        }

        $contextMock = $this->createMock(ProductBlockContext::class);

        $contextMock->method('getRegistry')->willReturn($registryMock);

        return $contextMock;
    }

    /**
     * @param $registryMock
     * @return ProductBlockContext|MockObject
     */
    private function getContextMockLegacy($registryMock)
    {
        $contextMock = $this->getMockBuilder(ProductBlockContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock->expects($this->any())
            ->method('getRegistry')
            ->willReturn($registryMock);

        return $contextMock;
    }

    /**
     * @param array $returnMap
     * @return Registry|MockObject|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getCoreRegistryMock(array $returnMap)
    {
        if (!method_exists($this, 'createMock')) {
            return $this->getCoreRegistryMockLegacy($returnMap);
        }

        $coreRegistryMock = $this->createMock(Registry::class);

        $coreRegistryMock->method('registry')->willReturnMap($returnMap);

        return $coreRegistryMock;
    }

    /**
     * @param array $returnMap
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getCoreRegistryMockLegacy(array $returnMap)
    {
        $coreRegistryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreRegistryMock->expects($this->any())
            ->method('registry')
            ->willReturnMap($returnMap);

        return $coreRegistryMock;
    }

    /**
     * @param int|string|null $id
     * @param string|null $name
     * @param string|null $productUrl
     * @param string $mockClass
     * @return MockObject|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getProductMock(
        $id,
        $name,
        $productUrl,
        $mockClass = Product::class
    ) {
        if (!method_exists($this, 'createMock')) {
            return $this->getProductMockLegacy($id, $name, $productUrl, $mockClass);
        }

        $productMock = $this->createMock($mockClass);

        $productMock->method('getId')->willReturn($id);
        $productMock->method('getName')->willReturn($name);
        $productMock->method('getProductUrl')->willReturn($productUrl);

        return $productMock;
    }

    /**
     * @param int|string|null $id
     * @param string|null $name
     * @param string|null $productUrl
     * @param string $mockClass
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getProductMockLegacy(
        $id,
        $name,
        $productUrl,
        $mockClass = Product::class
    ) {
        $productMock = $this->getMockBuilder($mockClass)
            ->disableOriginalConstructor()
            ->getMock();

        $productMock->expects($this->any())
            ->method('getID')
            ->willReturn($id);
        $productMock->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $productMock->expects($this->any())
            ->method('getProductUrl')
            ->willReturn($productUrl);

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
