<?php

namespace Klevu\Search\Test\Integration\Model\Product;

use Klevu\Search\Model\Attribute\Rating as RatingAttribute;
use Klevu\Search\Model\Attribute\ReviewCount as ReviewCountAttribute;
use Klevu\Search\Model\Context;
use Klevu\Search\Model\Product\LoadAttribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface as DBAdapterInterface;
use Magento\Framework\DB\Select;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class LoadAttributeTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @magentoConfigFixture default/klevu_search/attributes/other cost,meta_title,rating,rating_count,review_count
     * @magentoConfigFixture default_store klevu_search/attributes/other cost,meta_title,rating,rating_count,review_count
     *
     * @return array[]
     * @throws \ReflectionException
     */
    public function testGetAttributeMapReturnsValidArray()
    {
        $this->setUpPhp5();

        $resourceConnectionFixture = [
            'color',
            'size',
            'rating',
            'rating_count',
            'review_count',
        ];

        /** @var LoadAttribute $loadAttribute */
        $loadAttribute = $this->objectManager->create(LoadAttribute::class, [
            'context' => $this->getContextMock($resourceConnectionFixture),
        ]);

        $reflectionMethod = new \ReflectionMethod(LoadAttribute::class, 'getAttributeMap');
        $reflectionMethod->setAccessible(true);

        $attributeMap = $reflectionMethod->invoke($loadAttribute);

        if (method_exists($this,'assertIsArray')) {
            $this->assertIsArray($attributeMap);
        } else {
            $this->assertTrue(is_array($attributeMap), 'Attribute Map is array');
        }
        $this->assertNotEmpty($attributeMap);

        foreach ($attributeMap as $klevuAttributeCode => $magentoAttributeCodes) {
            if (method_exists($this, 'assertIsArray')) {
                $this->assertIsArray($magentoAttributeCodes, '[' . $klevuAttributeCode . '] : Magento Attribute Codes is array');
            } else {
                $this->assertTrue(is_array($magentoAttributeCodes), '[' . $klevuAttributeCode . '] : Magento Attribute Codes is array');
            }
            $this->assertNotEmpty($magentoAttributeCodes, '[' . $klevuAttributeCode . '] : Magento Attribute Codes is not empty');

            foreach ($magentoAttributeCodes as $i => $magentoAttributeCode) {
                $this->assertTrue(is_string($magentoAttributeCode), '[' . $klevuAttributeCode . '][' . $magentoAttributeCode . '] Attribute code is string');
                $this->assertTrue('' !== trim($magentoAttributeCode), '[' . $klevuAttributeCode . '][' . $magentoAttributeCode . '] Attribute code is not empty');
            }
        }

        return $attributeMap;
    }

    /**
     * @param array[] $attributeMap
     *
     * @depends testGetAttributeMapReturnsValidArray
     */
    public function testGetAttributeMapContainsValidRatingMapping(array $attributeMap)
    {
        $this->assertArrayHasKey('rating', $attributeMap);
        $this->assertCount(1, $attributeMap['rating']);
        $this->assertSame(RatingAttribute::ATTRIBUTE_CODE, current($attributeMap['rating']));
    }

    /**
     * @param array[] $attributeMap
     *
     * @depends testGetAttributeMapReturnsValidArray
     */
    public function testGetAttributeMapContainsValidRatingCountMapping(array $attributeMap)
    {
        $this->assertArrayHasKey('rating_count', $attributeMap);
        $this->assertCount(1, $attributeMap['rating_count']);
        $this->assertSame(ReviewCountAttribute::ATTRIBUTE_CODE, current($attributeMap['rating_count']));
    }

    /**
     * @param array[] $attributeMap
     *
     * @depends testGetAttributeMapReturnsValidArray
     */
    public function testGetAttributeMapExcludesRatingFromOther(array $attributeMap)
    {
        $this->assertArrayHasKey('other', $attributeMap);
        $this->assertEmpty(array_intersect(['rating'], $attributeMap['other']));
    }

    /**
     * @param array[] $attributeMap
     *
     * @depends testGetAttributeMapReturnsValidArray
     */
    public function testGetAttributeMapExcludesRatingFromOtherAttributeToIndex(array $attributeMap)
    {
        $this->assertArrayHasKey('otherAttributeToIndex', $attributeMap);
        $this->assertEmpty(array_intersect(['rating'], $attributeMap['otherAttributeToIndex']));
    }

    /**
     * @param array[] $attributeMap
     *
     * @depends testGetAttributeMapReturnsValidArray
     */
    public function testGetAttributeMapExcludesRatingCountFromOther(array $attributeMap)
    {
        $this->assertArrayHasKey('other', $attributeMap);
        $this->assertEmpty(array_intersect(['rating_count'], $attributeMap['other']));
    }

    /**
     * @param array[] $attributeMap
     *
     * @depends testGetAttributeMapReturnsValidArray
     */
    public function testGetAttributeMapExcludesRatingCountFromOtherAttributeToIndex(array $attributeMap)
    {
        $this->assertArrayHasKey('otherAttributeToIndex', $attributeMap);
        $this->assertEmpty(array_intersect(['rating_count'], $attributeMap['otherAttributeToIndex']));
    }

    /**
     * @param array $fixture
     * @return ResourceConnection|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getResourceConnectionMock(array $fixture)
    {
        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->method('from')->willReturnSelf();
        $selectMock->method('join')->willReturnSelf();
        $selectMock->method('where')->willReturnSelf();
        $selectMock->method('group')->willReturnSelf();

        $connectionMock = $this->getMockBuilder(DBAdapterInterface::class)
            ->getMock();
        $connectionMock->method('select')
            ->willReturn($selectMock);
        $connectionMock->method('fetchCol')
            ->willReturn($fixture);

        $resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceConnectionMock->method('getConnection')
            ->willReturn($connectionMock);

        return $resourceConnectionMock;
    }

    private function getContextMock($resourceConnectionFixture)
    {
        return $this->objectManager->create(Context::class, [
            'resourceConnection' => $this->getResourceConnectionMock($resourceConnectionFixture),
        ]);
    }
}
