<?php

namespace Klevu\Search\Test\Integration\Model\Order\Sync;

use Klevu\Search\Model\Api\Action\Producttracking;
use Klevu\Search\Model\Api\Response;
use Klevu\Search\Model\Order\Sync as OrderSync;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BundleTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var Producttracking|MockObject
     */
    private $producttrackingActionMock;
    /**
     * @var int
     */
    private $correctDataCount;

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture default_store klevu_search/product_sync/order_sync_enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-klevu_test_store_1
     * @magentoConfigFixture klevu_test_store_2_store klevu_search/general/js_api_key klevu-klevu_test_store_2
     * @magentoConfigFixture default/currency/options/base USD
     * @magentoConfigFixture default/currency/options/allow USD,JPY,EUR,GBP
     * @magentoConfigFixture default_store currency/options/allow USD,JPY,EUR,GBP
     * @magentoConfigFixture klevu_test_store_1_store currency/options/allow USD,JPY,EUR,GBP
     * @magentoConfigFixture default/currency/options/default USD
     * @magentoConfigFixture default_store currency/options/default EUR
     * @magentoConfigFixture klevu_test_store_1_store currency/options/default GBP
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadCurrencyRatesFixtures
     * @magentoDataFixture loadBundleProductFixtures
     * @magentoDataFixture loadBundleOrderFixtures
     * @magentoDataFixture loadBundleKlevuOrderFixtures
     */
    public function testBundleProductOrderSync()
    {
        $this->setupPhp5();

        $this->correctDataCount = 0;

        $parentProduct = $this->getProduct('klevu_bundle_1');
        $response = $this->getResponseMock(true);

        $this->producttrackingActionMock->method('execute')
            ->willReturnCallback(function ($parameters) use ($parentProduct, $response) {
                $this->genericAssertions($parameters);

                $this->assertArrayHasKey('klevu_apiKey', $parameters);
                $this->assertSame('klevu-klevu_test_store_1', $parameters['klevu_apiKey']);

                $this->assertArrayHasKey('klevu_currency', $parameters);
                $this->assertSame('USD', $parameters['klevu_currency']);

                $this->assertArrayHasKey('klevu_unit', $parameters);
                $this->assertSame('1.0000', $parameters['klevu_unit']);

                $this->assertArrayHasKey('klevu_salePrice', $parameters);
                $this->assertSame(120.0000, $parameters['klevu_salePrice']);

                $this->assertArrayHasKey('klevu_productId', $parameters);
                $this->assertSame($parentProduct->getId(), $parameters['klevu_productId']);

                $this->assertArrayHasKey('klevu_productGroupId', $parameters);
                $this->assertSame($parentProduct->getId(), $parameters['klevu_productGroupId']);

                $this->assertArrayHasKey('klevu_productVariantId', $parameters);
                $this->assertSame($parentProduct->getId(), $parameters['klevu_productVariantId']);

                $this->correctDataCount++;

                return $response;
            });

        $orderSync = $this->objectManager->create(OrderSync::class, [
            'apiActionProducttracking' => $this->producttrackingActionMock
        ]);
        $orderSync->run();

        $this->assertSame(1, $this->correctDataCount, 'Number of successful order items synced');
    }

    /**
     * @param $parameters
     *
     * @return void
     */
    private function genericAssertions($parameters)
    {
        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($parameters);
        } else {
            $this->assertTrue(is_array($parameters), 'Is Array');
        }

        $this->assertArrayHasKey('klevu_type', $parameters);
        $this->assertSame('checkout', $parameters['klevu_type']);

        $this->assertArrayHasKey('klevu_orderDate', $parameters);
        $this->assertSame(date('Y-m-d'), $parameters['klevu_orderDate']);

        $this->assertArrayHasKey('klevu_productPosition', $parameters);
        $this->assertSame('1', $parameters['klevu_productPosition']);

        $this->assertArrayHasKey('klevu_orderId', $parameters);
        $this->assertNotNull($parameters['klevu_orderId']);

        $this->assertArrayHasKey('klevu_orderLineId', $parameters);
        $this->assertNotNull($parameters['klevu_orderLineId']);

        $this->assertArrayHasKey('klevu_checkoutDate', $parameters);
        $this->assertNotNull($parameters['klevu_checkoutDate']);

        $this->assertArrayHasKey('klevu_emailId', $parameters);
        $this->assertNotNull($parameters['klevu_emailId']);

        $this->assertArrayHasKey('klevu_storeTimezone', $parameters);
        $this->assertNotNull($parameters['klevu_storeTimezone']);

        $this->assertArrayHasKey('klevu_shopperIP', $parameters);

        $this->assertArrayHasKey('klevu_sessionId', $parameters);
        $this->assertNotNull($parameters['klevu_sessionId']);

        $this->assertArrayHasKey('klevu_clientIp', $parameters);
        $this->assertNotNull($parameters['klevu_clientIp']);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();

        $this->producttrackingActionMock = $this->getMockBuilder(Producttracking::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param bool $isSuccess
     * @param string|null $message
     *
     * @return Response|MockObject
     */
    private function getResponseMock($isSuccess, $message = '')
    {
        $responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $responseMock->method('isSuccess')->willReturn($isSuccess);
        $responseMock->method('getMessage')->willReturn($message);

        return $responseMock;
    }

    /**
     * @param string $sku
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku)
    {
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        return $productRepository->get($sku);
    }

    /**
     * Loads bundle order creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadBundleKlevuOrderFixtures()
    {
        require __DIR__ . '/_files/klevuOrderSync_bundleFixtures.php';
    }

    /**
     * Loads bundle order creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadBundleOrderFixtures()
    {
        require __DIR__ . '/_files/order_bundleFixtures.php';
    }

    /**
     * Rolls back bundle order creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadBundleOrderFixturesRollback()
    {
        require __DIR__ . '/_files/order_bundleFixtures_rollback.php';
    }

    /**
     * Loads bundle product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadBundleProductFixtures()
    {
        require __DIR__ . '/_files/product_bundleFixtures.php';
    }

    /**
     * Rolls back bundle product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadBundleProductFixturesRollback()
    {
        require __DIR__ . '/_files/product_bundleFixtures_rollback.php';
    }

    /**
     * Loads currency creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadCurrencyRatesFixtures()
    {
        require __DIR__ . '/_files/currencyRateFixtures.php';
    }

    /**
     * Rolls back currency creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadCurrencyRatesFixturesRollback()
    {
        require __DIR__ . '/_files/currencyRateFixtures_rollback.php';
    }

    /**
     * Loads store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        require __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back store and website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        require __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }
}
