<?php
/** @noinspection PhpSameParameterValueInspection */
/** @noinspection PhpUnhandledExceptionInspection */
// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Klevu\Search\Test\Integration\Metadata\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController as AbstractControllerTestCase;

class CheckoutCartOutputTest extends AbstractControllerTestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/metadata/enabled 0
     * @magentoConfigFixture default_store klevu_search/metadata/enabled 0
     * @magentoConfigFixture default/klevu_search/recommendations/enabled 0
     * @magentoConfigFixture default_store klevu_search/recommendations/enabled 0
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_frontendjs/configuration/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoDataFixture loadProductFixtures
     * @noinspection PhpParamsInspection
     */
    public function testJavascriptIsOutputToPageWhenSearchEnabled_ItemsInCart()
    {
        $this->setupPhp5();

        $quote = $this->createNewGuestCart(1, ['klevu_simple_1']);
        $this->checkoutSession->setQuoteId($quote->getId());

        $this->dispatch('/checkout/cart');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('[Klevu] Simple Product 1', $responseBody);
            $this->assertStringContainsString('<script type="text/javascript" id="klevu_page_meta">', $responseBody);
        } else {
            $this->assertContains('[Klevu] Simple Product 1', $responseBody);
            $this->assertContains('<script type="text/javascript" id="klevu_page_meta">', $responseBody);
        }
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#klevu_page_meta\s*=#', $responseBody);
            $this->assertMatchesRegularExpression('#"pageType"\s*:\s*"cart"#', $responseBody);
        } else {
            $this->assertRegExp('#klevu_page_meta\s*=#', $responseBody);
            $this->assertRegExp('#"pageType"\s*:\s*"cart"#', $responseBody);
        }

        $this->tearDownPhp5();
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->checkoutSession = $this->objectManager->get(CheckoutSession::class);
        $this->objectManager->addSharedInstance($this->checkoutSession, CheckoutSession::class);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function tearDownPhp5()
    {
        $this->objectManager->removeSharedInstance(CheckoutSession::class);
    }

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        require __DIR__ . '/../_files/productFixtures.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        require __DIR__ . '/../_files/productFixtures_rollback.php';
    }

    /**
     * @param int $storeId
     * @param string[] $skus
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function createNewGuestCart($storeId, array $skus)
    {
        /** @var Quote $quote */
        $quote = $this->objectManager->create(Quote::class);
        $quote->setStoreId($storeId);
        $quote->setIsActive(true);
        $quote->setIsMultiShipping(false);
        $quote->setCheckoutMethod('guest');
        $quote->setReservedOrderId('klevu_test_order_' . uniqid());
        $quote->setDataUsingMethod('email', 'no-reply@klevu.com');

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        foreach ($skus as $sku) {
            $product = $productRepository->get($sku);
            $quote->addProduct($product, 1);
        }

        $quote->collectTotals();
        /** @noinspection PhpDeprecationInspection */
        $quote->save();

        /** @var QuoteIdMask $quoteIdMask */
        $quoteIdMask = $this->objectManager->create(QuoteIdMaskFactory::class)->create();
        $quoteIdMask->setDataUsingMethod('quote_id', $quote->getId());
        $quoteIdMask->setDataChanges(true);
        /** @noinspection PhpDeprecationInspection */
        $quoteIdMask->save();

        return $quote;
    }
}
