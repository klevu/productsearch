<?php

namespace Klevu\Search\Test\Integration\Model\Product;

use Klevu\Search\Api\MagentoProductSyncRepositoryInterface;
use Klevu\Search\Model\Product\MagentoProductActions;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductActionsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @magentoAppArea frontend
     *
     * Background: incorrectly setting $lastProductEntityId was causing
     * getMagentoProductIds to return IDs that had already been processed
     * in stores where product ids exceeded 100000
     * KS-12172
     */
    public function testAddProductCollection_WhileLoop_BreaksWhenItShould()
    {
        $this->setupPhp5();

        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $store = $storeManager->getStore(1);

        $mockProductCollection = $this->getMockProductCollection();
        $mockProductCollection->method('isChildProductCollection')->willReturn(false);

        $mockChildProductCollection = $this->getMockProductCollection();
        $mockChildProductCollection->method('isChildProductCollection')->willReturn(true);

        $mockProductRepository = $this->getMockBuilder(MagentoProductSyncRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockProductRepository->method('getProductIdsCollection')->willReturn($mockProductCollection);
        $mockProductRepository->method('getChildProductIdsCollection')->willReturn($mockChildProductCollection);
        $mockProductRepository->method('getParentProductRelations')->willReturn([]);

        $mockProductRepository->expects($this->once())
            ->method('getMaxProductId')
            ->willReturn(999999);

        // In reality this would be 100, made smaller to keep testing simple.
        $numberOfProductsToReturn = 6; // max number of product ids per mocked collection is 11 so this will cause 2 loops

        // The data returned from the callback should cause getBatchDataForCollection to be called 4 times, twice in each loop.
        $maxAllowedCalls = 4;

        $mockProductRepository->expects($this->atMost($maxAllowedCalls))
            ->method('getBatchDataForCollection')
            ->willReturnCallback(function ($productCollection, $store, $productIds, $lastEntityId) use ($numberOfProductsToReturn) {
                // method added to mock product collections above for the purpose of this test only, does not exist in $productCollection
                if ($productCollection->isChildProductCollection()) {
                    $productIds = [
                        '123456'
                    ];
                } else {
                    $productIds = [
                        '26',
                        '1863',
                        '35932',
                        '100005',
                        '100010',
                        '110000',
                        '111000',
                        '215000',
                        '350000',
                        '950000',
                        '999999'
                    ];
                }

                $filteredProductIds = array_filter($productIds, function ($productId) use ($lastEntityId) {
                    return $productId > $lastEntityId;
                });

                return array_slice($filteredProductIds, 0, $numberOfProductsToReturn);
            });

        $productAction = $this->objectManager->create(MagentoProductActions::class, [
            'magentoProductRepository' => $mockProductRepository
        ]);
        $productAction->addProductCollection($store);
    }

    /**
     * @return ProductCollection|MockObject
     */
    private function getMockProductCollection()
    {
        $mockProductCollectionBuilder = $this->getMockBuilder(ProductCollection::class);
        // method added to enable us to tell the 2 collection apart in the $mockProductRepository callback
        if (method_exists($mockProductCollectionBuilder, 'addMethods')) {
            $mockProductCollectionBuilder->addMethods(['isChildProductCollection']);
        } else {
            $mockProductCollectionBuilder->setMethods(['isChildProductCollection']);
        }

        return $mockProductCollectionBuilder->disableOriginalConstructor()->getMock();
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }
}
