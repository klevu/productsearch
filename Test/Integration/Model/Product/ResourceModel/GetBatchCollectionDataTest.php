<?php

namespace Klevu\Search\Test\Integration\Model\Product\ResourceModel;

use Klevu\Search\Model\Product\ResourceModel\Product as ProductResourceModel;
use Klevu\Search\Model\Product\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product as MagentoProductResource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetBatchCollectionDataTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;
    /**
     * @var array[]
     */
    private $mockProducts = [
        [
            'row_id' => 1234,
            'entity_id' => 5678,
            'attribute_set' => 1,
            'type_id' => 'simple',
            'sku' => 'product 1'
        ],
        [
            'row_id' => 2345,
            'entity_id' => 6789,
            'attribute_set' => 2,
            'type_id' => 'simple',
            'sku' => 'product 2'
        ],
        [
            'row_id' => 3456,
            'entity_id' => 5678,
            'attribute_set' => 2,
            'type_id' => 'simple',
            'sku' => 'product 2'
        ]
    ];

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testEntityIdIsReturned()
    {
        $this->setupPhp5();
        $store = $this->getStore();

        $mockConnection = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockConnection->expects($this->never())->method('fetchCol');
        $mockConnection->expects($this->once())->method('fetchAll')->wilLReturn($this->mockProducts);

        $mockMageProductResource = $this->getMockBuilder(MagentoProductResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockMageProductResource->expects($this->once())->method('getConnection')->willReturn($mockConnection);

        $klevuProductCollection = $this->objectManager->get(ProductCollection::class);
        $magentoProductCollection = $klevuProductCollection->initCollectionByType($store, [], []);
        $productIds = [];
        $lastEntityId = null;

        $resourceModel = $this->objectManager->create(ProductResourceModel::class, [
            'productResourceModel' => $mockMageProductResource
        ]);
        $productIds = $resourceModel->getBatchDataForCollection(
            $magentoProductCollection,
            $store,
            $productIds,
            $lastEntityId
        );

        if (method_exists($this, 'assertArrayContains')) {
            $this->assertArrayContains(5678, $productIds);
            $this->assertArrayContains(6789, $productIds);
        } else {
            $this->assertContains(5678, $productIds);
            $this->assertContains(6789, $productIds);
        }
        $this->assertCount(1, array_filter($productIds, function ($productId) {
            return $productId === 5678;
        }));
        $this->assertNotContains(1234, $productIds);
        $this->assertNotContains(2345, $productIds);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode = 'klevu_test_store_1')
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }
}
