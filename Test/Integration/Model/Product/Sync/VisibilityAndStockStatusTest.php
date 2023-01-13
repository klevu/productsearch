<?php
/** phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps */

namespace Klevu\Search\Test\Integration\Model\Product\Sync;

use Klevu\Search\Api\Service\Catalog\Product\StockServiceInterface;
use Klevu\Search\Model\Api\Action\Addrecords;
use Klevu\Search\Model\Api\Action\Deleterecords;
use Klevu\Search\Model\Api\Action\Updaterecords;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Model\Product\Sync as ProductSync;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * See also KS-11955 for price related fixtures
 */
class VisibilityAndStockStatusTest extends TestCase
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
     * @var MockObject&LoggerInterface
     */
    private $loggerMock;

    /**
     * @var int[]
     */
    private $deleteRecordsFixtures = [];

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager->addSharedInstance($this->loggerMock, LoggerInterface::class);
        $this->objectManager->addSharedInstance($this->loggerMock, 'Klevu\Search\Logger\Logger\Search');

        $this->deleteRecordsFixtures = [];

        /** @var StockServiceInterface $stockService */
        $stockService = $this->objectManager->get(StockServiceInterface::class);
        $stockService->clearCache();
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    public function testSyncData_AddRecords_ObjectMethod_IncludeOosDisabled_ShowOosDisabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, 0),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->loggerMock->expects($this->never())->method('emergency');
        $this->loggerMock->expects($this->never())->method('critical');
        $this->loggerMock->expects($this->never())->method('alert');
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 1
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    public function testSyncData_AddRecords_ObjectMethod_IncludeOosDisabled_ShowOosDisabled_CatalogVisibilityEnabled()
    {
        $this->setupPhp5();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, 0),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_viscatalog' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->loggerMock->expects($this->never())->method('emergency');
        $this->loggerMock->expects($this->never())->method('critical');
        $this->loggerMock->expects($this->never())->method('alert');
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    public function testSyncData_AddRecords_ObjectMethod_IncludeOosDisabled_ShowOosEnabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, 0),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 12.5,
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->loggerMock->expects($this->never())->method('emergency');
        $this->loggerMock->expects($this->never())->method('critical');
        $this->loggerMock->expects($this->never())->method('alert');
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    public function testSyncData_AddRecords_ObjectMethod_IncludeOosEnabled_ShowOosDisabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, 0),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_oos_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_oos_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childrendisabled' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_oos_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->loggerMock->expects($this->never())->method('emergency');
        $this->loggerMock->expects($this->never())->method('critical');
        $this->loggerMock->expects($this->never())->method('alert');
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $this->objectManager->get(\Klevu\Search\Service\Catalog\Product\Stock::class)->clearCache();
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    public function testSyncData_AddRecords_ObjectMethod_IncludeOosEnabled_ShowOosEnabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, 0),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_oos_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childrenoos' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_oos_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childrendisabled' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_oos_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->loggerMock->expects($this->never())->method('emergency');
        $this->loggerMock->expects($this->never())->method('critical');
        $this->loggerMock->expects($this->never())->method('alert');
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 1
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    public function testSyncData_AddRecords_ObjectMethod_IncludeOosEnabled_ShowOosEnabled_CatalogVisibilityEnabled()
    {
        $this->setupPhp5();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, 0),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_oos_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_viscatalog' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childrenoos' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_oos_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childrendisabled' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_oos_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->loggerMock->expects($this->never())->method('emergency');
        $this->loggerMock->expects($this->never())->method('critical');
        $this->loggerMock->expects($this->never())->method('alert');
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 1
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_AddRecords_CollectionMethod_IncludeOosDisabled_ShowOosDisabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, 0),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->loggerMock->expects($this->never())->method('emergency');
        $this->loggerMock->expects($this->never())->method('critical');
        $this->loggerMock->expects($this->never())->method('alert');
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 1
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 1
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_AddRecords_CollectionMethod_IncludeOosDisabled_ShowOosDisabled_CatalogVisibilityEnabled()
    {
        $this->setupPhp5();

        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, 0),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_viscatalog' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->loggerMock->expects($this->never())->method('emergency');
        $this->loggerMock->expects($this->never())->method('critical');
        $this->loggerMock->expects($this->never())->method('alert');
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 1
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_AddRecords_CollectionMethod_IncludeOosDisabled_ShowOosEnabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, 0),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 12.5,
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->loggerMock->expects($this->never())->method('emergency');
        $this->loggerMock->expects($this->never())->method('critical');
        $this->loggerMock->expects($this->never())->method('alert');
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 1
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_AddRecords_CollectionMethod_IncludeOosEnabled_ShowOosDisabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, 0),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_oos_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_oos_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childrendisabled' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_oos_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->loggerMock->expects($this->never())->method('emergency');
        $this->loggerMock->expects($this->never())->method('critical');
        $this->loggerMock->expects($this->never())->method('alert');
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 1
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_AddRecords_CollectionMethod_IncludeOosEnabled_ShowOosEnabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, 0),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_oos_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childrenoos' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_oos_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childrendisabled' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_oos_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->loggerMock->expects($this->never())->method('emergency');
        $this->loggerMock->expects($this->never())->method('critical');
        $this->loggerMock->expects($this->never())->method('alert');
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 1
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 1
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    public function testSyncData_AddRecords_CollectionMethod_IncludeOosEnabled_ShowOosEnabled_CatalogVisibilityEnabled()
    {
        $this->setupPhp5();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, 0),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, []),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_oos_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_viscatalog' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childrenoos' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_oos_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childrendisabled' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_oos_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        $this->loggerMock->expects($this->never())->method('emergency');
        $this->loggerMock->expects($this->never())->method('critical');
        $this->loggerMock->expects($this->never())->method('alert');
        $this->loggerMock->expects($this->never())->method('error');
        $this->loggerMock->expects($this->never())->method('warning');

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_UpdateAndDeleteRecords_ObjectMethod_IncludeOosDisabled_ShowOosDisabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        $expectedDeletedSkus = [
            [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_notvisible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_viscatalog',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_visible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_notvisible',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_childreninstock',
                'product' => 'klevu_simple_synctest_child_oos',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_childrenoos',
                'product' => 'klevu_simple_synctest_child_oos',
            ], [
                'parent' => 'klevu_configurable_synctest_oos_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_oos_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_childrenoos',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_oos_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_childrendisabled',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_childrenoos',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_oos_childreninstock',
            ]
        ];
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, count($expectedDeletedSkus)),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();
        self::loadKlevuProductSyncFixturesActual();

        $this->deleteRecordsFixtures = array_map(function ($deleteFixture) {
            $product = $this->productRepository->get($deleteFixture['product']);
            $parent = isset($deleteFixture['parent'])
                ? $this->productRepository->get($deleteFixture['parent'])
                : null;

            $return = '';
            if ($parent) {
                $return .= $parent->getId() . '-';
            }
            $return .= $product->getId();

            return $return;
        }, $expectedDeletedSkus);

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 1
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_UpdateAndDeleteRecords_ObjectMethod_IncludeOosDisabled_ShowOosDisabled_CatalogVisibilityEnabled()
    {
        $this->setupPhp5();

        $expectedDeletedSkus = [
            [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_notvisible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_visible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_notvisible',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_childreninstock',
                'product' => 'klevu_simple_synctest_child_oos',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_childrenoos',
                'product' => 'klevu_simple_synctest_child_oos',
            ], [
                'parent' => 'klevu_configurable_synctest_oos_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_oos_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_childrenoos',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_oos_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_childrendisabled',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_childrenoos',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_oos_childreninstock',
            ]
        ];
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, count($expectedDeletedSkus)),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_viscatalog' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();
        self::loadKlevuProductSyncFixturesActual();

        $this->deleteRecordsFixtures = array_map(function ($deleteFixture) {
            $product = $this->productRepository->get($deleteFixture['product']);
            $parent = isset($deleteFixture['parent'])
                ? $this->productRepository->get($deleteFixture['parent'])
                : null;

            $return = '';
            if ($parent) {
                $return .= $parent->getId() . '-';
            }
            $return .= $product->getId();

            return $return;
        }, $expectedDeletedSkus);

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_UpdateAndDeleteRecords_ObjectMethod_IncludeOosDisabled_ShowOosEnabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        $expectedDeletedSkus = [
            [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_notvisible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_viscatalog',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_visible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_notvisible',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_childreninstock',
                'product' => 'klevu_simple_synctest_child_oos',
            ],
            [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_childrenoos',
                'product' => 'klevu_simple_synctest_child_oos',
            ], [
                'parent' => 'klevu_configurable_synctest_oos_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_oos_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_childrenoos',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_oos_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_childrendisabled',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_childrenoos',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_oos_childreninstock',
            ]
        ];
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, count($expectedDeletedSkus)),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 12.5,
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();
        self::loadKlevuProductSyncFixturesActual();

        $this->deleteRecordsFixtures = array_map(function ($deleteFixture) {
            $product = $this->productRepository->get($deleteFixture['product']);
            $parent = isset($deleteFixture['parent'])
                ? $this->productRepository->get($deleteFixture['parent'])
                : null;

            $return = '';
            if ($parent) {
                $return .= $parent->getId() . '-';
            }
            $return .= $product->getId();

            return $return;
        }, $expectedDeletedSkus);

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_UpdateAndDeleteRecords_ObjectMethod_IncludeOosEnabled_ShowOosDisabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        $expectedDeletedSkus = [
            [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_notvisible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_viscatalog',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_notvisible',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
            ]
        ];
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, count($expectedDeletedSkus)),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_oos_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_oos_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childrendisabled' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_oos_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();
        self::loadKlevuProductSyncFixturesActual();

        $this->deleteRecordsFixtures = array_map(function ($deleteFixture) {
            $product = $this->productRepository->get($deleteFixture['product']);
            $parent = isset($deleteFixture['parent'])
                ? $this->productRepository->get($deleteFixture['parent'])
                : null;

            $return = '';
            if ($parent) {
                $return .= $parent->getId() . '-';
            }
            $return .= $product->getId();

            return $return;
        }, $expectedDeletedSkus);

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_UpdateAndDeleteRecords_ObjectMethod_IncludeOosEnabled_ShowOosEnabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        $expectedDeletedSkus = [
            [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_notvisible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_viscatalog',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_notvisible',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
            ]
        ];
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, count($expectedDeletedSkus)),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_oos_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childrenoos' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_oos_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childrendisabled' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_oos_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();
        self::loadKlevuProductSyncFixturesActual();

        $this->deleteRecordsFixtures = array_map(function ($deleteFixture) {
            $product = $this->productRepository->get($deleteFixture['product']);
            $parent = isset($deleteFixture['parent'])
                ? $this->productRepository->get($deleteFixture['parent'])
                : null;

            $return = '';
            if ($parent) {
                $return .= $parent->getId() . '-';
            }
            $return .= $product->getId();

            return $return;
        }, $expectedDeletedSkus);

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 0
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 1
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_UpdateAndDeleteRecords_ObjectMethod_IncludeOosEnabled_ShowOosEnabled_CatalogVisibilityEnabled()
    {
        $this->setupPhp5();

        $expectedDeletedSkus = [
            [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_notvisible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_notvisible',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
            ]
        ];
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, count($expectedDeletedSkus)),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_oos_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_viscatalog' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childrenoos' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_oos_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childrendisabled' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_oos_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();
        self::loadKlevuProductSyncFixturesActual();

        $this->deleteRecordsFixtures = array_map(function ($deleteFixture) {
            $product = $this->productRepository->get($deleteFixture['product']);
            $parent = isset($deleteFixture['parent'])
                ? $this->productRepository->get($deleteFixture['parent'])
                : null;

            $return = '';
            if ($parent) {
                $return .= $parent->getId() . '-';
            }
            $return .= $product->getId();

            return $return;
        }, $expectedDeletedSkus);

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 1
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_UpdateAndDeleteRecords_CollectionMethod_IncludeOosDisabled_ShowOosDisabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        $expectedDeletedSkus = [
            [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_notvisible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_viscatalog',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_visible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_notvisible',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_childreninstock',
                'product' => 'klevu_simple_synctest_child_oos',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_childrenoos',
                'product' => 'klevu_simple_synctest_child_oos',
            ], [
                'parent' => 'klevu_configurable_synctest_oos_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_oos_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_childrenoos',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_oos_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_childrendisabled',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_childrenoos',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_oos_childreninstock',
            ]
        ];
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, count($expectedDeletedSkus)),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();
        self::loadKlevuProductSyncFixturesActual();

        $this->deleteRecordsFixtures = array_map(function ($deleteFixture) {
            $product = $this->productRepository->get($deleteFixture['product']);
            $parent = isset($deleteFixture['parent'])
                ? $this->productRepository->get($deleteFixture['parent'])
                : null;

            $return = '';
            if ($parent) {
                $return .= $parent->getId() . '-';
            }
            $return .= $product->getId();

            return $return;
        }, $expectedDeletedSkus);

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 1
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 1
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_UpdateAndDeleteRecords_CollectionMethod_IncludeOosDisabled_ShowOosDisabled_CatalogVisibilityEnabled()
    {
        $this->setupPhp5();

        $expectedDeletedSkus = [
            [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_notvisible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_visible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_notvisible',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_childreninstock',
                'product' => 'klevu_simple_synctest_child_oos',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_childrenoos',
                'product' => 'klevu_simple_synctest_child_oos',
            ], [
                'parent' => 'klevu_configurable_synctest_oos_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_oos_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_childrenoos',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_oos_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_childrendisabled',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_childrenoos',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_oos_childreninstock',
            ]
        ];
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, count($expectedDeletedSkus)),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_viscatalog' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();
        self::loadKlevuProductSyncFixturesActual();

        $this->deleteRecordsFixtures = array_map(function ($deleteFixture) {
            $product = $this->productRepository->get($deleteFixture['product']);
            $parent = isset($deleteFixture['parent'])
                ? $this->productRepository->get($deleteFixture['parent'])
                : null;

            $return = '';
            if ($parent) {
                $return .= $parent->getId() . '-';
            }
            $return .= $product->getId();

            return $return;
        }, $expectedDeletedSkus);

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 1
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 0
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_UpdateAndDeleteRecords_CollectionMethod_IncludeOosDisabled_ShowOosEnabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        $expectedDeletedSkus = [
            [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_notvisible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_viscatalog',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_visible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_notvisible',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_childreninstock',
                'product' => 'klevu_simple_synctest_child_oos',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_childrenoos',
                'product' => 'klevu_simple_synctest_child_oos',
            ], [
                'parent' => 'klevu_configurable_synctest_oos_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_oos_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_childrenoos',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_oos_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_childrendisabled',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_childrenoos',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_oos_childreninstock',
            ]
        ];
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, count($expectedDeletedSkus)),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 12.5,
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();
        self::loadKlevuProductSyncFixturesActual();

        $this->deleteRecordsFixtures = array_map(function ($deleteFixture) {
            $product = $this->productRepository->get($deleteFixture['product']);
            $parent = isset($deleteFixture['parent'])
                ? $this->productRepository->get($deleteFixture['parent'])
                : null;

            $return = '';
            if ($parent) {
                $return .= $parent->getId() . '-';
            }
            $return .= $product->getId();

            return $return;
        }, $expectedDeletedSkus);

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 1
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 0
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_UpdateAndDeleteRecords_CollectionMethod_IncludeOosEnabled_ShowOosDisabled_CatalogViibilityDisabled()
    {
        $this->setupPhp5();

        $expectedDeletedSkus = [
            [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_notvisible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_viscatalog',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_notvisible',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
            ]
        ];
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, count($expectedDeletedSkus)),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_oos_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_oos_childreninstock' => [
                    'price' => 25.5,
                    'salePrice' => 25.5,
                    'startPrice' => 25.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childrendisabled' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_oos_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();
        self::loadKlevuProductSyncFixturesActual();

        $this->deleteRecordsFixtures = array_map(function ($deleteFixture) {
            $product = $this->productRepository->get($deleteFixture['product']);
            $parent = isset($deleteFixture['parent'])
                ? $this->productRepository->get($deleteFixture['parent'])
                : null;

            $return = '';
            if ($parent) {
                $return .= $parent->getId() . '-';
            }
            $return .= $product->getId();

            return $return;
        }, $expectedDeletedSkus);

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 1
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 0
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 0
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_UpdateAndDeleteRecords_CollectionMethod_IncludeOosEnabled_ShowOosEnabled_CatalogVisibilityDisabled()
    {
        $this->setupPhp5();

        $expectedDeletedSkus = [
            [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_notvisible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_viscatalog',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_notvisible',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_viscatalog_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
            ]
        ];
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, count($expectedDeletedSkus)),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_oos_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childrenoos' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_oos_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childrendisabled' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_oos_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();
        self::loadKlevuProductSyncFixturesActual();

        $this->deleteRecordsFixtures = array_map(function ($deleteFixture) {
            $product = $this->productRepository->get($deleteFixture['product']);
            $parent = isset($deleteFixture['parent'])
                ? $this->productRepository->get($deleteFixture['parent'])
                : null;

            $return = '';
            if ($parent) {
                $return .= $parent->getId() . '-';
            }
            $return .= $product->getId();

            return $return;
        }, $expectedDeletedSkus);

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
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
     * @magentoConfigFixture default/klevu_search/product_sync/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @magentoConfigFixture default/klevu_search/developer/collection_method 1
     * @magentoConfigFixture default/klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/include_oos 1
     * @magentoConfigFixture default/cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture klevu_test_store_1_store cataloginventory/options/show_out_of_stock 1
     * @magentoConfigFixture default/klevu_search/product_sync/catalogvisibility 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/catalogvisibility 1
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoDataFixture loadKlevuProductSyncFixtures
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public function testSyncData_UpdateAndDeleteRecords_CollectionMethod_IncludeOosEnabled_ShowOosEnabled_CatalogVisibilityEnabled()
    {
        $this->setupPhp5();

        $expectedDeletedSkus = [
            [
                'parent' => null,
                'product' => 'klevu_simple_synctest_instock_notvisible',
            ], [
                'parent' => null,
                'product' => 'klevu_simple_synctest_oos_notvisible',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_1',
            ], [
                'parent' => 'klevu_configurable_synctest_instock_notvisible_childreninstock',
                'product' => 'klevu_simple_synctest_child_instock_2',
            ], [
                'parent' => null,
                'product' => 'klevu_grouped_synctest_instock_notvisible_childreninstock',
            ], [
                'parent' => null,
                'product' => 'klevu_bundle_synctest_instock_notvisible_childreninstock',
            ]
        ];
        $this->objectManager->addSharedInstance(
            $this->getDeleterecordsMock(true, count($expectedDeletedSkus)),
            Deleterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getUpdaterecordsMock(true, [
                'klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_oos_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_simple_synctest_instock_vissearch' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_simple_synctest_instock_viscatalog' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_instock_visible' => [
                    'price' => 10.0,
                    'salePrice' => 4.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childreninstock;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => 4.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_viscatalog_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_instock_vissearch_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'search',
                    'inStock' => 'yes',
                ],
                'klevu_configurable_synctest_instock_childrenoos;;;;klevu_simple_synctest_child_oos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_1' => [
                    'price' => 20.0,
                    'salePrice' => 20.0,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_configurable_synctest_oos_childreninstock;;;;klevu_simple_synctest_child_instock_2' => [
                    'price' => 30.0,
                    'salePrice' => 7.99,
                    'startPrice' => 7.99,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_grouped_synctest_instock_childrenoos' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_oos_childreninstock' => [
                    'price' => 12.5, // Because Magento's setting pulls the OOS child into the calculations
                    'salePrice' => 12.5,
                    'startPrice' => 12.5,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_grouped_synctest_instock_childrendisabled' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
                'klevu_bundle_synctest_instock_childrenoos' => [
                    'price' => 0.0,
                    'salePrice' => 0.0,
                    'startPrice' => null,
                    'toPrice' => null,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_oos_childreninstock' => [
                    'price' => 15.99,
                    'salePrice' => 15.99,
                    'startPrice' => 15.99,
                    'toPrice' => 50.0,
                    'visibility' => 'catalog-search',
                    'inStock' => 'no',
                ],
                'klevu_bundle_synctest_instock_childreninstock_fixedprice' => [
                    'price' => 50.01,
                    'salePrice' => 50.01,
                    'startPrice' => 50.01,
                    'toPrice' => 100.01,
                    'visibility' => 'catalog-search',
                    'inStock' => 'yes',
                ],
            ]),
            Updaterecords::class
        );
        $this->objectManager->addSharedInstance(
            $this->getAddrecordsMock(true, []),
            Addrecords::class
        );
        // phpcs:enable Generic.Files.LineLength.TooLong

        // Cannot use annotations otherwise above shared instances are already
        //  instantiated by observers / plugins on product save
        self::loadProductFixturesActual();
        self::loadKlevuProductSyncFixturesActual();

        $this->deleteRecordsFixtures = array_map(function ($deleteFixture) {
            $product = $this->productRepository->get($deleteFixture['product']);
            $parent = isset($deleteFixture['parent'])
                ? $this->productRepository->get($deleteFixture['parent'])
                : null;

            $return = '';
            if ($parent) {
                $return .= $parent->getId() . '-';
            }
            $return .= $product->getId();

            return $return;
        }, $expectedDeletedSkus);

        $store = $this->storeManager->getStore('klevu_test_store_1');

        /** @var ProductSync $productSync */
        $productSync = $this->objectManager->get(ProductSync::class);
        $productSync->syncData($store);
    }

    /**
     * @param bool|string $return
     * @param int $expectedCount
     * @return Deleterecords|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getDeleterecordsMock($return, $expectedCount)
    {
        $response = $this->getResponseMock(
            true === $return,
            is_string($return) ? $return : null
        );

        $deleterecordsMock = $this->getMockBuilder(Deleterecords::class)
            ->disableOriginalConstructor()
            ->getMock();
        $deleterecordsMock->expects($expectedCount ? $this->once() : $this->never())
            ->method('execute')
            ->willReturnCallback(function ($parameters) use ($response) {
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

                $expectedData = array_fill_keys($this->deleteRecordsFixtures, null);
                ksort($expectedData);
                $receivedData = array_fill_keys(array_column($parameters['records'], 'id'), null);
                ksort($receivedData);

                $this->assertSame($expectedData, $receivedData, 'IDs received in deleteRecord execution');

                return $response;
            });

        return $deleterecordsMock;
    }

    /**
     * @param bool|string $return
     * @param string[] $expectedData
     * @return Updaterecords|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getUpdaterecordsMock($return, array $expectedData)
    {
        $response = $this->getResponseMock(
            true === $return,
            is_string($return) ? $return : null
        );

        $updaterecordsMock = $this->getMockBuilder(Updaterecords::class)
            ->disableOriginalConstructor()
            ->getMock();
        $updaterecordsMock->expects($expectedData ? $this->once() : $this->never())
            ->method('execute')
            ->willReturnCallback(function ($parameters) use ($response, $expectedData) {
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

                $expectedSkus = array_fill_keys(array_keys($expectedData), null);
                ksort($expectedSkus);
                $receivedSkus = array_fill_keys(
                    array_column($parameters['records'], 'sku'),
                    null
                );
                ksort($receivedSkus);
                $this->assertSame($expectedSkus, $receivedSkus, 'SKUs received in updateRecord execution');

                foreach ($parameters['records'] as $record) {
                    if (method_exists($this, 'assertIsArray')) {
                        $this->assertIsArray($record);
                    } else {
                        $this->assertTrue(is_array($record), 'Is Array');
                    }

                    $this->assertArrayHasKey('sku', $record);
                    $this->assertArrayHasKey($record['sku'], $expectedData);

                    foreach ($expectedData[$record['sku']] as $expectedField => $expectedValue) {
                        $this->assertArrayHasKey($expectedField, $record);
                        $this->assertSame($expectedValue, $record[$expectedField]);
                    }
                }

                return $response;
            });

        return $updaterecordsMock;
    }

    /**
     * @param bool|string $return
     * @param string[] $expectedData
     * @return Addrecords|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getAddrecordsMock($return, array $expectedData)
    {
        $response = $this->getResponseMock(
            true === $return,
            is_string($return) ? $return : null
        );

        $addrecordsMock = $this->getMockBuilder(Addrecords::class)
            ->disableOriginalConstructor()
            ->getMock();
        $addrecordsMock->expects($expectedData ? $this->once() : $this->never())
            ->method('execute')
            ->willReturnCallback(function ($parameters) use ($response, $expectedData) {
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

                $expectedSkus = array_fill_keys(array_keys($expectedData), null);
                ksort($expectedSkus);
                $receivedSkus = array_fill_keys(
                    array_column($parameters['records'], 'sku'),
                    null
                );
                ksort($receivedSkus);
                $this->assertSame($expectedSkus, $receivedSkus, 'SKUs received in addRecord execution');

                foreach ($parameters['records'] as $record) {
                    if (method_exists($this, 'assertIsArray')) {
                        $this->assertIsArray($record);
                    } else {
                        $this->assertTrue(is_array($record), 'Is Array');
                    }

                    $this->assertArrayHasKey('sku', $record);
                    if (isset($expectedData[$record['sku']])) {
                        foreach ($expectedData[$record['sku']] as $expectedField => $expectedValue) {
                            $this->assertArrayHasKey($expectedField, $record);
                            $this->assertSame(
                                $expectedValue,
                                $record[$expectedField],
                                '[' . $record['sku'] . '] ' . $expectedField
                            );
                        }
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
    public static function loadProductFixturesActual()
    {
        include __DIR__ . '/_files/productFixturesIncludeOos.php';
    }

    /**
     * Used by annotations so rollback is performed even when a test fails
     */
    public static function loadProductFixtures()
    {
        // Intentionally empty
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/_files/productFixturesIncludeOos_rollback.php';
    }

    /**
     * Loads klevu product sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuProductSyncFixturesActual()
    {
        include __DIR__ . '/_files/klevuProductSyncFixturesIncludeOos.php';
    }

    /**
     * Used by annotations so rollback is performed even when a test fails
     */
    public static function loadKlevuProductSyncFixtures()
    {
        // Intentionally empty
    }

    /**
     * Rolls back klevu product sync creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadKlevuProductSyncFixturesRollback()
    {
        include __DIR__ . '/_files/klevuProductSyncFixturesIncludeOos_rollback.php';
    }
}
