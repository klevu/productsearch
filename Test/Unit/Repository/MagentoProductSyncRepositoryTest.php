<?php

namespace Klevu\Search\Test\Unit\Repository;

use Klevu\Search\Model\Product\ProductIndividualInterface;
use Klevu\Search\Model\Product\ProductParentInterface;
use Klevu\Search\Model\Product\ResourceModel\Product as ProductResourceModel;
use Klevu\Search\Model\Product\ResourceModel\Product\Collection as KlevuProductCollection;
use Klevu\Search\Repository\MagentoProductSyncRepository;
use Klevu\Search\Service\Catalog\Product\JoinParentStatusToSelect;
use Klevu\Search\Service\Catalog\Product\JoinParentVisibilityToSelect;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Adapter\AdapterInterface as DbAdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MagentoProductSyncRepositoryTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testGetMaxProductIdReturnsInt()
    {
        $this->setupPhp5();
        $maxProductId = mt_rand(1, 99999999);

        $constructorClasses = $this->instantiateConstructorClasses();
        $constructorClasses['mockProductCollection']
            ->expects($this->once())
            ->method('getMaxProductId')
            ->willReturn($maxProductId);
        $constructorClasses['mockScopeConfig']->expects($this->never())
            ->method('isSetFlag');

        $productRepository = $this->instantiateMagentoProductSyncRepository($constructorClasses);
        $mockStore = $this->getMockStore();
        $result = $productRepository->getMaxProductId($mockStore);

        if (method_exists($this, 'assertIsInt')) {
            $this->assertIsInt($result);
        } else {
            $this->assertTrue(is_int($result), 'Is Int');
        }
        $this->assertSame($maxProductId, $result);
    }

    public function testGetProductsReturnsCollection()
    {
        $this->setupPhp5();

        $mockMagentoProductCollection = $this->getMockMagentoProductCollection();

        $constructorClasses = $this->instantiateConstructorClasses();
        $constructorClasses['mockProductIndividual']
            ->expects($this->once())
            ->method('getProductIndividualTypeArray')
            ->willReturn(['simple', 'bundle', 'grouped', 'virtual', 'downloadable', 'giftcard']);

        $constructorClasses['mockProductParent']->expects($this->never())
            ->method('getProductParentTypeArray');

        $constructorClasses['mockScopeConfig']->expects($this->once())
            ->method('isSetFlag')
            ->willReturn(true);

        $constructorClasses['mockProductCollection']
            ->expects($this->once())
            ->method('initCollectionByType')
            ->willReturn($mockMagentoProductCollection);

        $productRepository = $this->instantiateMagentoProductSyncRepository($constructorClasses);

        $mockStore = $this->getMockStore();
        $result = $productRepository->getProductIdsCollection($mockStore);

        $this->assertInstanceOf(ProductCollection::class, $result);
    }

    public function testGetChildIdsReturnsArray()
    {
        $this->setupPhp5();
        $mockMagentoProductCollection = $this->getMockMagentoProductCollection();

        $constructorClasses = $this->instantiateConstructorClasses();
        $constructorClasses['mockProductIndividual']
            ->expects($this->once())
            ->method('getProductChildTypeArray')
            ->willReturn(['simple', 'virtual']);

        $constructorClasses['mockProductParent']->expects($this->never())
            ->method('getProductParentTypeArray');

        $constructorClasses['mockProductCollection']
            ->expects($this->once())
            ->method('initCollectionByType')
            ->willReturn($mockMagentoProductCollection);

        $productRepository = $this->instantiateMagentoProductSyncRepository($constructorClasses);

        $mockStore = $this->getMockStore();
        $result = $productRepository->getChildProductIdsCollection($mockStore);

        $this->assertInstanceOf(ProductCollection::class, $result);
    }

    public function testGetParentProductIdsReturnsArray()
    {
        $this->setupPhp5();

        $this->getMockScopeConfig();

        $mockMagentoProductCollection = $this->getMockMagentoProductCollection();

        $constructorClasses = $this->instantiateConstructorClasses();
        $constructorClasses['mockProductIndividual']
            ->expects($this->never())
            ->method('getProductIndividualTypeArray');

        $constructorClasses['mockProductParent']
            ->expects($this->once())
            ->method('getProductParentTypeArray')
            ->willReturn(['configurable']);

        $constructorClasses['mockScopeConfig']->expects($this->once())
            ->method('isSetFlag')
            ->willReturn(true);

        $constructorClasses['mockProductResourceModel']->expects($this->never())
            ->method('getParentProductRelations');
        $constructorClasses['mockProductResourceModel']
            ->method('getBatchDataForCollection')
            ->willReturnOnConsecutiveCalls(
                [
                    0 => 1,
                    1 => 2,
                    2 => 3,
                    3 => 4
                ],
                [
                    0 => 5,
                    1 => 75,
                    2 => 1223,
                    3 => 8262
                ],
                [] // must finish with an empty array stop the while(true) otherwise we get an infinite loop
            );

        $constructorClasses['mockProductCollection']
            ->expects($this->once())
            ->method('initCollectionByType')
            ->willReturn($mockMagentoProductCollection);

        $productRepository = $this->instantiateMagentoProductSyncRepository($constructorClasses);

        $mockStore = $this->getMockStore();
        $result = $productRepository->getParentProductIds($mockStore);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($result);
        } else {
            $this->assertTrue(is_array($result), 'Is Array');
        }

        $this->assertContains(1, $result);
        $this->assertContains(1223, $result);
    }

    /**
     * @return array
     */
    private function instantiateConstructorClasses()
    {
        $mockProductIndividual = $this->getMockProductIndividual();
        $mockProductParent = $this->getMockProductParent();
        $mockScopeConfig = $this->getMockScopeConfig();
        $mockProductResourceModel = $this->getMockProductResourceModel();
        $mockProductCollection = $this->getMockProductCollection();
        $mockOptionProvider = $this->getMockOptionProvider();
        $mockJoinParentVisibilityToSelectService = $this->getMockJoinParentVisibilityToSelectService();
        $mockJoinParentStatusToSelectService = $this->getMockJoinParentStatusToSelectService();

        return [
            'mockProductIndividual' => $mockProductIndividual,
            'mockProductParent' => $mockProductParent,
            'mockScopeConfig' => $mockScopeConfig,
            'mockProductResourceModel' => $mockProductResourceModel,
            'mockProductCollection' => $mockProductCollection,
            'mockOptionProvider' => $mockOptionProvider,
            'mockJoinParentVisibilityToSelectService' => $mockJoinParentVisibilityToSelectService,
            'mockJoinParentStatusToSelectService' => $mockJoinParentStatusToSelectService,
        ];
    }

    /**
     * @param array $classes
     *
     * @return MagentoProductSyncRepository|Object
     */
    private function instantiateMagentoProductSyncRepository(array $classes)
    {
        return $this->objectManager->getObject(MagentoProductSyncRepository::class, [
            'klevuProductIndividual' => $classes['mockProductIndividual'],
            'klevuProductParent' => $classes['mockProductParent'],
            'scopeConfig' => $classes['mockScopeConfig'],
            'productResourceModel' => $classes['mockProductResourceModel'],
            'productCollection' => $classes['mockProductCollection'],
            'optionProvider' => $classes['mockOptionProvider'],
            'joinParentVisibilityToSelectService' => $classes['mockJoinParentVisibilityToSelectService'],
            'joinParentStatusToSelectService' => $classes['mockJoinParentStatusToSelectService'],
        ]);
    }

    /**
     * @return StoreInterface|MockObject
     */
    private function getMockStore()
    {
        return $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockScopeConfig()
    {
        return $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ProductIndividualInterface|MockObject
     */
    private function getMockProductIndividual()
    {
        return $this->getMockBuilder(ProductIndividualInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ProductIndividualInterface|MockObject
     */
    private function getMockProductParent()
    {
        return $this->getMockBuilder(ProductParentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ProductResourceModel|MockObject
     */
    private function getMockProductResourceModel()
    {
        return $this->getMockBuilder(ProductResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ProductCollection|MockObject
     */
    private function getMockMagentoProductCollection()
    {
        $mockConnection = $this->getMockBuilder(DbAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockConnection->method('quoteIdentifier')->willReturnArgument(0);

        $mockSelect = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSelect->method('getConnection')->willReturn($mockConnection);

        $mockResource = $this->getMockBuilder(ProductResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockResource->method('getTable')->willReturnArgument(0);

        $return = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $return->method('getSelect')->willReturn($mockSelect);
        $return->method('getResource')->willReturn($mockResource);

        return $return;
    }

    /**
     * @return KlevuProductCollection|MockObject
     */
    private function getMockProductCollection()
    {
        return $this->getMockBuilder(KlevuProductCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return OptionProvider&MockObject
     */
    private function getMockOptionProvider()
    {
        return $this->getMockBuilder(OptionProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return JoinParentVisibilityToSelect&MockObject
     */
    private function getMockJoinParentVisibilityToSelectService()
    {
        $mockJoinParentVisibilityToSelectService = $this->getMockBuilder(JoinParentVisibilityToSelect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockJoinParentVisibilityToSelectService->method('execute')
            ->willReturnArgument(0);

        return $mockJoinParentVisibilityToSelectService;
    }

    /**
     * @return JoinParentStatusToSelect&MockObject
     */
    private function getMockJoinParentStatusToSelectService()
    {
        $mockJoinParentStatusToSelectService = $this->getMockBuilder(JoinParentStatusToSelect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockJoinParentStatusToSelectService->method('execute')
            ->willReturnArgument(0);

        return $mockJoinParentStatusToSelectService;
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
