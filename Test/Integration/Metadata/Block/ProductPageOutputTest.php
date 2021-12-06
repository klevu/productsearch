<?php
/** @noinspection DuplicatedCode */
/** @noinspection PhpSameParameterValueInspection */
/** @noinspection PhpPrivateFieldCanBeLocalVariableInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace Klevu\Search\Test\Integration\Metadata\Block;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController as AbstractControllerTestCase;

class ProductPageOutputTest extends AbstractControllerTestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var string
     */
    private $urlSuffix;

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
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
     * @magentoDbIsolation disabled
     * @noinspection PhpParamsInspection
     */
    public function testJavascriptIsOutputToPageWhenSearchEnabled()
    {
        $this->setupPhp5();

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $defaultStore = $storeManager->getStore('default');
        $storeManager->setCurrentStore($defaultStore->getId());

        $product = $this->productRepository->get('klevu_simple_1', false, $defaultStore->getId());
        $url = $this->prepareUrl($product->getUrlKey());

        $this->dispatch($url);

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('<script type="text/javascript" id="klevu_page_meta">', $responseBody);
        } else {
            $this->assertContains('<script type="text/javascript" id="klevu_page_meta">', $responseBody);
        }
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('#klevu_page_meta\s*=#', $responseBody);
            $this->assertMatchesRegularExpression('#"pageType"\s*:\s*"pdp"#', $responseBody);
        } else {
            $this->assertRegExp('#klevu_page_meta\s*=#', $responseBody);
            $this->assertRegExp('#"pageType"\s*:\s*"pdp"#', $responseBody);
        }
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->urlSuffix = $this->scopeConfig->getValue(
            ProductUrlPathGenerator::XML_PATH_PRODUCT_URL_SUFFIX,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Prepare url to dispatch
     *
     * @param string $urlKey
     * @param bool $addSuffix
     * @return string
     */
    private function prepareUrl($urlKey, $addSuffix = true)
    {
        return $addSuffix ? '/' . $urlKey . $this->urlSuffix : '/' . $urlKey;
    }

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../_files/productFixtures.php';
    }

    /**
     * Rolls back creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../_files/productFixtures_rollback.php';
    }
}
