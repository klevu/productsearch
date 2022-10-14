<?php

namespace Klevu\Search\Test\Integration\Model\Product\Sync;

use Klevu\Search\Model\Api\Action\Addrecords;
use Klevu\Search\Model\Api\Action\Deleterecords;
use Klevu\Search\Model\Api\Action\Updaterecords;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Model\Product\Sync as ProductSync;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class RatingDataTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/attributes/other sku,rating,review_count
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/attributes/other sku,rating,review_count
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/general/rating_flag 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rating_flag 1
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testSync()
    {
        $this->setupPhp5();

        $responseMock = $this->getResponseMock();

        $deleteRecordsMock = $this->getDeleteRecordsMock();
        $deleteRecordsMock->expects($this->never())->method('execute');
        $this->objectManager->addSharedInstance($deleteRecordsMock, Deleterecords::class);

        $updateRecordsMock = $this->getUpdateRecordsMock();
        $updateRecordsMock->expects($this->never())->method('execute');
        $this->objectManager->addSharedInstance($updateRecordsMock, Updaterecords::class);

        $addRecordsMock = $this->getAddRecordsMock();
        $addRecordsMock->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function (array $parameters) use ($responseMock) {
                $this->assertArrayHasKey('records', $parameters);
                $this->assertSame([
                    'klevu_simple_ratingtest_without_rating_without_rating_count',
                    'klevu_simple_ratingtest_without_rating_with_rating_count',
                    'klevu_simple_ratingtest_with_rating_without_rating_count',
                    'klevu_simple_ratingtest_with_rating_with_rating_count',
                ], array_column($parameters['records'], 'sku'));

                // Note: we use isset in the following checks when a field is not expected as either null
                //  or a missing field are valid
                foreach ($parameters['records'] as $record) {
                    switch ($record['sku']) {
                        case 'klevu_simple_ratingtest_without_rating_without_rating_count':
                            $this->assertFalse(isset($record['rating']), $record['sku'] . ' has rating');
                            $this->assertFalse(isset($record['rating_count']), $record['sku'] . ' has rating');
                            break;

                        case 'klevu_simple_ratingtest_without_rating_with_rating_count':
                            $this->assertFalse(isset($record['rating']), $record['sku'] . ' has rating');
                            $this->assertArrayHasKey('rating_count', $record, $record['sku']);
                            $this->assertSame(10, $record['rating_count']);
                            break;

                        case 'klevu_simple_ratingtest_with_rating_without_rating_count':
                            $this->assertArrayHasKey('rating', $record, $record['sku']);
                            $this->assertSame(3.75, $record['rating']);
                            $this->assertFalse(isset($record['rating_count']), $record['sku'] . ' has rating');
                            break;

                        case 'klevu_simple_ratingtest_with_rating_with_rating_count':
                            $this->assertArrayHasKey('rating', $record, $record['sku']);
                            $this->assertSame(2.1, $record['rating']);

                            $this->assertArrayHasKey('rating_count', $record, $record['sku']);
                            $this->assertSame(16, $record['rating_count']);
                            break;
                    }

                    if (array_key_exists('other', $record)) {
                        if (method_exists($this, 'assertIsArray')) {
                            $this->assertIsArray($record['other'], $record['sku'] . ': other');
                        } else {
                            $this->assertTrue(is_array($record['other']), $record['sku'] . ': other is array');
                        }
                        $this->assertArrayNotHasKey('rating', $record['other'], $record['sku'] . ': other');
                        $this->assertArrayNotHasKey('rating_count', $record['other'], $record['sku'] . ': other');
                        $this->assertArrayNotHasKey('review_count', $record['other'], $record['sku'] . ': other');
                    }

                    $this->assertArrayHasKey('otherAttributeToIndex', $record, $record['sku']);
                    if (method_exists($this, 'assertIsArray')) {
                        $this->assertIsArray($record['otherAttributeToIndex'], $record['sku'] . ': otherAttributeToIndex');
                    } else {
                        $this->assertTrue(is_array($record['otherAttributeToIndex']), $record['sku'] . ': otherAttributeToIndex is array');
                    }
                    $this->assertArrayHasKey('sku', $record['otherAttributeToIndex'], $record['sku'] . ': other');
                    $this->assertSame($record['sku'], $record['otherAttributeToIndex']['sku'], $record['sku'] . ': other');
                    $this->assertArrayNotHasKey('rating', $record['otherAttributeToIndex'], $record['sku'] . ': otherAttributeToIndex');
                    $this->assertArrayNotHasKey('rating_count', $record['otherAttributeToIndex'], $record['sku'] . ': otherAttributeToIndex');
                    $this->assertArrayNotHasKey('review_count', $record['otherAttributeToIndex'], $record['sku'] . ': otherAttributeToIndex');
                }

                return $responseMock;
            });
        $this->objectManager->addSharedInstance($addRecordsMock, Addrecords::class);

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixtures();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);

        self::loadProductFixturesRollback();
    }

    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
    }

    private function getResponseMock()
    {
        $responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $responseMock->method('isSuccess')->willReturn(true);
        $responseMock->method('getMessage')->willReturn(null);

        return $responseMock;
    }

    private function getDeleteRecordsMock()
    {
        return $this->getMockBuilder(Deleterecords::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getUpdateRecordsMock()
    {
        return $this->getMockBuilder(Updaterecords::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getAddRecordsMock()
    {
        return $this->getMockBuilder(Addrecords::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Loads store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../../_files/productFixtures_ratings.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../../_files/productFixtures_ratings_rollback.php';
    }
}
