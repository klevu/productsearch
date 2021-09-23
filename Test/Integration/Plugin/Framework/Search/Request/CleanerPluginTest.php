<?php

namespace Klevu\Search\Test\Integration\Plugin\Framework\Search\Request;

use Klevu\Search\Model\Api\Magento\Request\Product as ProductRequestApi;
use Klevu\Search\Model\Api\Magento\Request\ProductInterface as ProductRequestApiInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController;

class CleanerPluginTest extends AbstractController
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Scenario: When Klevu SRLP is enabled, results from the Klevu API should be used
     *              and not those from Magento's native search
     *
     *    Given: Search landing is enabled via Stores > Configuration for the applicable store
     *      and: Magento's native search returns results for a search query
     *      and: Klevu API returns no results for the same search query
     *     When: A search is performed on the frontend
     *     Then: The SRLP should return no results
     *
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadFixtures
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key ABCDEFG1234567890
     * @magentoConfigFixture default_store klevu_search/searchlanding/landenabled 1
     */
    public function testSearchResults_LandingEnabled_NativeResults_KlevuNoResults()
    {
        $this->setupPhp5();
        $this->checkCurrentEngine();

        $product = $this->productRepository->get('424756');
        $productRequestMock = $this->getProductRequestMock([]);

        //injecting to current instance of object mgr
        $this->objectManager->addSharedInstance($productRequestMock, ProductRequestApiInterface::class);
        $this->objectManager->addSharedInstance($productRequestMock, ProductRequestApi::class);

        $this->dispatch('catalogsearch/result/?q=rich');
        $responseBody = $this->getResponse()->getBody();
        $this->assertStringNotContainsString($product->getName(), $responseBody);
        $this->assertStringContainsString('Your search returned no results.', $responseBody);
    }

    /**
     * Scenario: When Klevu SRLP is enabled, results from the Klevu API should be used
     *              and not those from Magento's native search
     *
     *    Given: Search landing is enabled via Stores > Configuration for the applicable store
     *      and: Magento's native search returns no results for a search query
     *      and: Klevu API returns results for the same search query
     *     When: A search is performed on the frontend
     *     Then: The SRLP should return results from the Klevu API
     *
     * Ref: KS-5825. In later versions of Magento, some searches with no native results which
     *                  returned results from the API were not displaying results on SRLP
     *                  with search landing enabled
     *
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadFixtures
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key ABCDEFG1234567890
     * @magentoConfigFixture default_store klevu_search/searchlanding/landenabled 1
     */
    public function testSearchResults_LandingEnabled_NativeNoResults_KlevuResults()
    {
        $this->setupPhp5();
        $this->checkCurrentEngine();

        $product = $this->productRepository->get('424756');
        $productRequestMock = $this->getProductRequestMock([$product->getId()]);

        //injecting to current instance of object mgr
        $this->objectManager->addSharedInstance($productRequestMock, ProductRequestApiInterface::class);
        $this->objectManager->addSharedInstance($productRequestMock, ProductRequestApi::class);

        $this->dispatch('catalogsearch/result/?q=abcdefgh');
        $responseBody = $this->getResponse()->getBody();
        $this->assertStringContainsString($product->getName(), $responseBody);
        $this->assertStringNotContainsString('Your search returned no results.', $responseBody);
    }

    /**
     * Scenario: When Klevu SRLP is disabled, results from Magento's native search should be used
     *              and not those from Klevu API
     *
     *    Given: Search landing is disabled via Stores > Configuration for the applicable store
     *      and: Magento's native search returns results for a search query
     *      and: Klevu API returns no results for the same search query
     *     When: A search is performed on the frontend
     *     Then: The SRLP should return results from native search
     *
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadFixtures
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key ABCDEFG1234567890
     * @magentoConfigFixture default_store klevu_search/searchlanding/landenabled 0
     */
    public function testSearchResults_LandingDisabled_NativeResults_KlevuNoResults()
    {
        $this->setupPhp5();
        $this->checkCurrentEngine();

        $product = $this->productRepository->get('424756');
        $productRequestMock = $this->getProductRequestMock([]);

        //injecting to current instance of object mgr
        $this->objectManager->addSharedInstance($productRequestMock, ProductRequestApiInterface::class);
        $this->objectManager->addSharedInstance($productRequestMock, ProductRequestApi::class);

        $this->dispatch('catalogsearch/result/?q=rich');
        $responseBody = $this->getResponse()->getBody();
        $this->assertStringContainsString($product->getName(), $responseBody);
        $this->assertStringNotContainsString('Your search returned no results.', $responseBody);
    }

    /**
     * Scenario: When Klevu SRLP is enabled, results from the Klevu API should be used
     *              and not those from Magento's native search
     *
     *    Given: Search landing is disabled via Stores > Configuration for the applicable store
     *      and: Magento's native search returns no results for a search query
     *      and: Klevu API returns results for the same search query
     *     When: A search is performed on the frontend
     *     Then: The SRLP should return no results
     *
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadFixtures
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key ABCDEFG1234567890
     * @magentoConfigFixture default_store klevu_search/searchlanding/landenabled 0
     */
    public function testSearchResults_LandingDisabled_NativeNoResults_KlevuResults()
    {
        $this->setupPhp5();
        $this->checkCurrentEngine();

        $product = $this->productRepository->get('424756');
        $productRequestMock = $this->getProductRequestMock([$product->getId()]);

        //injecting to current instance of object mgr
        $this->objectManager->addSharedInstance($productRequestMock, ProductRequestApiInterface::class);
        $this->objectManager->addSharedInstance($productRequestMock, ProductRequestApi::class);

        $this->dispatch('catalogsearch/result/?q=abcdefgh');
        $responseBody = $this->getResponse()->getBody();
        $this->assertStringNotContainsString($product->getName(), $responseBody);
        $this->assertStringContainsString('Your search returned no results.', $responseBody);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
        $this->scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
    }

    /**
     * Check currently configured search engine and mark test skipped if not elasticssearch
     */
    private function checkCurrentEngine()
    {
        $currentEngine = $this->scopeConfig->getValue('catalog/search/engine');
        if (false === strpos($currentEngine, 'elasticsearch')) {
            $this->markTestSkipped(sprintf(
                'Search engine must use Elasticsearch to run this test; "%s" set',
                $currentEngine
            ));
        }
    }

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadFixtures()
    {
        require __DIR__ . '/_files/product_simple_cleaner.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadFixturesRollback()
    {
        require __DIR__ . '/_files/product_simple_cleaner_rollback.php';
    }

    /**
     * @param array $productIds
     * @return ProductRequestApiInterface|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getProductRequestMock(array $productIds)
    {
        $productRequestMock = $this->getMockBuilder(ProductRequestApiInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $productRequestMock->expects($this->any())
            ->method('_getKlevuProductIds')
            ->willReturn($productIds);
        return $productRequestMock;
    }
}
