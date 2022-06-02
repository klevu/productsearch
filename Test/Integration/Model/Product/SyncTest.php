<?php

namespace Klevu\Search\Test\Integration\Model\Product;

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

class SyncTest extends TestCase
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
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/currency/options/base USD
     * @magentoConfigFixture default/currency/options/default JPY
     * @magentoConfigFixture default/currency/options/allow JPY,EUR,GBP
     * @magentoConfigFixture default_store currency/options/default JPY
     * @magentoConfigFixture default_store currency/options/allow JPY,EUR,GBP
     * @magentoConfigFixture klevu_test_store_1_store currency/options/default JPY
     * @magentoConfigFixture klevu_test_store_1_store currency/options/allow JPY,EUR,GBP
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testSyncData_Delete_SimpleProduct()
    {
        $this->setupPhp5();

        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, [
                'klevu_simple_1',
            ]),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadKlevuProductSyncFixtures();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);

        self::loadKlevuProductSyncFixturesRollback();
        static::loadWebsiteFixturesRollback();

    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/currency/options/base USD
     * @magentoConfigFixture default/currency/options/default JPY
     * @magentoConfigFixture default/currency/options/allow JPY,EUR,GBP
     * @magentoConfigFixture default_store currency/options/default JPY
     * @magentoConfigFixture default_store currency/options/allow JPY,EUR,GBP
     * @magentoConfigFixture klevu_test_store_1_store currency/options/default JPY
     * @magentoConfigFixture klevu_test_store_1_store currency/options/allow JPY,EUR,GBP
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testSyncData_Update_SimpleProduct()
    {
        $this->setupPhp5();

        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, []),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_1' => [
                    'name' => '[Klevu] Simple Product 1',
                    'sku' => 'klevu_simple_1',
                    'price' => 10.0,
                    'salePrice' => 10.0,
                    'startPrice' => 10.0,
                    'visibility' => 'catalog-search',
                    'dateAdded' => date('Y-m-d'),
                    'product_type' => 'simple',
                    'currency' => 'USD',
                    'category' => '',
                    'listCategory' => 'KLEVU_PRODUCT',
                    'categoryIds' => '',
                    'categoryPaths' => '',
                    'groupPrices' => null,
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixtures();
        self::loadKlevuProductSyncFixtures();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);

        self::loadKlevuProductSyncFixturesRollback();
        self::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/currency/options/base USD
     * @magentoConfigFixture default/currency/options/default JPY
     * @magentoConfigFixture default/currency/options/allow JPY,EUR,GBP
     * @magentoConfigFixture default_store currency/options/default JPY
     * @magentoConfigFixture default_store currency/options/allow JPY,EUR,GBP
     * @magentoConfigFixture klevu_test_store_1_store currency/options/default JPY
     * @magentoConfigFixture klevu_test_store_1_store currency/options/allow JPY,EUR,GBP
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testSyncData_Add_SimpleProduct()
    {
        $this->setupPhp5();

        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, []),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
            'klevu_simple_1' => [
        'name' => '[Klevu] Simple Product 1',
        'sku' => 'klevu_simple_1',
        'price' => 10.0,
        'salePrice' => 10.0,
        'startPrice' => 10.0,
        'visibility' => 'catalog-search',
        'dateAdded' => date('Y-m-d'),
        'product_type' => 'simple',
        'currency' => 'USD',
        'category' => '',
        'listCategory' => 'KLEVU_PRODUCT',
        'categoryIds' => '',
        'categoryPaths' => '',
        'groupPrices' => null,
        'inStock' => 'yes',
    ],
            ]),
            Addrecords::class
        );

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixtures();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);

        self::loadProductFixturesRollback();
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @param bool|string $return
     * @param array $expectedSkus
     * @return Deleterecords|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getDeleterecordsMock($return, array $expectedSkus)
    {
        $response = $this->getResponseMock(
            true === $return,
            is_string($return) ? $return : null
        );

        $deleterecordsMock = $this->getMockBuilder(Deleterecords::class)
            ->disableOriginalConstructor()
            ->getMock();
        $deleterecordsMock->expects($this->exactly(count($expectedSkus)))
            ->method('execute')
            ->willReturnCallback(function ($parameters) use ($response, $expectedSkus) {
                if (method_exists($this, 'assertIsArray')) {
                    $this->assertIsArray($parameters);
                } else {
                    $this->assertTrue(is_array($parameters), 'Is Array');
                }

                $this->assertArrayHasKey('sessionId', $parameters);
                $this->assertArrayHasKey('records', $parameters);

                if (method_exists($this, 'assertIsArray')) {
                    $this->assertIsArray($parameters['records']);
                } else {
                    $this->assertTrue(is_array($parameters['records']), 'Is Array');
                }

                foreach (array_values($parameters['records']) as $i => $record) {
                    if (method_exists($this, 'assertIsArray')) {
                        $this->assertIsArray($record);
                    } else {
                        $this->assertTrue(is_array($record), 'Is Array');
                    }

                    $product = $this->productRepository->get($expectedSkus[$i]);
                    switch ($product->getTypeId()) {
                        case 'simple':
                            $expectedData = [
                                'id' => (string)$product->getId(),
                            ];
                            break;

                        default:
                            throw new \InvalidArgumentException(sprintf(
                                'Product type %s not currently supported',
                                $product->getTypeId()
                            ));
                            break;
                    }

                    $this->assertSame($expectedData, $record);
                }

                return $response;
            });

        return $deleterecordsMock;
    }

    /**
     * @param bool|string $return
     * @param array $expectedProductData
     * @return Updaterecords|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getUpdaterecordsMock($return, array $expectedProductData)
    {
        $response = $this->getResponseMock(
            true === $return,
            is_string($return) ? $return : null
        );

        $updaterecordsMock = $this->getMockBuilder(Updaterecords::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updaterecordsMock->expects($this->exactly(count($expectedProductData)))
            ->method('execute')
            ->willReturnCallback(function ($parameters) use ($response, $expectedProductData) {
                if (method_exists($this, 'assertIsArray')) {
                    $this->assertIsArray($parameters);
                } else {
                    $this->assertTrue(is_array($parameters), 'Is Array');
                }

                $this->assertArrayHasKey('sessionId', $parameters);
                $this->assertArrayHasKey('records', $parameters);

                if (method_exists($this, 'assertIsArray')) {
                    $this->assertIsArray($parameters['records']);
                } else {
                    $this->assertTrue(is_array($parameters['records']), 'Is Array');
                }

                foreach ($parameters['records'] as $record) {
                    if (method_exists($this, 'assertIsArray')) {
                        $this->assertIsArray($record);
                    } else {
                        $this->assertTrue(is_array($record), 'Is Array');
                    }

                    $this->assertArrayHasKey('sku', $record);
                    $sku = $record['sku'];
                    $this->assertArrayHasKey($sku, $expectedProductData);

                    $product = $this->productRepository->get($sku);
                    switch ($expectedProductData[$sku]['product_type']) {
                        case 'simple':
                            $expectedProductData[$sku]['itemGroupId'] = 0;
                            $expectedProductData[$sku]['id'] = (string)$product->getId();
                            break;

                        default:
                            throw new \InvalidArgumentException(sprintf(
                                'Product type %s not currently supported',
                                $expectedProductData[$sku]['product_type']
                            ));
                            break;
                    }

                    $missingData = array_diff_key($expectedProductData[$sku], $record);
                    $this->assertSame([], $missingData);

                    $dataIntersect = array_intersect_key($record, $expectedProductData[$sku]);
                    foreach ($dataIntersect as $key => $value) {
                        $this->assertSame($expectedProductData[$sku][$key], $value, $key);
                    }
                }

                return $response;
            });

        return $updaterecordsMock;
    }

    /**
     * @param bool|string $return
     * @param array $expectedProductData
     * @return Addrecords|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getAddrecordsMock($return, array $expectedProductData)
    {
        $response = $this->getResponseMock(
            true === $return,
            is_string($return) ? $return : null
        );

        $addrecordsMock = $this->getMockBuilder(Addrecords::class)
            ->disableOriginalConstructor()
            ->getMock();
        $addrecordsMock->expects($this->exactly(count($expectedProductData)))
            ->method('execute')
            ->willReturnCallback(function ($parameters) use ($response, $expectedProductData) {
                if (method_exists($this, 'assertIsArray')) {
                    $this->assertIsArray($parameters);
                } else {
                    $this->assertTrue(is_array($parameters), 'Is Array');
                }

                $this->assertArrayHasKey('sessionId', $parameters);
                $this->assertArrayHasKey('records', $parameters);

                if (method_exists($this, 'assertIsArray')) {
                    $this->assertIsArray($parameters['records']);
                } else {
                    $this->assertTrue(is_array($parameters['records']), 'Is Array');
                }

                foreach ($parameters['records'] as $record) {
                    if (method_exists($this, 'assertIsArray')) {
                        $this->assertIsArray($record);
                    } else {
                        $this->assertTrue(is_array($record), 'Is Array');
                    }

                    $this->assertArrayHasKey('sku', $record);
                    $sku = $record['sku'];
                    $this->assertArrayHasKey($sku, $expectedProductData);

                    $product = $this->productRepository->get($sku);
                    switch ($expectedProductData[$sku]['product_type']) {
                        case 'simple':
                            $expectedProductData[$sku]['itemGroupId'] = 0;
                            $expectedProductData[$sku]['id'] = (string)$product->getId();
                            break;

                        default:
                            throw new \InvalidArgumentException(sprintf(
                                'Product type %s not currently supported',
                                $expectedProductData[$sku]['product_type']
                            ));
                            break;
                    }

                    $missingData = array_diff_key($expectedProductData[$sku], $record);
                    $this->assertEmpty($missingData);

                    $dataIntersect = array_intersect_key($record, $expectedProductData[$sku]);
                    foreach ($dataIntersect as $key => $value) {
                        $this->assertSame($expectedProductData[$sku][$key], $value, $key);
                    }
                }

                return $response;
            });

        return $addrecordsMock;
    }

    /**
     * @param bool $isSuccess
     * @param string $message
     * @return Response|\PHPUnit\Framework\MockObject\MockObject
     * @throws \ReflectionException
     */
    private function getResponseMock($isSuccess, $message = '')
    {
        if (!method_exists($this, 'createMock')) {
            return $this->getResponseMockLegacy($isSuccess, $message);
        }

        $responseMock = $this->createMock(Response::class);
        $responseMock->method('isSuccess')->willReturn($isSuccess);
        $responseMock->method('getMessage')->willReturn($message);

        return $responseMock;
    }

    /**
     * @param bool $isSuccess
     * @param string $message
     * @return Response|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getResponseMockLegacy($isSuccess, $message = '')
    {
        $responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $responseMock->expects($this->any())->method('isSuccess')->willReturn($isSuccess);
        $responseMock->expects($this->any())->method('getMessage')->willReturn($message);

        return $responseMock;
    }

    /**
     * Loads store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../_files/productFixtures.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../_files/klevuProductSyncFixtures_rollback.php';
    }

    /**
     * Loads klevu product sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuProductSyncFixtures()
    {
        include __DIR__ . '/../../_files/klevuProductSyncFixtures.php';
    }

    /**
     * Rolls back klevu product sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuProductSyncFixturesRollback()
    {
        include __DIR__ . '/../../_files/productFixtures_rollback.php';
    }
}
